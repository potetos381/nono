<?php
require_once 'MachinePayment.php';
header('Content-Type: application/json');
header('Cache-Control: no-store');

$mp = new MachinePayment();
$machines = method_exists($mp, 'getMachinesWithAvailability')
    ? $mp->getMachinesWithAvailability()
    : $mp->getMachinesWithLiveStatus();

$available = 0; $inUse = 0;
foreach ($machines as $m) {
    if ($m['status'] === 'available') $available++;
    if ($m['status'] === 'in_use')    $inUse++;
}

echo json_encode([
    'server_time' => date('c'),
    'summary' => [
        'total'     => count($machines),
        'available' => $available,
        'in_use'    => $inUse,
    ],
    'machines' => $machines,
]);