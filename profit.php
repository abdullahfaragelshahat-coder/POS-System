<?php
include 'connect.php';

$sql = "SELECT 
        SUM(sale_items.quantity * sale_items.price) AS revenue
        FROM sale_items";

$stmt = $conn->prepare($sql);
$stmt->execute();
$data = $stmt->fetch(PDO::FETCH_ASSOC);

$revenue = $data['revenue'] ?? 0;
?>

<h1>Profit Report</h1>

<p>Total Revenue: <?php echo $revenue; ?> EGP</p>