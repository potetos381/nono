<?php
require_once 'MachinePayment.php';
$mp = new MachinePayment();

$code = $_GET['code'] ?? '';
$machine = $mp->getMachine($code);
if (!$machine) die("Machine not found. Please scan a valid QR code.");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($machine['machine_code']) ?> — <?= APP_NAME ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'assets.php'; ?>

    <div class="card">
        <div class="machine-icon"><?= $machine['machine_type'] === 'dryer' ? '🌀' : '🧺' ?></div>
        <h1><?= e($machine['machine_name']) ?></h1>
        <p class="subtitle"><?= e($machine['machine_code']) ?> • <?= e($machine['location']) ?></p>

        <span class="status s-<?= $machine['status'] ?>"><?= strtoupper($machine['status']) ?></span>

        <div class="price">
            <?= CURRENCY ?> <?= money($machine['price_per_cycle']) ?>
            <small>/ cycle</small>
        </div>
        <p class="subtitle">⏱ <?= (int)$machine['cycle_minutes'] ?> minutes</p>

        <?php if ($machine['status'] === 'available'): ?>
            <a class="btn" href="pay.php?machine_id=<?= $machine['id'] ?>">💳 Pay &amp; Start Machine</a>
        <?php else: ?>
            <div class="alert alert-error" style="text-align:center;">
                ⚠ Machine is <?= e($machine['status']) ?>. Please try again later.
            </div>
        <?php endif; ?>

        <a href="board.php" class="btn btn-secondary" style="margin-top:10px;">📺 View Live Board</a>
        <a href="index.php" class="btn btn-secondary" style="margin-top:10px;">← Back</a>
    </div>
</body>
</html>