<?php
include 'connect.php';

if (!isset($_GET['id'])) {
    header("Location: products.php");
    exit;
}

$id = $_GET['id'];
$sql = "SELECT * FROM products WHERE id = :id";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':id', $id);
$stmt->execute();
$product = $stmt->fetch();

if (!$product) {
    die("المنتج غير موجود!");
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <title>تعديل بيانات المنتج</title>
    <style>
        * { margin:0; padding:0; box-sizing:border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background:#f4f6f9; display:flex; justify-content:center; align-items:center; min-height:100vh; }
        .container { width:100%; max-width:450px; background:#fff; padding:30px; border-radius:12px; box-shadow:0 4px 6px rgba(0,0,0,0.05); }
        h1 { margin-bottom:25px; text-align:center; color:#333; font-size:22px; }
        form { display:flex; flex-direction:column; gap:15px; }
        label { font-size:14px; color:#64748b; font-weight:600; }
        input { padding:12px; border-radius:6px; border:1px solid #cbd5e1; outline:none; font-size:15px; }
        input:focus { border-color:#4f46e5; }
        button { background:#4f46e5; color:#fff; font-weight:600; cursor:pointer; border:none; padding:14px; border-radius:6px; font-size:16px; margin-top:10px; }
        button:hover { background:#4338ca; }
        .back-link { text-align:center; margin-top:20px; display:block; color:#64748b; text-decoration:none; font-size:14px; }
    </style>
</head>
<body>

<div class="container">
    <h1>تعديل بيانات المنتج</h1>
    
    <form method="POST" action="update_product.php">
        <input type="hidden" name="id" value="<?php echo $product['id']; ?>">

        <label>اسم المنتج</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($product['name']); ?>" required>

        <label>السعر (جنيه)</label>
        <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required>

        <label>الكمية بالمخزن</label>
        <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" required>

        <button type="submit">حفظ التعديلات المحدثة</button>
    </form>

    <a href="products.php" class="back-link">← العودة للمخزن</a>
</div>

</body>
</html>