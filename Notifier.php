<?php
// Notifier.php — sends notifications when cycles finish
require_once 'config.php';

class Notifier {
    private $pdo;

    public function __construct() {
        $this->pdo = getDB();
    }

    /**
     * Find all paid transactions whose cycle ended but haven't been notified yet.
     */
    public function getFinishedTransactions() {
        $stmt = $this->pdo->prepare("
            SELECT t.*, m.machine_code, m.machine_name
            FROM transactions t
            JOIN machines m ON m.id = t.machine_id
            WHERE t.status = 'paid'
              AND t.notified = 0
              AND t.cycle_ends_at IS NOT NULL
              AND t.cycle_ends_at <= NOW()
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }

    /**
     * Mark transaction as notified.
     */
    public function markNotified($transactionId) {
        $stmt = $this->pdo->prepare("
            UPDATE transactions
            SET notified = 1, notified_at = NOW()
            WHERE id = :id
        ");
        return $stmt->execute([':id' => $transactionId]);
    }

    /**
     * Send notification. In demo mode we just log; swap in real providers below.
     */
    public function send($transaction) {
        $message = "Hi! Your laundry on {$transaction['machine_code']} is DONE. "
                 . "Please collect your clothes. — LaundryPay";

        // === Pick ONE or MORE channels below ===

        // (a) Log to file (always)
        $this->logToFile($transaction, $message);

        // (b) SMS via Twilio (uncomment when configured)
        // $this->sendSMS($transaction['customer_phone'], $message);

        // (c) Email (uncomment when configured)
        // $this->sendEmail($transaction['customer_email'] ?? '', $message);

        // (d) WhatsApp via Twilio (uncomment)
        // $this->sendWhatsApp($transaction['customer_phone'], $message);

        return true;
    }

    /* ==================== CHANNELS ==================== */

    private function logToFile($txn, $message) {
        $log = __DIR__ . '/logs/notifications.log';
        if (!is_dir(dirname($log))) mkdir(dirname($log), 0755, true);
        $line = sprintf(
            "[%s] Txn:%s | Phone:%s | Machine:%s | %s\n",
            date('Y-m-d H:i:s'),
            $txn['transaction_ref'],
            $txn['customer_phone'],
            $txn['machine_code'],
            $message
        );
        file_put_contents($log, $line, FILE_APPEND);
    }

    /**
     * Twilio SMS — sign up at https://twilio.com
     * composer require twilio/sdk
     */
    public function sendSMS($to, $body) {
        // Uncomment & fill in when ready:
        /*
        require __DIR__ . '/vendor/autoload.php';
        $sid   = getenv('TWILIO_SID');
        $token = getenv('TWILIO_TOKEN');
        $from  = getenv('TWILIO_FROM');

        $client = new \Twilio\Rest\Client($sid, $token);
        $client->messages->create($to, ['from' => $from, 'body' => $body]);
        */
        return true;
    }

    /**
     * WhatsApp via Twilio
     */
    public function sendWhatsApp($to, $body) {
        /*
        require __DIR__ . '/vendor/autoload.php';
        $sid   = getenv('TWILIO_SID');
        $token = getenv('TWILIO_TOKEN');

        $client = new \Twilio\Rest\Client($sid, $token);
        $client->messages->create(
            "whatsapp:" . $to,
            ['from' => 'whatsapp:+14155238886', 'body' => $body]
        );
        */
        return true;
    }

    /**
     * Simple email via PHP mail() or PHPMailer
     */
    public function sendEmail($to, $body) {
        if (empty($to)) return false;
        $subject = "Your laundry is done!";
        $headers = "From: noreply@laundrypay.local\r\n";
        @mail($to, $subject, $body, $headers);
        return true;
    }

    /**
     * Cron entry point — call this every minute.
     */
    public function run() {
        $finished = $this->getFinishedTransactions();
        $count = 0;

        foreach ($finished as $txn) {
            $this->send($txn);
            $this->markNotified($txn['id']);
            $count++;
        }

        return $count;
    }

    /**
     * Fetch a transaction by ref (for the live-status API).
     */
    public function getTransactionByRef($ref) {
        $stmt = $this->pdo->prepare("
            SELECT t.*, m.machine_code, m.machine_name
            FROM transactions t
            JOIN machines m ON m.id = t.machine_id
            WHERE t.transaction_ref = :r
        ");
        $stmt->execute([':r' => $ref]);
        return $stmt->fetch();
    }
}
?>