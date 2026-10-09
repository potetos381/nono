<?php
// status_api.php?ref=WM240101ABC123
// Returns JSON: { finished: true/false, remaining: seconds, machine: "WM-01", ... }
require_once 'Notifier.php';

header('Content-Type: application/json');

$ref = $_GET['ref'] ?? '';
if ($ref === '') {
    echo json_encode(['error' => 'Missing ref']);
    exit;
}

$n = new Notifier();
$txn = $n->getTransactionByRef($ref);

if (!$txn) {
    echo json_encode(['error' => 'Not found']);
    exit;
}

$now = time();
$end = strtotime($txn['cycle_ends_at']);
$remaining = max(0, $end - $now);
$finished = ($remaining === 0 && $txn['cycle_ends_at'] !== null);

// If finished and not yet notified → notify now (fallback for the cron)
if ($finished && (int)$txn['notified'] === 0) {
    $n->send($txn);
    $n->markNotified($txn['id']);
}

echo json_encode([
    'transaction_ref' => $txn['transaction_ref'],
    'machine_code'    => $txn['machine_code'],
    'machine_name'    => $txn['machine_name'],
    'status'          => $txn['status'],
    'cycle_ends_at'   => $txn['cycle_ends_at'],
    'finished'        => $finished,
    'remaining'       => $remaining,
    'notified'        => (int)$txn['notified'],
]);