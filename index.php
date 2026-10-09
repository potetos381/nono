<?php
require_once 'MachinePayment.php';
$mp = new MachinePayment();
$machines = $mp->getAllMachines();

$countAvailable = 0; $countInUse = 0;
foreach ($machines as $m) {
    if ($m['status'] === 'available') $countAvailable++;
    if ($m['status'] === 'in_use')    $countInUse++;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= APP_NAME ?> — Self-Service Washing Machines</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .page-header { position: relative; z-index: 2; text-align: center; padding: 50px 20px 10px; }
        .page-header .logo {
            width: 96px; height: 96px; margin: 0 auto 14px;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, #4cc9f0, #1e3a5f 70%);
            display: flex; align-items: center; justify-content: center;
            font-size: 48px;
            box-shadow: 0 0 50px rgba(76,201,240,0.6), inset 0 0 24px rgba(255,255,255,0.2);
            animation: spinPulse 4s ease-in-out infinite;
        }
        .page-header h1 { font-size: 34px; }
        .page-header p  { color: var(--text-dim); font-size: 14px; }

        .quick-links { margin-top: 20px; display: flex; gap: 10px; justify-content: center; flex-wrap: wrap; }
        .quick-links a {
            padding: 10px 20px; border-radius: 20px;
            font-size: 13px; text-decoration: none; font-weight: 700;
            background: rgba(27,38,59,0.7);
            border: 1px solid rgba(76,201,240,0.3);
            color: var(--accent);
        }
        .quick-links a:hover { background: rgba(76,201,240,0.15); }

        .summary { display: flex; justify-content: center; gap: 14px; flex-wrap: wrap; margin: 24px auto 10px; padding: 0 20px; position: relative; z-index: 2; }
        .summary .chip {
            background: rgba(27,38,59,0.7); backdrop-filter: blur(10px);
            border: 1px solid rgba(76,201,240,0.25);
            padding: 8px 18px; border-radius: 30px; font-size: 13px;
            color: var(--text-dim); display: flex; align-items: center; gap: 8px;
        }
        .summary .chip b { color: var(--accent); font-size: 15px; }
        .dot { width: 8px; height: 8px; border-radius: 50%; }
        .dot-green  { background: #2a9d8f; box-shadow: 0 0 10px #2a9d8f; }
        .dot-orange { background: #e76f51; box-shadow: 0 0 10px #e76f51; }

        .grid {
            position: relative; z-index: 2;
            max-width: 1000px; margin: 26px auto 60px;
            padding: 0 20px;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(230px, 1fr));
            gap: 18px;
        }
        .m-card {
            background: rgba(27,38,59,0.75); backdrop-filter: blur(14px);
            border: 1px solid rgba(76,201,240,0.2);
            border-radius: 18px; padding: 22px 18px; text-align: center;
            box-shadow: 0 12px 32px rgba(0,0,0,0.35);
            transition: transform 0.25s, box-shadow 0.25s, border-color 0.25s;
            animation: cardIn 0.6s cubic-bezier(.2,.8,.3,1) both;
        }
        .m-card:hover { transform: translateY(-6px); border-color: rgba(76,201,240,0.6); box-shadow: 0 18px 44px rgba(76,201,240,0.35); }
        .m-card .icon { font-size: 40px; margin-bottom: 8px; display: block; }
        .m-card h3 { color: var(--accent); margin-bottom: 4px; font-size: 18px; }
        .m-card p { color: var(--text-dim); font-size: 12px; margin-bottom: 4px; }
        .m-card .price-sm {
            font-size: 26px; font-weight: 800; color: var(--accent-3);
            margin: 12px 0 8px; text-shadow: 0 0 20px rgba(249,199,79,0.5);
        }
        .m-card .btn { font-size: 13px; padding: 11px; margin-top: 14px; }
        .m-card .btn.disabled {
            background: rgba(108,117,125,0.4); box-shadow: none;
            cursor: not-allowed; pointer-events: none; color: #c8d4e0;
        }
        footer { position: relative; z-index: 2; text-align: center; color: var(--text-dim); font-size: 12px; padding: 30px 20px 40px; opacity: 0.7; }
    </style>
</head>
<body>
    <?php include 'assets.php'; ?>

    <header class="page-header">
        <div class="logo">🧺</div>
        <h1><?= APP_NAME ?></h1>
        <p>Scan • Pay • Wash — Self-Service Washing Machines</p>
        <div class="quick-links">
            <a href="board.php">📺 Live Status Board</a>
            <a href="admin.php">⚙ Admin Panel</a>
        </div>
    </header>

    <?php if (!empty($machines)): ?>
    <div class="summary">
        <div class="chip"><span class="dot dot-green"></span> Available: <b><?= $countAvailable ?></b></div>
        <div class="chip"><span class="dot dot-orange"></span> In Use: <b><?= $countInUse ?></b></div>
        <div class="chip">Total: <b><?= count($machines) ?></b></div>
    </div>
    <?php endif; ?>

    <div class="grid">
        <?php foreach ($machines as $m): ?>
            <?php
                $icon = ($m['machine_type'] === 'dryer') ? '🌀' : '🧺';
                $isAvailable = ($m['status'] === 'available');
            ?>
            <div class="m-card">
                <span class="icon"><?= $icon ?></span>
                <h3><?= e($m['machine_name']) ?></h3>
                <p><?= e($m['machine_code']) ?></p>
                <p>📍 <?= e($m['location']) ?></p>
                <div class="price-sm"><?= CURRENCY ?> <?= money($m['price_per_cycle']) ?></div>
                <span class="status s-<?= $m['status'] ?>"><?= strtoupper($m['status']) ?></span>
                <?php if ($isAvailable): ?>
                    <a class="btn" href="machine.php?code=<?= urlencode($m['machine_code']) ?>">Select Machine</a>
                <?php else: ?>
                    <a class="btn disabled" href="#"><?= $m['status'] === 'in_use' ? 'In Use' : 'Unavailable' ?></a>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>

    <footer>© <?= date('Y') ?> <?= APP_NAME ?> — PHP + QR Payment System</footer>
</body>
</html>