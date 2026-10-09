<?php
// notifications.php — view sent notifications
$logFile = __DIR__ . '/logs/notifications.log';
$lines = file_exists($logFile) ? array_reverse(file($logFile)) : [];
$lines = array_slice($lines, 0, 100);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Notification Log — LaundryPay</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .wrap { max-width: 1000px; margin: 30px auto; padding: 0 20px;
                position: relative; z-index: 2; }
        .log-box {
            background: rgba(27, 38, 59, 0.75);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(76, 201, 240, 0.2);
            border-radius: 14px;
            padding: 18px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #a0d8ff;
            max-height: 600px;
            overflow-y: auto;
            box-shadow: 0 12px 30px rgba(0,0,0,0.4);
        }
        .log-box div {
            padding: 6px 0;
            border-bottom: 1px dashed rgba(76, 201, 240, 0.15);
        }
        .log-box div:last-child { border-bottom: none; }
        .empty {
            text-align: center;
            padding: 40px;
            color: var(--text-dim);
        }
    </style>
</head>
<body>
    <?php include 'assets.php'; ?>
    <div class="wrap">
        <h1 style="color: var(--accent); margin-bottom: 16px;">🔔 Notification Log</h1>
        <p style="color: var(--text-dim); margin-bottom: 20px; font-size: 14px;">
            Last <?= count($lines) ?> notifications sent.
        </p>

        <?php if (empty($lines)): ?>
            <div class="empty">No notifications sent yet.</div>
        <?php else: ?>
            <div class="log-box">
                <?php foreach ($lines as $line): ?>
                    <div><?= htmlspecialchars(rtrim($line)) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <p style="margin-top: 20px;">
            <a href="admin.php" class="btn btn-secondary" style="width: auto; display: inline-block;">← Back to Admin</a>
        </p>
    </div>
</body>
</html>