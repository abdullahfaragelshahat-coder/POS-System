<?php
include 'connect.php';
session_start();

// حماية الصفحة
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$current_role = $_SESSION['role'] ?? 'cashier';
$message = "";

// 1. كود الحذف (مسموح للأدمن فقط برمجياً لحماية السيرفر)
if (isset($_GET['delete_id'])) {
    if ($current_role === 'admin') {
        $delete_id = $_GET['delete_id'];
        $sql_delete = "DELETE FROM products WHERE id = :id";
        $stmt_delete = $conn->prepare($sql_delete);
        $stmt_delete->bindParam(':id', $delete_id);
        if ($stmt_delete->execute()) {
            $message = "تم حذف المنتج بنجاح!";
        }
    } else {
        echo "<script>alert('غير مسموح لك بالحذف!'); window.location.href='products.php';</script>";
        exit;
    }
}

// 2. كود الإضافة (مسموح للأدمن فقط)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    if ($current_role === 'admin') {
        $name = trim($_POST['name']);
        $price = $_POST['price'];
        $quantity = $_POST['quantity'];

        if (!empty($name) && !empty($price) && isset($quantity)) {
            $sql = "INSERT INTO products (name, price, quantity) VALUES (:name, :price, :quantity)";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':name', $name);
            $stmt->bindParam(':price', $price);
            $stmt->bindParam(':quantity', $quantity);
            if ($stmt->execute()) {
                $message = "تم إضافة المنتج بنجاح!";
            }
        }
    } else {
        echo "<script>alert('غير مسموح لك بإضافة منتجات!'); window.location.href='products.php';</script>";
        exit;
    }
}

$sql_select = "SELECT * FROM products ORDER BY id DESC";
$stmt_select = $conn->prepare($sql_select);
$stmt_select->execute();
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>إدارة وجرد المخزن</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family:Arial, sans-serif; }
        body { background:#f4f4f4; padding:30px; }
        .container { max-width: 900px; margin:0 auto; background:#fff; padding:30px; border-radius:10px; box-shadow:0 0 10px rgba(0,0,0,0.1); }
        .links-menu { margin-bottom: 20px; background: #eeedf7; padding: 12px; border-radius: 5px; }
        .links-menu a { text-decoration: none; color: #b8952e; font-weight: bold; margin-left: 20px; font-size: 15px; }
        h1 { color:#333; margin-bottom:20px; font-size:22px; }
        .form-inline { display:flex; gap:15px; margin-bottom:30px; background:#f9f9f9; padding:20px; border-radius:8px; }
        input, button { padding:12px; border-radius:5px; border:1px solid #ccc; }
        button { background:#b8952e; color:#fff; font-weight:bold; cursor:pointer; border:none; }
        table { width:100%; border-collapse:collapse; }
        th, td { padding:12px; text-align:right; border-bottom:1px solid #ddd; }
        th { background:#b8952e; color:#fff; }
        .btn-delete { color:red; text-decoration:none; margin-left:10px; font-weight:bold; }
        .btn-edit { color:blue; text-decoration:none; font-weight:bold; }
        .success { background:#d4edda; color:#155724; padding:12px; border-radius:5px; margin-bottom:20px; }
        .user-badge { float: left; background: #333; color: #fff; padding: 4px 10px; border-radius: 20px; font-size: 12px; }
    </style>
</head>
<body>

<div class="container">
    <div class="links-menu">
        <span class="user-badge">الصلاحية الحالية: <?php echo ($current_role === 'admin' ? 'مدير 👑' : 'كاشير 🛒'); ?></span>
        <?php if ($current_role === 'admin') { ?>
            <a href="dashboard.php">📊 لوحة التحكم والإحصائيات</a>
        <?php } ?>
        <a href="sales.php">🛒 شاشة بيع الفواتير</a>
    </div>

    <h1>شاشة جرد وعرض المخزن</h1>
    
    <?php if(!empty($message)) echo "<div class='success'>$message</div>"; ?>

    <?php if ($current_role === 'admin') { ?>
        <form method="POST" class="form-inline">
            <input type="text" name="name" placeholder="اسم المنتج الجديد" style="flex: 2; " required>
            <input type="number" step="0.01" name="price" placeholder="السعر" style="flex: 1;" required>
            <input type="number" name="quantity" placeholder="الكمية" style="flex: 1;" required>
            <button type="submit" name="add_product">إضافة المنتج</button>
        </form>
    <?php } ?>

    <table>
        <thead>
            <tr>
                <th>كود المنتج</th>
                <th>اسم المنتج</th>
                <th>السعر</th>
                <th>الكمية المتاحة</th>
                <?php if ($current_role === 'admin') { ?> <th>التحكم</th> <?php } ?>
            </tr>
        </thead>
        <tbody>
            <?php while($row = $stmt_select->fetch(PDO::FETCH_ASSOC)) { ?>
            <tr>
                <td>#<?php echo $row['id']; ?></td>
                <td><strong><?php echo htmlspecialchars($row['name']); ?></strong></td>
                <td style="color: #b8952e; font-weight: bold;"><?php echo number_format($row['price'], 2); ?> ج.م</td>
                <td><?php echo $row['quantity']; ?> قطعة</td>
                
                <?php if ($current_role === 'admin') { ?>
                <td>
                    <a href="products.php?delete_id=<?php echo $row['id']; ?>" class="btn-delete" onclick="return confirm('هل أنت متأكد؟')">حذف ❌</a>
                    <a href="edit_product.php?id=<?php echo $row['id']; ?>" class="btn-edit">تعديل ✏️</a>
                </td>
                <?php } ?>
            </tr>
            <?php } ?>
        </tbody>
    </table>
</div>

</body>
</html>