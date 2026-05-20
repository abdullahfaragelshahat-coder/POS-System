<?php
include 'connect.php';
session_start();

// 1. حماية الصفحة: التأكد من تسجيل الدخول أولاً
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// 2. فحص الصلاحية: لو مش أدمن، اطرده بره الصفحة ورجعه لشاشة المبيعات بتاعته
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('عذراً، هذه الصفحة مخصصة للإدارة فقط! 🚫'); window.location.href='sales.php';</script>";
    exit;
}

// حساب إجمالي أرباح ومبيعات المحل بالكامل
$sql_total = "SELECT SUM(total_price) AS grand_total FROM sales";
$stmt_total = $conn->prepare($sql_total);
$stmt_total->execute();
$total_data = $stmt_total->fetch(PDO::FETCH_ASSOC);
$grand_total = $total_data['grand_total'] ?? 0;

// حساب عدد الفواتير
$sql_count = "SELECT COUNT(id) AS total_sales_count FROM sales";
$stmt_count = $conn->prepare($sql_count);
$stmt_count->execute();
$count_data = $stmt_count->fetch(PDO::FETCH_ASSOC);
$sales_count = $count_data['total_sales_count'] ?? 0;

// جلب المنتجات الأكثر مبيعاً
$sql_popular = "SELECT products.name, SUM(sale_items.quantity) AS total_qty_sold 
                FROM sale_items 
                JOIN products ON sale_items.product_id = products.id 
                GROUP BY sale_items.product_id 
                ORDER BY total_qty_sold DESC LIMIT 3";
$stmt_popular = $conn->prepare($sql_popular);
$stmt_popular->execute();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>لوحة التحكم والإصدارات - الإدارة فقط</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
        body { background:#f4f4f4; padding:30px; }
        .container { max-width: 800px; margin:0 auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
        
        .links-menu { margin-bottom: 20px; background: #eeedf7; padding: 12px; border-radius: 5px; }
        .links-menu a { text-decoration: none; color: #b8952e; font-weight: bold; margin-left: 20px; font-size: 15px; }
        
        h1 { color:#333; margin-bottom:20px; font-size:24px; text-align:center; }
        h2 { color:#555; margin-top:30px; margin-bottom:15px; font-size:18px; }
        
        .stats-grid { display:flex; gap:20px; margin-bottom:20px; }
        .stat-card { flex:1; background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #ddd; text-align:center; }
        .stat-card p { font-size:14px; color:#666; margin-bottom:5px; }
        .stat-card h3 { font-size:24px; }
        
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { padding:12px; text-align:right; border-bottom:1px solid #ddd; }
        th { background:#b8952e; color:#fff; }
        .user-badge { float: left; background: #b8952e; color: #fff; padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: bold; }
    </style>
</head>
<body>

<div class="container">
    <div class="links-menu">
        <span class="user-badge">مرحباً بك: مدير السيستم 👑</span>
        <a href="products.php">📦 إدارة المخزن والمنتجات</a>
        <a href="sales.php">🛒 شاشة بيع الفواتير</a>
    </div>

    <h1>التقارير السرية والإحصائيات العامة للمحل</h1>

    <div class="stats-grid">
        <div class="stat-card" style="border-right: 4px solid #1e7e34;">
            <p>إجمالي المبيعات والدخل الحالي</p>
            <h3 style="color: #1e7e34;"><?php echo number_format($grand_total, 2); ?> ج.م</h3>
        </div>
        
        <div class="stat-card" style="border-right: 4px solid #b8952e;">
            <p>عدد الفواتير الصادرة</p>
            <h3 style="color: #b8952e;">الفواتير: <?php echo $sales_count; ?></h3>
        </div>
    </div>

    <h2>الأعـلـى مـبـيـعـاً فـي الـمـحـل 🔥</h2>
    <table>
        <thead>
            <tr>
                <th>اسم المنتج</th>
                <th>إجمالي الكمية المباعة</th>
            </tr>
        </thead>
        <tbody>
            <?php while($popular = $stmt_popular->fetch(PDO::FETCH_ASSOC)) { ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($popular['name']); ?></strong></td>
                <td style="font-weight: bold; color: #b8952e;"><?php echo $popular['total_qty_sold']; ?> قطعة</td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<div class="links-menu">
    <span class="user-badge">مرحباً بك: مدير السيستم 👑</span>
    <a href="daily_report.php" style="color: #1e7e34;">📅 التقرير المالي اليومي</a> <a href="products.php">📦 إدارة المخزن والمنتجات</a>
    <a href="sales.php">🛒 شاشة بيع الفواتير</a>
</div>
</body>
</html>