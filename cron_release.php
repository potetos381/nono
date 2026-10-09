<?php
// cron_all.php — releases finished machines + sends notifications
require_once 'MachinePayment.php';
require_once 'Notifier.php';

$mp = new MachinePayment();
$mp->releaseFinishedMachines();

$n = new Notifier();
$sent = $n->run();

echo "[" . date('Y-m-d H:i:s') . "] Released machines, notified {$sent} txns\n";