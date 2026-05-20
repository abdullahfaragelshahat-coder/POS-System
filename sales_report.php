<?php
include 'connect.php';

$date = $_GET['date'] ?? date('Y-m-d');

$sql = "SELECT * FROM sales 
        WHERE DATE(created_at) = :date";

$stmt = $conn->prepare($sql);
$stmt->bindParam(':date', $date);
$stmt->execute();

$sales = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<form method="GET">
    <input type="date" name="date" value="<?php echo $date; ?>">
    <button>Filter</button>
</form>

<?php foreach($sales as $sale) { ?>
    <p>
        #<?php echo $sale['id']; ?> -
        <?php echo $sale['total_price']; ?>
    </p>
<?php } ?>