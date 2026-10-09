<?php
require_once 'MachinePayment.php';

$useSSE = isset($_GET['sse']) && $_GET['sse'] == '1';
$mp = new MachinePayment();

/* ============================================================
   SSE MODE
   ============================================================ */
if ($useSSE) {
    header('Content-Type: text/event-stream');
    header('Cache-Control: no-cache');
    header('X-Accel-Buffering: no');
    header('Connection: keep-alive');

    while (ob_get_level() > 0) ob_end_flush();
    ob_implicit_flush(true);
    set_time_limit(0);

    $lastHash = '';
    $start = time();

    echo ": connected\n\n";
    flush();

    while (true) {
        if (connection_aborted()) break;
        if (time() - $start > 55) break;

        $hash = $mp->getStateHash();

        if ($hash !== $lastHash) {
            $lastHash = $hash;
            $snap = $mp->getLiveSnapshot();

            echo "event: update\n";
            echo "data: " . json_encode([
                'server_ts' => time(),
                'machines'  => $snap,
            ]) . "\n\n";
            flush();
        } else {
            echo ": heartbeat\n\n";
            flush();
            sleep(1);
        }
        usleep(500000);
    }
    exit;
}

/* ============================================================
   POLLING MODE
   ============================================================ */
header('Content-Type: application/json');
header('Cache-Control: no-store');

$lastHash    = $_GET['last_hash'] ?? '';
$force       = isset($_GET['force']);
$currentHash = $mp->getStateHash();

if (!$force && $currentHash === $lastHash) {
    echo json_encode([
        'changed'   => false,
        'server_ts' => time(),
        'hash'      => $currentHash,
    ]);
    exit;
}

$snapshot = $mp->getLiveSnapshot();

echo json_encode([
    'changed'   => true,
    'server_ts' => time(),
    'hash'      => $currentHash,
    'machines'  => $snapshot,
]);