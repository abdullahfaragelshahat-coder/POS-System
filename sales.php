<?php
session_start();
include 'connect.php';

// حماية الصفحة: لو مش مسجل دخول ارجع لصفحة اللوجن
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$current_role = $_SESSION['role'] ?? 'cashier';

$message = "";
$error = "";

// جلب المنتجات المتاحة للبيع
$sql_products = "SELECT * FROM products WHERE quantity > 0 ORDER BY name ASC";
$stmt_products = $conn->prepare($sql_products);
$stmt_products->execute();
$all_products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);

// كود تنفيذ عملية البيع
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['make_sale'])) {
    $product_id = $_POST['product_id'];
    $quantity_sold = intval($_POST['quantity_sold']);

    if (!empty($product_id) && $quantity_sold > 0) {
        $sql_check = "SELECT * FROM products WHERE id = :id";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bindParam(':id', $product_id);
        $stmt_check->execute();
        $product = $stmt_check->fetch(PDO::FETCH_ASSOC);

        if ($product && $product['quantity'] >= $quantity_sold) {
            $total_price = $product['price'] * $quantity_sold;

            // حفظ الفاتورة باسم المستخدم اللي سجل دخول
            $sql_insert_sale = "INSERT INTO sales (total_price, user_id) VALUES (:total_price, :user_id)";
            $stmt_sale = $conn->prepare($sql_insert_sale);
            $stmt_sale->bindParam(':total_price', $total_price);
            $stmt_sale->bindParam(':user_id', $user_id);
            $stmt_sale->execute();
            $sale_id = $conn->lastInsertId();

            // حفظ تفاصيل البند
            $sql_item = "INSERT INTO sale_items (sale_id, product_id, quantity, price) VALUES (:sid, :pid, :qty, :price)";
            $stmt_item = $conn->prepare($sql_item);
            $stmt_item->bindParam(':sid', $sale_id);
            $stmt_item->bindParam(':pid', $product_id);
            $stmt_item->bindParam(':qty', $quantity_sold);
            $stmt_item->bindParam(':price', $product['price']);
            $stmt_item->execute();

            // تحديث كمية المخزن
            $new_qty = $product['quantity'] - $quantity_sold;
            $sql_update_prod = "UPDATE products SET quantity = :new_qty WHERE id = :pid";
            $stmt_update = $conn->prepare($sql_update_prod);
            $stmt_update->bindParam(':new_qty', $new_qty);
            $stmt_update->bindParam(':pid', $product_id);
            $stmt_update->execute();

            $message = "تم إتمام عملية البيع بنجاح! 🎉";
            
            // تحديث القائمة
            $stmt_products->execute();
            $all_products = $stmt_products->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $error = "عذراً، الكمية المطلوبة غير متوفرة!";
        }
    }
}

// جلب آخر الفواتير للعرض
$sql_recent = "SELECT sales.id, products.name AS product_name, sale_items.quantity, sales.total_price, sales.created_at 
               FROM sales 
               JOIN sale_items ON sales.id = sale_items.sale_id 
               JOIN products ON sale_items.product_id = products.id 
               ORDER BY sales.id DESC LIMIT 10";
$stmt_recent = $conn->prepare($sql_recent);
$stmt_recent->execute();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>شاشة المبيعات</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family: Arial, sans-serif; }
        body { background:#f4f4f4; padding:30px; }
        .container { max-width: 1000px; margin:0 auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
        .links-menu { margin-bottom: 25px; background: #eeedf7; padding: 14px; border-radius: 5px; }
        .links-menu a { text-decoration: none; color: #b8952e; font-weight: bold; margin-left: 25px; font-size: 15px; }
        h1, h2 { color:#333; margin-bottom:20px; font-size:22px; }
        .form-sale { display:flex; gap:15px; margin-bottom:30px; background:#f9f9f9; padding:20px; border-radius:8px; align-items: center; }
        select, input, button { padding:12px; border-radius:5px; border:1px solid #ccc; font-size: 15px; }
        button { background:#1e7e34; color:#fff; font-weight:bold; cursor:pointer; border:none; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:14px; text-align:right; border-bottom:1px solid #ddd; }
        th { background:#b8952e; color:#fff; }
        .success { background:#d4edda; color:#155724; padding:12px; border-radius:5px; margin-bottom:20px; }
        .error { background:#f8d7da; color:#721c24; padding:12px; border-radius:5px; margin-bottom:20px; }
        .user-badge { float: left; background: #333; color: #fff; padding: 5px 12px; border-radius: 20px; font-size: 13px; }
    </style>
</head>
<body>

<div class="container">
    <div class="links-menu">
        <span class="user-badge">مرحباً: <?php echo htmlspecialchars($username); ?> (<?php echo ($current_role === 'admin' ? 'مدير' : 'كاشير'); ?>) 👤</span>
        
        <?php if ($current_role === 'admin') { ?>
            <a href="dashboard.php">📊 لوحة التحكم والإحصائيات</a>
        <?php } ?>
        
        <a href="products.php">📦 إدارة المخزن والمنتجات</a>
        <a href="logout.php" style="color: red;">خروج ➡️</a>
    </div>

    <h1>شاشة البيع واستخراج الفواتير السريعة</h1>

    <?php if(!empty($message)) echo "<div class='success'>$message</div>"; ?>
    <?php if(!empty($error)) echo "<div class='error'>$error</div>"; ?>

    <form method="POST" class="form-sale">
        <select name="product_id" style="flex: 2.5;" required>
            <option value="">-- اختر المنتج --</option>
            <?php foreach ($all_products as $prod) { ?>
                <option value="<?php echo $prod['id']; ?>">
                    <?php echo htmlspecialchars($prod['name']); ?> (السعر: <?php echo number_format($prod['price'], 2); ?> ج.م) | المتاح: [<?php echo $prod['quantity']; ?>]
                </option>
            <?php } ?>
        </select>
        <input type="number" name="quantity_sold" placeholder="الكمية" min="1" style="flex: 1;" required>
        <button type="submit" name="make_sale">إتمام البيع 🛒</button>
    </form>

    <h2>آخر العمليات والفواتير الصادرة</h2>
    <table>
        <thead>
            <tr>
                <th>رقم الفاتورة</th>
                <th>اسم المنتج</th>
                <th>الكمية</th>
                <th>إجمالي الحساب</th>
                <th>التاريخ</th>
            </tr>
        </thead>
        <tbody>
            <?php while($sale = $stmt_recent->fetch(PDO::FETCH_ASSOC)) { ?>
            <tr>
                <td>#<?php echo $sale['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($sale['product_name']); ?></strong></td>
                <td><?php echo $sale['quantity']; ?> قطعة</td>
                <td style="color: #1e7e34; font-weight: bold;"><?php echo number_format($sale['total_price'], 2); ?> ج.م</td>
                <td><?php echo date('H:i : d-m-Y', strtotime($sale['created_at'])); ?></td>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>