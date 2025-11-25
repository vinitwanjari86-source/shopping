<?php
session_start();
$conn = new mysqli("localhost", "root", "", "pacman");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch products from database
$products = $conn->query("SELECT * FROM products");

// Handle Add to Cart
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["add_to_cart"])) {
    $id = $_POST["product_id"];
    $name = $_POST["product_name"];
    $price = $_POST["product_price"];
    
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }
    $_SESSION['cart'][$id] = ["name" => $name, "price" => $price, "quantity" => 1];
}

// Handle Remove from Cart
if (isset($_GET["remove"])) {
    $id = $_GET["remove"];
    unset($_SESSION['cart'][$id]);
}

// Handle Order Placement
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["place_order"])) {
    $order_details = json_encode($_SESSION['cart']);
    $total_price = $_POST['total_price'];
    $conn->query("INSERT INTO orders (details, total_price) VALUES ('$order_details', '$total_price')");
    $_SESSION['cart'] = [];
    echo "<script>alert('Order placed successfully!');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart & Checkout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { background: #f8f9fa; }
        .card { border-radius: 10px; }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h2 class="text-center">Products</h2>
        <div class="row">
            <?php while ($row = $products->fetch_assoc()) { ?>
                <div class="col-md-4 mb-3">
                    <div class="card p-3">
                        <h5><?= $row["name"] ?></h5>
                        <p>Price: $<?= $row["price"] ?></p>
                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?= $row["id"] ?>">
                            <input type="hidden" name="product_name" value="<?= $row["name"] ?>">
                            <input type="hidden" name="product_price" value="<?= $row["price"] ?>">
                            <button type="submit" name="add_to_cart" class="btn btn-primary">Add to Cart</button>
                        </form>
                    </div>
                </div>
            <?php } ?>
        </div>

        <h2 class="text-center mt-4">Shopping Cart</h2>
        <table class="table">
            <thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Action</th></tr></thead>
            <tbody>
                <?php $total = 0; foreach ($_SESSION['cart'] ?? [] as $id => $item) { ?>
                    <tr>
                        <td><?= $item["name"] ?></td>
                        <td>$<?= $item["price"] ?></td>
                        <td><input type="number" value="<?= $item["quantity"] ?>" min="1" class="form-control w-50 quantity" data-id="<?= $id ?>"></td>
                        <td class="total-price" data-id="<?= $id ?>">$<?= $item["price"] ?></td>
                        <td><a href="?remove=<?= $id ?>" class="btn btn-danger">Remove</a></td>
                    </tr>
                <?php $total += $item["price"] * $item["quantity"]; } ?>
            </tbody>
        </table>
        <h3 class="text-end">Grand Total: $<span id="grand-total"><?= $total ?></span></h3>
        
        <form method="POST" class="text-end">
            <input type="hidden" name="total_price" id="total_price" value="<?= $total ?>">
            <button type="submit" name="place_order" class="btn btn-success">Place Order</button>
        </form>
    </div>

    <script>
        document.querySelectorAll(".quantity").forEach(input => {
            input.addEventListener("change", function() {
                let id = this.dataset.id;
                let price = parseFloat(document.querySelector(`[data-id='${id}'].total-price`).textContent.replace('$', '')) / this.value;
                document.querySelector(`[data-id='${id}'].total-price`).textContent = `$${(price * this.value).toFixed(2)}`;
                let total = [...document.querySelectorAll(".total-price")].reduce((acc, el) => acc + parseFloat(el.textContent.replace('$', '')), 0);
                document.getElementById("grand-total").textContent = total.toFixed(2);
                document.getElementById("total_price").value = total;
            });
        });
    </script>
</body>
</html>
