<?php
session_start();
include 'connect.php'; // الربط على store_db

// 1. حماية الصفحة: التأكد من تسجيل الدخول أولاً ومن أنه مدير
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "<script>alert('عذراً، هذه الصفحة مخصصة للإدارة فقط! 🚫'); window.location.href='sales.php';</script>";
    exit;
}

// تاريخ اليوم الحالي بتنسيق قاعدة البيانات
$today = date('Y-m-d');

// أ) حساب إجمالي فلوس مبيعات اليوم فقط
$sql_today_total = "SELECT SUM(total_price) AS today_total FROM sales WHERE DATE(created_at) = :today";
$stmt_total = $conn->prepare($sql_today_total);
$stmt_total->execute([':today' => $today]);
$total_data = $stmt_total->fetch(PDO::FETCH_ASSOC);
$today_money = $total_data['today_total'] ?? 0;

// ب) حساب عدد فواتير اليوم فقط
$sql_today_count = "SELECT COUNT(id) AS today_count FROM sales WHERE DATE(created_at) = :today";
$stmt_count = $conn->prepare($sql_today_count);
$stmt_count->execute([':today' => $today]);
$count_data = $stmt_count->fetch(PDO::FETCH_ASSOC);
$today_bills = $count_data['today_count'] ?? 0;

// ج) جلب تفاصيل حية لكل البضاعة اللي اتباعت النهاردة
$sql_today_items = "SELECT sales.id AS bill_id, products.name AS prod_name, sale_items.quantity, sale_items.price, (sale_items.quantity * sale_items.price) AS total_item_price, sales.created_at 
                    FROM sale_items 
                    JOIN sales ON sale_items.sale_id = sales.id 
                    JOIN products ON sale_items.product_id = products.id 
                    WHERE DATE(sales.created_at) = :today
                    ORDER BY sales.id DESC";
$stmt_items = $conn->prepare($sql_today_items);
$stmt_items->execute([':today' => $today]);
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>التقرير المالي اليومي</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
        body { background:#f4f4f4; padding:30px; }
        .container { max-width: 900px; margin:0 auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
        
        .links-menu { margin-bottom: 20px; background: #eeedf7; padding: 12px; border-radius: 5px; }
        .links-menu a { text-decoration: none; color: #b8952e; font-weight: bold; margin-left: 20px; font-size: 15px; }
        
        h1 { color:#333; margin-bottom:5px; font-size:24px; text-align:center; }
        .report-date { text-align:center; color:#666; margin-bottom:25px; font-size:14px; font-weight:bold; }
        
        .stats-grid { display:flex; gap:20px; margin-bottom:30px; }
        .stat-card { flex:1; background:#f9f9f9; padding:20px; border-radius:8px; border:1px solid #ddd; text-align:center; }
        .stat-card p { font-size:14px; color:#666; margin-bottom:5px; }
        .stat-card h3 { font-size:24px; }
        
        table { width:100%; border-collapse:collapse; margin-top:10px; }
        th, td { padding:12px; text-align:right; border-bottom:1px solid #ddd; }
        th { background:#b8952e; color:#fff; }
        tr:hover { background: #fafafa; }
        
        /* زرار الطباعة اللطيف */
        .btn-print { background: #333; color: #fff; padding: 10px 20px; border: none; border-radius: 5px; font-weight: bold; cursor: pointer; float: left; margin-top: -10px; }
        
        /* كود لإخفاء القائمة والأزرار عند طباعة التقرير ورقياً */
        @media print {
            .links-menu, .btn-print { display: none; }
            body { padding: 0; background: #fff; }
            .container { box-shadow: none; max-width: 100%; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="links-menu">
        <a href="dashboard.php">📊 الرجوع للوحة التحكم</a>
        <a href="sales.php">🛒 شاشة بيع الفواتير</a>
    </div>

    <button class="btn-print" onclick="window.print()">🖨️ طباعة التقرير</button>
    <h1>تقرير حركة المبيعات اليومية 🧾</h1>
    <div class="report-date">تاريخ اليوم: <?php echo date('d-m-Y', strtotime($today)); ?></div>

    <div class="stats-grid">
        <div class="stat-card" style="border-right: 4px solid #1e7e34;">
            <p>درج النقدية الحالي (النهاردة)</p>
            <h3 style="color: #1e7e34;"><?php echo number_format($today_money, 2); ?> ج.م</h3>
        </div>
        
        <div class="stat-card" style="border-right: 4px solid #b8952e;">
            <p>إجمالي فواتير اليوم</p>
            <h3 style="color: #b8952e;"><?php echo $today_bills; ?> فاتورة</h3>
        </div>
    </div>

    <h2>تفاصيل حركة مبيعات بضاعة اليوم:</h2>
    <table>
        <thead>
            <tr>
                <th>رقم الفاتورة</th>
                <th>اسم المنتج المباع</th>
                <th>الكمية</th>
                <th>سعر القطعة</th>
                <th>الإجمالي</th>
                <th>وقت البيع</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $has_items = false;
            while($item = $stmt_items->fetch(PDO::FETCH_ASSOC)) { 
                $has_items = true;
            ?>
            <tr>
                <td>#<?php echo $item['bill_id']; ?></td>
                <td><strong><?php echo htmlspecialchars($item['prod_name']); ?></strong></td>
                <td><?php echo $item['quantity']; ?> قطعة</td>
                <td><?php echo number_format($item['price'], 2); ?> ج.م</td>
                <td style="color:#1e7e34; font-weight:bold;"><?php echo number_format($item['total_item_price'], 2); ?> ج.م</td>
                <td style="font-size:13px; color:#777;"><?php echo date('H:i A', strtotime($item['created_at'])); ?></td>
            </tr>
            <?php } 
            if(!$has_items) {
                echo "<tr><td colspan='6' style='text-align:center; color:#999; padding:20px;'>لم يتم بيع أي منتجات اليوم حتى الآن.</td></tr>";
            }
            ?>
        </tbody>
    </table>
</div>

</body>
</html>