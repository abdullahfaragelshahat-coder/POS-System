<?php
include 'connect.php';

if (isset($_GET['id'])) {
    $sale_id = $_GET['id'];

    /* 1. إرجاع الكميات المباعة إلى المخزن أولاً */
    $sql_items = "SELECT * FROM sale_items WHERE sale_id = :id";
    $stmt_items = $conn->prepare($sql_items);
    $stmt_items->bindParam(':id', $sale_id);
    $stmt_items->execute();
    $items = $stmt_items->fetchAll();

    foreach ($items as $item) {
        $sql_update = "UPDATE products SET quantity = quantity + :qty WHERE id = :pid";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bindParam(':qty', $item['quantity']);
        $stmt_update->bindParam(':pid', $item['product_id']);
        $stmt_update->execute();
    }

    /* 2. حذف بنود الفاتورة */
    $conn->prepare("DELETE FROM sale_items WHERE sale_id=?")->execute([$sale_id]);

    /* 3. حذف الفاتورة الرئيسية */
    $conn->prepare("DELETE FROM sales WHERE id=?")->execute([$sale_id]);
}

header("Location: sales.php");
exit;
?>