<?php
session_start();
require_once 'MachinePayment.php';
$mp = new MachinePayment();

$token = $_GET['token'] ?? '';

if ($token) {
    $session = $mp->getQrSession($token);
    if (!$session) die("Invalid QR code.");
} elseif (!empty($_GET['machine_id'])) {
    $new = $mp->createQrSession((int)$_GET['machine_id']);
    if (!$new) die("Cannot create QR session.");
    header("Location: pay.php?token=" . $new['token']);
    exit;
} else {
    die("Missing token or machine_id.");
}

if ($session['status'] !== 'active') die("This QR session is no longer valid.");

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pay — <?= e($session['machine_code']) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <?php include 'assets.php'; ?>

    <div class="card">
        <div class="machine-icon">💳</div>
        <h2><?= e($session['machine_name']) ?></h2>
        <p class="subtitle"><?= e($session['machine_code']) ?> • <?= e($session['location']) ?></p>
        <link rel="stylesheet" href="style.css">                

        <div class="price"><?= CURRENCY ?> <?= money($session['amount']) ?></div>
        <p class="subtitle">Cycle: <?= (int)$session['cycle_minutes'] ?> minutes</p>

        <?php if (!empty($_SESSION['pay_error'])): ?>
            <div class="alert alert-error"><?= e($_SESSION['pay_error']) ?></div>
            <?php unset($_SESSION['pay_error']); ?>
        <?php endif; ?>

        <form method="POST" action="pay_process.php">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="qr_token" value="<?= e($session['qr_token']) ?>">

            <label>Your Name (optional)</label>
            <input type="text" name="customer_name" placeholder="e.g., Ahmad">

            <label>Phone Number *</label>
            <input type="tel" name="customer_phone" required placeholder="01XXXXXXXX">

            <label>Payment Method *</label>
            <select name="payment_method" required>
                <option value="qr_wallet">QR Wallet (Touch 'n Go / DuitNow)</option>
                <option value="e_wallet">E-Wallet (GrabPay / Boost)</option>
                <option value="online_banking">Online Banking (FPX)</option>
                <option value="credit_card">Credit / Debit Card</option>
            </select>

            <button type="submit" class="btn">🔒 Pay <?= CURRENCY ?> <?= money($session['amount']) ?></button>
        </form>
    </div>
</body>
</html>