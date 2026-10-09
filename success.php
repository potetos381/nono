<?php
require_once 'MachinePayment.php';
$mp = new MachinePayment();
$txn = $mp->getTransaction($_GET['ref'] ?? '');
if (!$txn) die("Transaction not found.");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Washing — <?= htmlspecialchars($txn['transaction_ref']) ?></title>
    <link rel="stylesheet" href="style.css">
    <style>
        .progress {
            width: 100%;
            height: 14px;
            background: rgba(10, 17, 40, 0.7);
            border-radius: 20px;
            overflow: hidden;
            margin: 22px 0 10px;
            border: 1px solid rgba(76, 201, 240, 0.3);
        }
        .progress-bar {
            height: 100%;
            width: 0%;
            background: linear-gradient(90deg, #4cc9f0, #7b2cbf, #f9c74f);
            background-size: 200% 100%;
            animation: shimmer 2s linear infinite;
            transition: width 1s linear;
            border-radius: 20px;
        }
        @keyframes shimmer {
            0%   { background-position: 0% 50%; }
            100% { background-position: 200% 50%; }
        }
        .status-line {
            font-size: 14px;
            color: var(--text-dim);
            margin-top: 6px;
        }
        .done-banner {
            display: none;
            margin: 20px 0;
            padding: 18px;
            border-radius: 14px;
            background: linear-gradient(135deg, #2a9d8f, #21867a);
            box-shadow: 0 0 40px rgba(42, 157, 143, 0.6);
            animation: pop 0.6s cubic-bezier(.3, 1.5, .6, 1) both;
        }
        .done-banner.show { display: block; }
        .done-banner h3 {
            color: #fff;
            font-size: 22px;
            margin-bottom: 6px;
            letter-spacing: 1px;
        }
        .done-banner p {
            color: #eaf4ff;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include 'assets.php'; ?>

    <div class="card">
        <div class="check">✓</div>
        <h2>Payment Successful</h2>
        <p class="subtitle">Machine is starting — please load your laundry</p>

        <div style="margin-top:20px;">
            <div class="info-row"><span>Reference</span><span><?= htmlspecialchars($txn['transaction_ref']) ?></span></div>
            <div class="info-row"><span>Machine</span><span><?= htmlspecialchars($txn['machine_code']) ?></span></div>
            <div class="info-row"><span>Amount</span><span>RM <?= money($txn['amount']) ?></span></div>
            <div class="info-row"><span>Method</span><span><?= ucwords(str_replace('_',' ', $txn['payment_method'])) ?></span></div>
        </div>

        <!-- Progress bar -->
        <div class="progress"><div class="progress-bar" id="bar"></div></div>
        <p class="status-line">⏳ Time remaining</p>
        <div class="timer" id="timer">--:--</div>

        <!-- When cycle finishes -->
        <div class="done-banner" id="doneBanner">
            <h3>🎉 Your laundry is DONE!</h3>
            <p>Please collect your clothes from <strong id="doneMachine"><?= htmlspecialchars($txn['machine_code']) ?></strong>.</p>
        </div>

        <!-- Browser notification permission -->
        <button class="btn btn-secondary" id="enableNotify" style="margin-top:16px;">
            🔔 Enable Notifications
        </button>
        <p class="status-line" id="notifyStatus" style="font-size:12px; margin-top:8px;"></p>

        <a href="index.php" class="btn btn-secondary" style="margin-top:16px;">
            ← Back to machines
        </a>
    </div>

    <script>
    // ---- Config from PHP ----
    const REF        = <?= json_encode($txn['transaction_ref']) ?>;
    const MACHINE    = <?= json_encode($txn['machine_code']) ?>;
    const START      = <?= json_encode(date('c', strtotime($txn['cycle_started_at'] ?? $txn['created_at']))) ?>;
    const END_TIME   = new Date(<?= json_encode(date('c', strtotime($txn['cycle_ends_at']))) ?>).getTime();
    const TOTAL_MS   = END_TIME - new Date(START).getTime();

    let notificationFired = false;

    // ---- Countdown + Progress Bar ----
    function tick() {
        const now = Date.now();
        const diff = Math.max(0, END_TIME - now);

        const m = Math.floor(diff / 60000);
        const s = Math.floor((diff % 60000) / 1000);
        document.getElementById('timer').textContent =
            String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');

        // Progress bar width: 0% → 100%
        const elapsed = TOTAL_MS - diff;
        const pct = Math.min(100, Math.max(0, (elapsed / TOTAL_MS) * 100));
        document.getElementById('bar').style.width = pct + '%';

        if (diff <= 0 && !notificationFired) {
            fireDone();
        }
        if (diff > 0) {
            setTimeout(tick, 1000);
        }
    }
    tick();

    // ---- Fire the "done" event ----
    function fireDone() {
        notificationFired = true;

        // 1. Show on-screen banner
        document.getElementById('doneBanner').classList.add('show');
        document.getElementById('doneMachine').textContent = MACHINE;

        // 2. Play beep sound
        playBeep();

        // 3. Browser notification
        sendBrowserNotification();

        // 4. Vibrate (mobile)
        if (navigator.vibrate) navigator.vibrate([300, 100, 300, 100, 300]);

        // 5. Ping the API so the server marks it done (if cron hasn't yet)
        fetch('status_api.php?ref=' + encodeURIComponent(REF))
            .then(r => r.json())
            .then(d => console.log('Status synced:', d))
            .catch(err => console.warn('Sync failed', err));
    }

    // ---- Beep using Web Audio API ----
    function playBeep() {
        try {
            const ctx = new (window.AudioContext || window.webkitAudioContext)();
            [0, 0.4, 0.8].forEach(delay => {
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.frequency.value = 880;
                osc.type = 'sine';
                gain.gain.setValueAtTime(0.2, ctx.currentTime + delay);
                gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + delay + 0.3);
                osc.start(ctx.currentTime + delay);
                osc.stop(ctx.currentTime + delay + 0.3);
            });
        } catch (e) { console.warn('Beep failed', e); }
    }

    // ---- Browser notification ----
    const notifyBtn = document.getElementById('enableNotify');
    const notifyStatus = document.getElementById('notifyStatus');

    if (!('Notification' in window)) {
        notifyBtn.style.display = 'none';
        notifyStatus.textContent = 'Notifications not supported on this browser.';
    } else if (Notification.permission === 'granted') {
        notifyBtn.style.display = 'none';
        notifyStatus.textContent = '✅ Browser notifications enabled.';
    } else if (Notification.permission === 'denied') {
        notifyBtn.style.display = 'none';
        notifyStatus.textContent = '⚠ Notifications blocked. Enable in browser settings.';
    }

    notifyBtn.addEventListener('click', async () => {
        const perm = await Notification.requestPermission();
        if (perm === 'granted') {
            notifyBtn.style.display = 'none';
            notifyStatus.textContent = '✅ Browser notifications enabled.';
        } else {
            notifyStatus.textContent = '⚠ Permission not granted.';
        }
    });

    function sendBrowserNotification() {
        if (Notification.permission === 'granted') {
            new Notification('🧺 Laundry Done!', {
                body: `Your clothes on ${MACHINE} are ready to collect.`,
                icon: 'assets/icon.png',   // optional
                tag: 'laundry-done',
                requireInteraction: true,
            });
        }
    }

    // ---- Background polling (every 15s) — in case the tab is left open ----
    setInterval(() => {
        fetch('status_api.php?ref=' + encodeURIComponent(REF))
            .then(r => r.json())
            .then(d => {
                if (d.finished && !notificationFired) fireDone();
            })
            .catch(() => {});
    }, 15000);
    </script>
</body>
</html>