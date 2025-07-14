<htm>
<meta http-equiv="Content-Type" content="text/html; charset=utf8"/>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
<link rel="stylesheet" href="style.css">
<body>
<?php
include 'config.php';
session_start();

// Function to create database connection
function getConnection() {
    global $mysql_host, $mysql_username, $mysql_password;
    $conn = new mysqli($mysql_host, $mysql_username, $mysql_password);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    $conn->query("USE BookStore");
    return $conn;
}

// Handle add to cart
if(isset($_POST['ac'])) {
    $conn = getConnection();
    
    $bookID = $_POST['ac'];
    $quantity = intval($_POST['quantity']);
    
    // Get customer ID if logged in
    $customerID = null;
    if(isset($_SESSION['id'])) {
        $stmt = $conn->prepare("SELECT CustomerID FROM Customer WHERE UserID = ?");
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $customerID = $row['CustomerID'];
        }
    }
    
    // Get book details
    $stmt = $conn->prepare("SELECT * FROM Book WHERE BookID = ?");
    $stmt->bind_param("s", $bookID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $price = $row['Price'];
        $totalPrice = $price * $quantity;
        
        // Check if item already exists in cart
        if($customerID) {
            $stmt = $conn->prepare("SELECT * FROM Cart WHERE BookID = ? AND CustomerID = ?");
            $stmt->bind_param("si", $bookID, $customerID);
        } else {
            $stmt = $conn->prepare("SELECT * FROM Cart WHERE BookID = ? AND CustomerID IS NULL");
            $stmt->bind_param("s", $bookID);
        }
        $stmt->execute();
        $cartResult = $stmt->get_result();
        
        if($cartResult->num_rows > 0) {
            // Update existing cart item
            if($customerID) {
                $stmt = $conn->prepare("UPDATE Cart SET Quantity = Quantity + ?, TotalPrice = Price * (Quantity + ?) WHERE BookID = ? AND CustomerID = ?");
                $stmt->bind_param("iisi", $quantity, $quantity, $bookID, $customerID);
            } else {
                $stmt = $conn->prepare("UPDATE Cart SET Quantity = Quantity + ?, TotalPrice = Price * (Quantity + ?) WHERE BookID = ? AND CustomerID IS NULL");
                $stmt->bind_param("iis", $quantity, $quantity, $bookID);
            }
            $stmt->execute();
        } else {
            // Insert new cart item
            if($customerID) {
                $stmt = $conn->prepare("INSERT INTO Cart(BookID, Quantity, Price, TotalPrice, CustomerID) VALUES(?, ?, ?, ?, ?)");
                $stmt->bind_param("siddi", $bookID, $quantity, $price, $totalPrice, $customerID);
            } else {
                $stmt = $conn->prepare("INSERT INTO Cart(BookID, Quantity, Price, TotalPrice, CustomerID) VALUES(?, ?, ?, ?, NULL)");
                $stmt->bind_param("sidd", $bookID, $quantity, $price, $totalPrice);
            }
            $stmt->execute();
        }
    }
    $conn->close();
}

// Handle empty cart
if(isset($_POST['delc'])) {
    $conn = getConnection();
    
    // Get customer ID if logged in
    $customerID = null;
    if(isset($_SESSION['id'])) {
        $stmt = $conn->prepare("SELECT CustomerID FROM Customer WHERE UserID = ?");
        $stmt->bind_param("i", $_SESSION['id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $customerID = $row['CustomerID'];
        }
    }
    
    // Delete cart items
    if($customerID) {
        $stmt = $conn->prepare("DELETE FROM Cart WHERE CustomerID = ?");
        $stmt->bind_param("i", $customerID);
    } else {
        $stmt = $conn->prepare("DELETE FROM Cart WHERE CustomerID IS NULL");
    }
    $stmt->execute();
    $conn->close();
}

// Get books for display
$conn = getConnection();
$stmt = $conn->prepare("SELECT * FROM Book");
$stmt->execute();
$result = $stmt->get_result();
?>	

<?php
// Display header based on login status
if(isset($_SESSION['id'])) {
    echo '<header>';
    echo '<blockquote>';
    echo '<a href="index.php"><img src="image/logo.png"></a>';
    echo '<form class="hf" action="logout.php"><input class="hi" type="submit" name="submitButton" value="Logout"></form>';
    echo '<form class="hf" action="edituser.php"><input class="hi" type="submit" name="submitButton" value="Edit Profile"></form>';
    echo '</blockquote>';
    echo '</header>';
} else {
    echo '<header>';
    echo '<blockquote>';
    echo '<a href="index.php"><img src="image/logo.png"></a>';
    echo '<form class="hf" action="register.php"><input class="hi" type="submit" name="submitButton" value="Register"></form>';
    echo '<form class="hf" action="login.php"><input class="hi" type="submit" name="submitButton" value="Login"></form>';
    echo '</blockquote>';
    echo '</header>';
}

echo '<blockquote>';
echo "<table id='myTable' style='width:80%; float:left'>";
echo "<tr>";

// Display books
$count = 0;
while($row = $result->fetch_assoc()) {
    if($count % 3 == 0 && $count != 0) {
        echo "</tr><tr>";
    }
    
    echo "<td style='vertical-align: top; padding: 10px;'>";
    echo "<table style='border: 1px solid #ddd; padding: 10px; margin: 5px;'>";
    echo '<tr><td><img src="'.$row["Image"].'" width="100%" style="max-width: 200px;"></td></tr>';
    echo '<tr><td style="padding: 5px;"><strong>Title:</strong> '.$row["BookTitle"].'</td></tr>';
    echo '<tr><td style="padding: 5px;"><strong>ISBN:</strong> '.$row["ISBN"].'</td></tr>';
    echo '<tr><td style="padding: 5px;"><strong>Author:</strong> '.$row["Author"].'</td></tr>';
    echo '<tr><td style="padding: 5px;"><strong>Type:</strong> '.$row["Type"].'</td></tr>';
    echo '<tr><td style="padding: 5px;"><strong>Price:</strong> RM'.$row["Price"].'</td></tr>';
    echo '<tr><td style="padding: 5px;">';
    echo '<form action="" method="post">';
    echo 'Quantity: <input type="number" value="1" min="1" name="quantity" style="width: 60px;"/><br><br>';
    echo '<input type="hidden" value="'.$row['BookID'].'" name="ac"/>';
    echo '<input class="button" type="submit" value="Add to Cart"/>';
    echo '</form></td></tr>';
    echo "</table>";
    echo "</td>";
    
    $count++;
}

echo "</tr>";
echo "</table>";

// Display cart
$customerID = null;
if(isset($_SESSION['id'])) {
    $stmt = $conn->prepare("SELECT CustomerID FROM Customer WHERE UserID = ?");
    $stmt->bind_param("i", $_SESSION['id']);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $customerID = $row['CustomerID'];
    }
}

