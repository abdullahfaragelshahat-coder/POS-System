<?php

session_start();
include 'connect.php';

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// 1. إجمالي مبيعات المستخدم
$sql_total = "SELECT SUM(total_price) AS total FROM sales WHERE user_id = :user_id";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->bindParam(':user_id', $user_id);
$stmt_total->execute();
$total_sales = $stmt_total->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;

// 2. عدد الفواتير
$sql_count = "SELECT COUNT(id) AS count FROM sales WHERE user_id = :user_id";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->bindParam(':user_id', $user_id);
$stmt_count->execute();
$invoice_count = $stmt_count->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

// 3. آخر عمليات البيع
$sql_recent = "SELECT sales.id, sales.total_price, sales.created_at
FROM sales
WHERE user_id = :user_id
ORDER BY sales.id DESC
LIMIT 5";

$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->bindParam(':user_id', $user_id);
$stmt_recent->execute();

?>

<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard</title>

    <style>
        body {
            font-family: Arial;
            background: #f5f5f5;
            padding: 20px;
        }

        .container {
            max-width: 800px;
            margin: auto;
            background: #fff;
            padding: 20px;
            border-radius: 10px;
        }

        .box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .card {
            flex: 1;
            margin: 10px;
            padding: 20px;
            background: #fff;
            border: 2px solid #d4af37;
            text-align: center;
            border-radius: 10px;
        }

        h2 {
            text-align: center;
        }

        table {
            width: 100%;
            margin-top: 20px;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #ddd;
            padding: 10px;
            text-align: center;
        }

        th {
            background: #d4af37;
            color: #fff;
        }

        .logout {
            display: block;
            text-align: center;
            margin-top: 20px;
        }

        a {
            text-decoration: none;
            color: #d4af37;
            font-weight: bold;
        }
    </style>
</head>
<body>

<div class="container">

    <h2>Welcome, <?php echo $username; ?> 👋</h2>

    <div class="box">

        <div class="card">
            <h3>💰 Total Sales</h3>
            <p><?php echo number_format($total_sales, 2); ?> EGP</p>
        </div>

        <div class="card">
            <h3>🧾 Invoices</h3>
            <p><?php echo $invoice_count; ?></p>
        </div>

    </div>

    <h3>Last Sales</h3>

    <table>
        <tr>
            <th>ID</th>
            <th>Total</th>
            <th>Date</th>
        </tr>

        <?php while($row = $stmt_recent->fetch(PDO::FETCH_ASSOC)) { ?>
        <tr>
            <td>#<?php echo $row['id']; ?></td>
            <td><?php echo number_format($row['total_price'], 2); ?></td>
            <td><?php echo $row['created_at']; ?></td>
        </tr>
        <?php } ?>

    </table>

    <div class="logout">
        <a href="logout.php">Logout</a>
    </div>

</div>

</body>
</html>