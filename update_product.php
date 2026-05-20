<?php
include 'connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = trim($_POST['name']);
    $price = $_POST['price'];
    $quantity = $_POST['quantity'];

    if (!empty($id) && !empty($name) && !empty($price) && isset($quantity)) {
        $sql = "UPDATE products SET name=:name, price=:price, quantity=:quantity WHERE id=:id";
        $stmt = $conn->prepare($sql);

        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':name', $name);
        $stmt->bindParam(':price', $price);
        $stmt->bindParam(':quantity', $quantity);
        $stmt->execute();
    }
}

header("Location: products.php");
exit;
?>