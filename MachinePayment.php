<?php
/**
 * MachinePayment.php
 * -----------------------------------------------------------
 * Core business logic for the LaundryPay QR payment system.
 *
 * Handles:
 *   • Machine lookups & status
 *   • QR session creation & validation
 *   • Payment processing (simulated gateway)
 *   • Transaction retrieval
 *   • Live status for public board
 *   • "Available at" info for real-time board
 *   • Auto-release of finished machines
 *   • Real-time snapshot & state hash for SSE
 * -----------------------------------------------------------
 */

require_once 'config.php';

class MachinePayment {

    /** @var PDO */
    private $pdo;

    public function __construct() {
        $this->pdo = getDB();
    }

    /* ==========================================================
       MACHINES — read
       ========================================================== */

    /**
     * Fetch a machine by its code (e.g. WM-01).
     */
    public function getMachine(string $code): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM machines WHERE machine_code = :c");
        $stmt->execute([':c' => $code]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Fetch a machine by its numeric ID.
     */
    public function getMachineById(int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT * FROM machines WHERE id = :id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Fetch all machines (simple list, no live info).
     */
    public function getAllMachines(): array {
        return $this->pdo->query("SELECT * FROM machines ORDER BY machine_code")->fetchAll();
    }

    /* ==========================================================
       MACHINES — write
       ========================================================== */

    /**
     * Update a machine's status.
     */
    public function updateMachineStatus(int $machineId, string $status): bool {
        $valid = ['available', 'in_use', 'maintenance', 'offline'];
        if (!in_array($status, $valid, true)) return false;

        $stmt = $this->pdo->prepare("
            UPDATE machines SET status = :s WHERE id = :id
        ");
        return $stmt->execute([':s' => $status, ':id' => $machineId]);
    }

    /* ==========================================================
       QR SESSIONS
       ========================================================== */

    /**
     * Create a fresh QR session for a machine.
     * Returns array with token, amount, expires_at, payment_url — or null on failure.
     */
    public function createQrSession(int $machineId): ?array {
        $machine = $this->getMachineById($machineId);
        if (!$machine) return null;

        // Expire any existing active sessions for this machine
        $this->pdo->prepare("
            UPDATE qr_sessions SET status = 'expired'
            WHERE machine_id = :m AND status = 'active'
        ")->execute([':m' => $machineId]);

        $token    = bin2hex(random_bytes(32));
        $amount   = $machine['price_per_cycle'];
        $expires  = date('Y-m-d H:i:s', strtotime('+' . QR_EXPIRE_MINUTES . ' minutes'));

        $stmt = $this->pdo->prepare("
            INSERT INTO qr_sessions (machine_id, qr_token, amount, expires_at)
            VALUES (:m, :t, :a, :e)
        ");
        $stmt->execute([
            ':m' => $machineId,
            ':t' => $token,
            ':a' => $amount,
            ':e' => $expires,
        ]);

        return [
            'token'       => $token,
            'amount'      => $amount,
            'expires_at'  => $expires,
            'payment_url' => BASE_URL . "pay.php?token=" . $token,
        ];
    }

    /**
     * Fetch a QR session by token, including machine info.
     */
    public function getQrSession(string $token): ?array {
        $stmt = $this->pdo->prepare("
            SELECT
                q.*,
                m.machine_code,
                m.machine_name,
                m.location,
                m.cycle_minutes,
                m.status AS machine_status
            FROM qr_sessions q
            JOIN machines m ON m.id = q.machine_id
            WHERE q.qr_token = :t
        ");
        $stmt->execute([':t' => $token]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /* ==========================================================
       PAYMENT
       ========================================================== */

    /**
     * Generate a unique transaction reference.
     */
    private function generateRef(): string {
        return 'WM' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));
    }

    /**
     * Process a payment. In production, replace the simulated
     * gateway block with a real Stripe / PayPal / Billplz call.
     *
     * @return array{success: bool, errors?: array, transaction_ref?: string,
     *               amount?: float, machine?: string, cycle_ends_at?: string}
     */
    public function processPayment(array $data): array {
        /* ---------- 1. Validate QR session ---------- */
        $session = $this->getQrSession($data['qr_token'] ?? '');
        if (!$session) {
            return ['success' => false, 'errors' => ['Invalid QR code.']];
        }
        if ($session['status'] !== 'active') {
            return ['success' => false, 'errors' => ['QR code already used or expired.']];
        }
        if (strtotime($session['expires_at']) < time()) {
            $this->pdo->prepare("UPDATE qr_sessions SET status='expired' WHERE qr_token=:t")
                      ->execute([':t' => $data['qr_token']]);
            return ['success' => false, 'errors' => ['QR code has expired. Please scan again.']];
        }
        if ($session['machine_status'] !== 'available') {
            return ['success' => false, 'errors' => ['Machine is not available.']];
        }

        /* ---------- 2. Validate form fields ---------- */
        $errors = [];

        if (empty($data['customer_phone'])
            || !preg_match('/^[0-9+\-\s]{8,20}$/', $data['customer_phone'])) {
            $errors[] = "Valid phone number is required.";
        }

        $validMethods = ['qr_wallet', 'online_banking', 'credit_card', 'e_wallet'];
        if (empty($data['payment_method'])
            || !in_array($data['payment_method'], $validMethods, true)) {
            $errors[] = "Invalid payment method.";
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        /* ---------- 3. Simulated gateway ---------- */
        // In production:
        //   $charge = $stripe->charges->create([...]);
        //   if ($charge->status !== 'succeeded') return ['success'=>false, ...];
        $gatewayRef = 'GW' . strtoupper(bin2hex(random_bytes(6)));

        /* ---------- 4. Persist transaction ---------- */
        $ref        = $this->generateRef();
        $cycleStart = date('Y-m-d H:i:s');
        $cycleEnd   = date('Y-m-d H:i:s', strtotime('+' . $session['cycle_minutes'] . ' minutes'));

        try {
            $this->pdo->beginTransaction();

            $stmt = $this->pdo->prepare("
                INSERT INTO transactions
                (transaction_ref, machine_id, qr_token, customer_name, customer_phone,
                 amount, payment_method, payment_gateway_ref, status,
                 cycle_started_at, cycle_ends_at)
                VALUES
                (:ref, :mid, :tok, :name, :phone,
                 :amt, :method, :gw, 'paid',
                 :start, :end)
            ");
            $stmt->execute([
                ':ref'    => $ref,
                ':mid'    => $session['machine_id'],
                ':tok'    => $data['qr_token'],
                ':name'   => $data['customer_name'] ?? 'Guest',
                ':phone'  => $data['customer_phone'],
                ':amt'    => $session['amount'],
                ':method' => $data['payment_method'],
                ':gw'     => $gatewayRef,
                ':start'  => $cycleStart,
                ':end'    => $cycleEnd,
            ]);

            $this->pdo->prepare("UPDATE qr_sessions SET status='used' WHERE qr_token=:t")
                      ->execute([':t' => $data['qr_token']]);

            $this->pdo->prepare("
                UPDATE machines
                SET status='in_use', last_used=NOW()
                WHERE id=:id
            ")->execute([':id' => $session['machine_id']]);

            $this->pdo->commit();

            return [
                'success'         => true,
                'transaction_ref' => $ref,
                'amount'          => $session['amount'],
                'machine'         => $session['machine_code'],
                'cycle_ends_at'   => $cycleEnd,
            ];

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            error_log("Payment error: " . $e->getMessage());
            return ['success' => false, 'errors' => ['System error. Please try again.']];
        }
    }

    /* ==========================================================
       TRANSACTIONS
       ========================================================== */

    /**
     * Fetch a single transaction by reference.
     */
    public function getTransaction(string $ref): ?array {
        $stmt = $this->pdo->prepare("
            SELECT t.*, m.machine_code, m.machine_name, m.location
            FROM transactions t
            JOIN machines m ON m.id = t.machine_id
            WHERE t.transaction_ref = :r
        ");
        $stmt->execute([':r' => $ref]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * Get the current active cycle for a machine (if any).
     */
    public function getMachineCurrentCycle(int $machineId): ?array {
        $stmt = $this->pdo->prepare("
            SELECT id, transaction_ref, cycle_started_at, cycle_ends_at
            FROM transactions
            WHERE machine_id = :mid
              AND status = 'paid'
              AND cycle_ends_at IS NOT NULL
              AND cycle_ends_at >= NOW()
            ORDER BY cycle_ends_at ASC
            LIMIT 1
        ");
        $stmt->execute([':mid' => $machineId]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    /**
     * List all transactions (paginated).
     */
    public function getAllTransactions(int $limit = 20, int $offset = 0): array {
        $stmt = $this->pdo->prepare("
            SELECT t.*, m.machine_code
            FROM transactions t
            JOIN machines m ON m.id = t.machine_id
            ORDER BY t.created_at DESC
            LIMIT :l OFFSET :o
        ");
        $stmt->bindValue(':l', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':o', $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Today's revenue stats.
     */
    public function getTodayRevenue(): array {
        return $this->pdo->query("
            SELECT
                COALESCE(SUM(amount), 0) AS total,
                COUNT(*)                 AS txn
            FROM transactions
            WHERE status = 'paid'
              AND DATE(created_at) = CURDATE()
        ")->fetch();
    }

    /* ==========================================================
       LIVE STATUS (public board)
       ========================================================== */

    /**
     * Full live status: every machine, plus its current cycle info,
     * remaining seconds, and progress %.
     */
    public function getMachinesWithLiveStatus(): array {
        $sql = "
            SELECT
                m.id,
                m.machine_code,
                m.machine_name,
                m.location,
                m.machine_type,
                m.price_per_cycle,
                m.cycle_minutes,
                m.status,
                t.transaction_ref,
                t.cycle_started_at,
                t.cycle_ends_at,
                t.customer_name,
                t.customer_phone
            FROM machines m
            LEFT JOIN transactions t
                ON t.machine_id = m.id
                AND t.status = 'paid'
                AND t.cycle_ends_at IS NOT NULL
                AND t.cycle_ends_at >= NOW()
            ORDER BY m.machine_code
        ";
        $rows = $this->pdo->query($sql)->fetchAll();
        $now  = time();
        $out  = [];

        foreach ($rows as $r) {
            $endsAt    = $r['cycle_ends_at']    ? strtotime($r['cycle_ends_at'])    : null;
            $startedAt = $r['cycle_started_at'] ? strtotime($r['cycle_started_at']) : null;
            $remaining = $endsAt ? max(0, $endsAt - $now) : 0;

            /* Progress % */
            $progress = 0;
            if ($startedAt && $endsAt && $endsAt > $startedAt) {
                $total    = $endsAt - $startedAt;
                $elapsed  = $now - $startedAt;
                $progress = min(100, max(0, ($elapsed / $total) * 100));
            }

            /* Auto-flip finished machines */
            $status = $r['status'];
            if ($remaining === 0 && $status === 'in_use') {
                $status = 'available';
            }

            /* Mask phone */
            $maskedPhone = null;
            if (!empty($r['customer_phone'])) {
                $p = preg_replace('/\D/', '', $r['customer_phone']);
                $maskedPhone = strlen($p) > 6
                    ? substr($p, 0, 3) . '-***-' . substr($p, -3)
                    : '***';
            }

            $out[] = [
                'id'             => (int)$r['id'],
                'machine_code'   => $r['machine_code'],
                'machine_name'   => $r['machine_name'],
                'location'       => $r['location'],
                'machine_type'   => $r['machine_type'],
                'price'          => (float)$r['price_per_cycle'],
                'cycle_minutes'  => (int)$r['cycle_minutes'],
                'status'         => $status,
                'remaining'      => $remaining,
                'progress'       => round($progress, 1),
                'ends_at'        => $r['cycle_ends_at'],
                'started_at'     => $r['cycle_started_at'],
                'customer'       => $r['customer_name'] ?: 'Guest',
                'customer_phone' => $maskedPhone,
            ];
        }

        /* Sort: running first (soonest finish), then available, then others */
        usort($out, function ($a, $b) {
            $order = ['in_use' => 0, 'available' => 1, 'maintenance' => 2, 'offline' => 3];
            $oa = $order[$a['status']] ?? 9;
            $ob = $order[$b['status']] ?? 9;
            if ($oa !== $ob) return $oa - $ob;
            return $a['remaining'] - $b['remaining'];
        });

        return $out;
    }

    /**
     * Live status extended with human-readable "available at" info.
     */
    public function getMachinesWithAvailability(): array {
        $machines = $this->getMachinesWithLiveStatus();

        foreach ($machines as &$m) {
            if ($m['status'] === 'in_use' && !empty($m['ends_at'])) {
                $endTs = strtotime($m['ends_at']);

                $m['available_at']     = date('H:i', $endTs);
                $m['available_at_iso'] = date('c', $endTs);
                $m['available_in']     = $this->humanDiff($endTs - time());
                $m['available_when']   = (date('Y-m-d', $endTs) === date('Y-m-d'))
                    ? 'today' : 'tomorrow';
                $m['started_ago']      = !empty($m['started_at'])
                    ? $this->humanDiff(time() - strtotime($m['started_at'])) . ' ago'
                    : null;

            } elseif ($m['status'] === 'available') {
                $m['available_at']     = 'now';
                $m['available_at_iso'] = date('c');
                $m['available_in']     = 'now';
                $m['available_when']   = 'now';
                $m['started_ago']      = null;

            } else {
                $m['available_at']     = null;
                $m['available_at_iso'] = null;
                $m['available_in']     = 'unavailable';
                $m['available_when']   = 'unknown';
                $m['started_ago']      = null;
            }
        }
        unset($m); // break the reference

        return $machines;
    }

    /**
     * Compact snapshot for the real-time board.
     * Returns UNIX timestamps (ints) for smooth client-side countdown.
     */
    public function getLiveSnapshot(): array {
        $sql = "
            SELECT
                m.id,
                m.machine_code,
                m.machine_name,
                m.location,
                m.machine_type,
                m.price_per_cycle,
                m.cycle_minutes,
                m.status,
                UNIX_TIMESTAMP(t.cycle_started_at) AS started_ts,
                UNIX_TIMESTAMP(t.cycle_ends_at)    AS ends_ts,
                t.transaction_ref,
                t.customer_name
            FROM machines m
            LEFT JOIN transactions t
                ON t.machine_id = m.id
                AND t.status = 'paid'
                AND t.cycle_ends_at IS NOT NULL
                AND t.cycle_ends_at >= NOW()
            ORDER BY m.machine_code
        ";
        $rows = $this->pdo->query($sql)->fetchAll();

        $now = time();
        $out = [];

        foreach ($rows as $r) {
            $endsTs    = $r['ends_ts']    ? (int)$r['ends_ts']    : null;
            $startedTs = $r['started_ts'] ? (int)$r['started_ts'] : null;

            $status = $r['status'];
            if ($status === 'in_use' && $endsTs && $endsTs <= $now) {
                $status    = 'available';
                $endsTs    = null;
                $startedTs = null;
            }

            $out[] = [
                'id'            => (int)$r['id'],
                'machine_code'  => $r['machine_code'],
                'machine_name'  => $r['machine_name'],
                'location'      => $r['location'],
                'machine_type'  => $r['machine_type'],
                'price'         => (float)$r['price_per_cycle'],
                'cycle_minutes' => (int)$r['cycle_minutes'],
                'status'        => $status,
                'started_ts'    => $startedTs,
                'ends_ts'       => $endsTs,
                'customer'      => $r['customer_name'] ?: 'Guest',
                'server_ts'     => $now,
            ];
        }
        return $out;
    }

    /**
     * MD5 hash of current state — used to detect changes without heavy queries.
     */
    public function getStateHash(): string {
        $sql = "
            SELECT
                m.id,
                m.status,
                UNIX_TIMESTAMP(t.cycle_ends_at) AS ends_ts
            FROM machines m
            LEFT JOIN transactions t
                ON t.machine_id = m.id
                AND t.status = 'paid'
                AND t.cycle_ends_at >= NOW()
            ORDER BY m.id
        ";
        $rows = $this->pdo->query($sql)->fetchAll();
        return md5(json_encode($rows));
    }

    /* ==========================================================
       AUTO-RELEASE
       ========================================================== */

    /**
     * Free any machines whose cycle has ended.
     * Called from cron or at the top of the board.
     */
    public function releaseFinishedMachines(): void {
        $this->pdo->exec("
            UPDATE machines m
            JOIN transactions t ON t.machine_id = m.id
            SET m.status = 'available'
            WHERE m.status = 'in_use'
              AND t.status = 'paid'
              AND t.cycle_ends_at IS NOT NULL
              AND t.cycle_ends_at < NOW()
        ");
    }

    /* ==========================================================
       HELPERS
       ========================================================== */

    /**
     * Human-readable time diff: "45 sec", "12 min", "1 hr 5 min".
     */
    private function humanDiff(int $seconds): string {
        $seconds = max(0, $seconds);

        if ($seconds < 60) {
            return $seconds . ' sec';
        }
        $min = (int)floor($seconds / 60);
        if ($min < 60) {
            return $min . ' min';
        }
        $hr  = (int)floor($min / 60);
        $rem = $min % 60;
        return $rem > 0 ? "{$hr} hr {$rem} min" : "{$hr} hr";
    }
}