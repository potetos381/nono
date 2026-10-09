<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<pre style='background:#111;color:#0f0;padding:20px;'>";
echo "=== FILE CHECK ===\n\n";

$files = [
    'config.php',
    'MachinePayment.php',
    'api_realtime.php',
    'board.php',
    'style.css',
    'assetsphp',
];

foreach ($files as $f) {
    echo (file_exists(__DIR__ . '/' . $f) ? "✅ " : "❌ MISSING: ") . $f . "\n";
}

echo "\n=== CLASS CHECK ===\n\n";
require_once 'config.php';
require_once 'MachinePayment.php';

echo (class_exists('MachinePayment') ? "✅ MachinePayment class loads\n" : "❌ Class failed to load\n");

$methods = [
    'getLiveSnapshot',
    'getStateHash',
    'getMachinesWithLiveStatus',
    'getAllMachines',
];
foreach ($methods as $m) {
    echo (method_exists('MachinePayment', $m) ? "✅ " : "❌ MISSING: ") . $m . "()\n";
}

echo "\n=== DB CHECK ===\n\n";
try {
    $pdo = getDB();
    $db = $pdo->query("SELECT DATABASE() AS d")->fetch();
    echo "✅ Connected to: {$db['d']}\n";

    $tables = ['machines', 'qr_sessions', 'transactions'];
    foreach ($tables as $t) {
        try {
            $c = $pdo->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            echo "✅ $t: $c rows\n";
        } catch (Exception $e) {
            echo "❌ $t: " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "❌ DB failed: " . $e->getMessage() . "\n";
}

echo "\n=== LIVE SNAPSHOT TEST ===\n\n";
try {
    $mp = new MachinePayment();
    $snap = $mp->getLiveSnapshot();
    echo "✅ getLiveSnapshot() returned " . count($snap) . " machines\n";
    if (!empty($snap)) {
        echo "First machine:\n";
        print_r($snap[0]);
    }
} catch (Exception $e) {
    echo "❌ " . $e->getMessage() . "\n";
}

echo "\n=== STATE HASH TEST ===\n\n";
try {
    $mp = new MachinePayment();
    echo "✅ Hash: " . $mp->getStateHash() . "\n";
} catch (Exception $e) {
    echo "❌ " . $e->getMessage() . "\n";
}

echo "</pre>";