// Get cart items based on customer ID
if($customerID) {
    $stmt = $conn->prepare("SELECT Book.BookTitle, Book.Image, Cart.Price, Cart.Quantity, Cart.TotalPrice FROM Book, Cart WHERE Book.BookID = Cart.BookID AND Cart.CustomerID = ?");
    $stmt->bind_param("i", $customerID);
} else {
    $stmt = $conn->prepare("SELECT Book.BookTitle, Book.Image, Cart.Price, Cart.Quantity, Cart.TotalPrice FROM Book, Cart WHERE Book.BookID = Cart.BookID AND Cart.CustomerID IS NULL");
}
$stmt->execute();
$cartResult = $stmt->get_result();

echo "<table style='width:20%; float:right; border: 1px solid #ddd;'>";
echo "<tr><th style='text-align:left; padding: 10px;'><i class='fa fa-shopping-cart' style='font-size:24px'></i> Cart ";
echo "<form style='float:right; display: inline;' action='' method='post'>";
echo "<input type='hidden' name='delc'/>";
echo "<input class='cbtn' type='submit' value='Empty Cart' style='background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; font-size: 12px;'>";
echo "</form></th></tr>";

$total = 0;
$hasItems = false;

while($row = $cartResult->fetch_assoc()) {
    $hasItems = true;
    echo "<tr><td style='padding: 10px; border-bottom: 1px solid #eee;'>";
    echo '<img src="'.$row["Image"].'" width="50px" style="float: left; margin-right: 10px;"><br>';
    echo '<div style="margin-left: 60px;">';
    echo "<strong>".$row['BookTitle']."</strong><br>";
    echo "RM".$row['Price']."<br>";
    echo "Qty: ".$row['Quantity']."<br>";
    echo "<strong>Total: RM".$row['TotalPrice']."</strong>";
    echo '</div>';
    echo "<div style='clear: both;'></div>";
    echo "</td></tr>";
    $total += $row['TotalPrice'];
}

if(!$hasItems) {
    echo "<tr><td style='padding: 20px; text-align: center; color: #666;'>Your cart is empty</td></tr>";
}

echo "<tr><td style='text-align: center; background-color: #f8f9fa; padding: 15px;'>";
echo "<strong>Total: RM".number_format($total, 2)."</strong><br><br>";
if($hasItems) {
    echo "<form action='checkout.php' method='post'>";
    echo "<input class='button' type='submit' name='checkout' value='CHECKOUT' style='width: 100%; padding: 10px;'>";
    echo "</form>";
}
echo "</td></tr>";
echo "</table>";
echo '</blockquote>';

$conn->close();
?>

<style>
header {
    background-color: rgb(0,51,102);
    width: 100%;
}
header img {
    margin: 1%;
}
header .hi {
    background-color: #fff;
    border: none;
    border-radius: 20px;
    text-align: center;
    transition-duration: 0.5s; 
    padding: 8px 30px;
    cursor: pointer;
    color: #000;
    margin-top: 15%;
}
header .hi:hover {
    background-color: #ccc;
}
.hf {
    float: right;
    margin: 1%;
}
.button {
    background-color: rgb(0,51,102);
    border: none;
    border-radius: 20px;
    text-align: center;
    transition-duration: 0.5s; 
    padding: 8px 30px;
    cursor: pointer;
    color: #fff;
}
.button:hover {
    background-color: rgb(102,255,255);
    color: #000;
}
table {
    border-collapse: collapse;
}
th, td {
    text-align: left;
    padding: 8px;
}
tr {
    background-color: #fff;
}
th {
    background-color: rgb(0,51,102);
    color: white;
}
body {
    font-family: Arial, sans-serif;
    margin: 0;
    background-color: #f2f2f2;
}
blockquote {
    margin: 0;
    padding: 20px;
}
</style>

</body>
</html>