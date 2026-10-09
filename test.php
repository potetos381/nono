<?php
// test.php — diagnostic
require_once 'config.php';

echo "<h2>🔍 LaundryPay Diagnostics</h2>";

// 1. Test DB connection
try {
    $pdo = getDB();
    echo "✅ Database connected successfully<br>";
} catch (Exception $e) {
    die("❌ DB Connection failed: " . $e->getMessage());
}

// 2. Check which database we're connected to
$row = $pdo->query("SELECT DATABASE() AS db")->fetch();
echo "📁 Connected to database: <b>" . htmlspecialchars($row['db']) . "</b><br><br>";

// 3. List all tables
echo "<b>Tables in this database:</b><br>";
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
if (empty($tables)) {
    echo "❌ <span style='color:red'>NO TABLES FOUND — you haven't imported the SQL yet!</span><br>";
} else {
    foreach ($tables as $t) echo "• " . htmlspecialchars($t) . "<br>";
}
echo "<br>";

// 4. Check machines table
try {
    $count = $pdo->query("SELECT COUNT(*) FROM machines")->fetchColumn();
    echo "🧺 Machines in table: <b>" . $count . "</b><br>";
    if ($count > 0) {
        $rows = $pdo->query("SELECT * FROM machines")->fetchAll();
        echo "<table border='1' cellpadding='6' style='border-collapse:collapse; margin-top:10px;'>";
        echo "<tr><th>ID</th><th>Code</th><th>Name</th><th>Location</th><th>Price</th><th>Status</th></tr>";
        foreach ($rows as $r) {
            echo "<tr>";
            echo "<td>" . $r['id'] . "</td>";
            echo "<td>" . htmlspecialchars($r['machine_code']) . "</td>";
            echo "<td>" . htmlspecialchars($r['machine_name']) . "</td>";
            echo "<td>" . htmlspecialchars($r['location']) . "</td>";
            echo "<td>" . $r['price_per_cycle'] . "</td>";
            echo "<td>" . $r['status'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
} catch (PDOException $e) {
    echo "❌ <span style='color:red'>Cannot read machines table: " . $e->getMessage() . "</span><br>";
    echo "👉 You probably need to import the SQL schema.<br>";
}