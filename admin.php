<?php
require_once 'MachinePayment.php';
$mp = new MachinePayment();

// Auto-release finished cycles (can also be run via cron)
$mp->releaseFinishedMachines();

$machines = $mp->getAllMachines();
$txns     = $mp->getAllTransactions(15);
$today    = $mp->getTodayRevenue();
?>
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="style.css">
    <meta charset="UTF-8">
    <title>Laundry Admin</title>
    <a href="index.php" class="btn btn-secondary" style="margin-top:10px;">← Back</a>
    <style>
        h1 { color:#0d1b2a; }
        .stats { display:flex; gap:15px; margin-bottom:20px; }
        .stat { flex:1; background:#fff; padding:18px; border-radius:10px;
                box-shadow:0 2px 6px rgba(0,0,0,0.06); border-left:4px solid #4cc9f0; }
        .stat span { color:#666; font-size:12px; }
        .stat strong { font-size:24px; display:block; color:#0d1b2a; }
        .machines { display:grid; grid-template-columns:repeat(auto-fill,minmax(220px,1fr));
                    gap:15px; margin-bottom:25px; }
        .m { background:#fff; padding:15px; border-radius:10px;
             box-shadow:0 2px 6px rgba(0,0,0,0.06); }
        .m h3 { margin:0 0 5px; color:#0d1b2a; }
        .m p { margin:2px 0; font-size:13px; color:#666; }
        .m .status { display:inline-block; padding:3px 10px; border-radius:12px;
                     font-size:11px; font-weight:bold; margin-top:8px; color:#fff; }
        .s-available { background:#2a9d8f; }
        .s-in_use    { background:#e76f51; }
        .s-maintenance { background:#f4a261; }
        .s-offline   { background:#6c757d; }
        .m .qr-btn { margin-top:10px; display:inline-block; font-size:12px;
                     color:#4cc9f0; text-decoration:none; }
        table { width:100%; background:#4cc9f0; border-collapse:collapse;
                box-shadow:0 2px 6px rgba(0,0,0,0.06); border-radius:10px; overflow:hidden; }
        th, td { padding:10px; text-align:left; border-bottom:1px solid #eee; font-size:13px; }
        th { background:#0d1b2a; color:#fff; }
    </style>
</head>
<body>
    <h1>🧺 Laundry System Admin</h1>

    <div class="stats">
        <div class="stat"><span>TODAY'S REVENUE</span><strong>RM <?= money($today['total']) ?></strong></div>
        <div class="stat"><span>TRANSACTIONS TODAY</span><strong><?= (int)$today['txn'] ?></strong></div>
        <div class="stat"><span>MACHINES ONLINE</span><strong><?= count($machines) ?></strong></div>
    </div>

    <h2>Machines</h2>
    <div class="machines">
        <?php foreach ($machines as $m): ?>
            <div class="m">
                <h3><?= htmlspecialchars($m['machine_name']) ?></h3>
                <p><?= htmlspecialchars($m['machine_code']) ?></p>
                <p><?= htmlspecialchars($m['location']) ?></p>
                <p>RM <?= money($m['price_per_cycle']) ?> / <?= (int)$m['cycle_minutes'] ?> min</p>
                <span class="status s-<?= $m['status'] ?>"><?= strtoupper($m['status']) ?></span><br>
                <a class="qr-btn" target="_blank"
                   href="qr_print.php?code=<?= urlencode($m['machine_code']) ?>">
                   🖨 Print QR Sticker
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <h2>Recent Transactions</h2>
    <table>
        <thead>
            <tr>
                <th>Ref</th><th>Machine</th><th>Phone</th>
                <th>Amount</th><th>Method</th><th>Status</th><th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($txns as $t): ?>
            <tr>
                <td><?= htmlspecialchars($t['transaction_ref']) ?></td>
                <td><?= htmlspecialchars($t['machine_code']) ?></td>
                <td><?= htmlspecialchars($t['customer_phone']) ?></td>
                <td>RM <?= money($t['amount']) ?></td>
                <td><?= ucwords(str_replace('_',' ', $t['payment_method'])) ?></td>
                <td><?= strtoupper($t['status']) ?></td>
                <td><?= htmlspecialchars($t['created_at']) ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>