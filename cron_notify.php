<?php
// cron_notify.php — run every minute
require_once 'Notifier.php';

$n = new Notifier();
$sent = $n->run();

echo "[" . date('Y-m-d H:i:s') . "] Notified: {$sent} transaction(s)\n";