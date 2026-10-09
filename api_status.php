<?php
require_once 'MachinePayment.php';
header('Content-Type: application/json');

$ref = $_GET['ref'] ?? '';
if (!$ref) { echo json_encode(['error' => 'Missing ref']); exit; }

$mp = new MachinePayment();
$txn = $mp->getTransaction($ref);
if (!$txn) { echo json_encode(['error' => 'Not found']); exit; }

$now = time();
$end = $txn['cycle_ends_at'] ? strtotime($txn['cycle_ends_at']) : 0;
$remaining = max(0, $end - $now);

echo json_encode([
    'transaction_ref' => $txn['transaction_ref'],
    'machine_code'    => $txn['machine_code'],
    'status'          => $txn['status'],
    'cycle_ends_at'   => $txn['cycle_ends_at'],
    'finished'        => $remaining === 0,
    'remaining'       => $remaining,
]);