<?php
require_once 'config.php';
$code = $_GET['code'] ?? '';
if (!$code) die("Missing machine code.");

$url   = BASE_URL . "machine.php?code=" . urlencode($code);
$qrSrc = "generate_qr.php?data=" . urlencode($url);
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>QR Sticker — <?= htmlspecialchars($code) ?></title>
    <style>
        body {
            font-family: Arial; text-align: center; padding: 30px;
            background: linear-gradient(135deg, #0a1128, #1e3a5f);
            min-height: 100vh; color: #fff;
        }
        .sticker {
            display: inline-block;
            padding: 24px 28px;
            border-radius: 20px;
            background: linear-gradient(135deg, #ffffff, #eaf4ff);
            color: #0a1128;
            border: 4px dashed #4cc9f0;
            box-shadow: 0 20px 60px rgba(76, 201, 240, 0.45);
            position: relative;
        }
        .sticker::before {
            content: '🧺';
            position: absolute;
            top: -28px; left: 50%; transform: translateX(-50%);
            width: 56px; height: 56px;
            background: #4cc9f0;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            font-size: 28px;
            box-shadow: 0 6px 20px rgba(76, 201, 240, 0.6);
        }
        .code   { font-size: 30px; font-weight: 900; letter-spacing: 1px; margin-top: 10px; }
        .label  { font-size: 14px; color: #444; margin-top: 10px; font-weight: 600; }
        .price  { font-size: 22px; color: #c0392b; font-weight: 900; margin-top: 6px; }
        .sub    { font-size: 11px; color: #666; margin-top: 4px; letter-spacing: 1px; }
        img { display: block; margin: 10px auto; }
        @media print {
            body { background: #fff; }
            .no-print { display: none; }
        }
        .no-print button, .no-print a {
            margin: 20px 6px 0;
            padding: 10px 18px;
            border-radius: 8px;
            background: #4cc9f0;
            border: none;
            color: #0a1128;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
        }
    </style>
</head>
<body onload="window.print()">
    <div class="sticker">
        <div class="code"><?= htmlspecialchars($code) ?></div>
        <img src="<?= $qrSrc ?>" width="250" height="250" alt="QR Code">
        <div class="label">📱 Scan to Pay &amp; Start</div>
        <div class="price">RM 4.00</div>
        <div class="sub">LAUNDRYPAY • SELF-SERVICE</div>
    </div>

    <p class="no-print">
        <button onclick="window.print()">🖨 Print</button>
        <a href="admin.php">← Admin</a>
    </p>
</body>
</html>