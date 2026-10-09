
<?php
session_start();
require_once 'MachinePayment.php';


if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php"); exit;
}

if (empty($_POST['csrf_token']) || $_POST['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
    $_SESSION['pay_error'] = "Invalid session. Please try again.";
    header("Location: pay.php?token=" . urlencode($_POST['qr_token'] ?? ''));
    exit;
}

$mp = new MachinePayment();
$result = $mp->processPayment($_POST);

if ($result['success']) {
    header("Location: success.php?ref=" . urlencode($result['transaction_ref']));
    exit;
}
$_SESSION['pay_error'] = implode(' ', $result['errors']);
header("Location: pay.php?token=" . urlencode($_POST['qr_token']));
exit;