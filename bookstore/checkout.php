<html>
<body style="font-family:Arial; margin: 0 auto; background-color: #f2f2f2;">
<header>
<blockquote>
	<img src="image/logo.png">
	<input class="hi" style="float: right; margin: 2%;" type="button" name="cancel" value="Home" onClick="window.location='index.php';" />
</blockquote>
</header>
<?php
include 'config.php';
session_start();

// Initialize variables
$nameErr = $emailErr = $genderErr = $addressErr = $icErr = $contactErr = "";
$name = $email = $gender = $address = $ic = $contact = "";
$cID = null;
$orderProcessed = false;

// Function to sanitize input
function test_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

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

// Function to process order for logged-in user
function processLoggedInUserOrder($conn, $userId) {
    // Get customer ID from session user ID
    $stmt = $conn->prepare("SELECT CustomerID FROM Customer WHERE UserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $cID = $row['CustomerID'];
        
        // Create orders from cart items belonging to this customer
        $stmt = $conn->prepare("SELECT * FROM Cart WHERE CustomerID = ?");
        $stmt->bind_param("i", $cID);
        $stmt->execute();
        $cartResult = $stmt->get_result();
        
        if ($cartResult->num_rows > 0) {
            while($cartRow = $cartResult->fetch_assoc()) {
                $stmt2 = $conn->prepare("INSERT INTO `Order`(CustomerID, BookID, DatePurchase, Quantity, TotalPrice, Status) VALUES(?, ?, NOW(), ?, ?, 'N')");
                $stmt2->bind_param("isid", $cID, $cartRow['BookID'], $cartRow['Quantity'], $cartRow['TotalPrice']);
                $stmt2->execute();
            }
            
            // Clear cart for this customer
            $stmt = $conn->prepare("DELETE FROM Cart WHERE CustomerID = ?");
            $stmt->bind_param("i", $cID);
            $stmt->execute();
            
            return $cID;
        }
    }
    return null;
}

// Function to display order summary
function displayOrderSummary($conn, $cID) {
    $stmt = $conn->prepare("SELECT Customer.CustomerName, Customer.CustomerIC, Customer.CustomerGender, Customer.CustomerAddress, Customer.CustomerEmail, Customer.CustomerPhone, Book.BookTitle, Book.Price, Book.Image, `Order`.DatePurchase, `Order`.Quantity, `Order`.TotalPrice FROM Customer, Book, `Order` WHERE `Order`.CustomerID = Customer.CustomerID AND `Order`.BookID = Book.BookID AND `Order`.Status = 'N' AND `Order`.CustomerID = ?");
    $stmt->bind_param("i", $cID);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo '<div class="container">';
        echo '<blockquote>';
        echo '<input class="button" style="float: right;" type="button" name="cancel" value="Continue Shopping" onClick="window.location=\'index.php\';" />';
        echo '<h2 style="color: #000;">Order Successful</h2>';
        echo "<table style='width:100%'>";
        echo "<tr><th>Order Summary</th><th></th></tr>";
        
        $row = $result->fetch_assoc();
        echo "<tr><td>Name: </td><td>".$row['CustomerName']."</td></tr>";
        echo "<tr><td>IC Number: </td><td>".$row['CustomerIC']."</td></tr>";
        echo "<tr><td>E-mail: </td><td>".$row['CustomerEmail']."</td></tr>";
        echo "<tr><td>Mobile Number: </td><td>".$row['CustomerPhone']."</td></tr>";
        echo "<tr><td>Gender: </td><td>".$row['CustomerGender']."</td></tr>";
        echo "<tr><td>Address: </td><td>".$row['CustomerAddress']."</td></tr>";
        echo "<tr><td>Date: </td><td>".$row['DatePurchase']."</td></tr>";
        
        // Reset result pointer
        $stmt->execute();
        $result = $stmt->get_result();
        
        $total = 0;
        while($row = $result->fetch_assoc()) {
            echo "<tr><td style='border-top: 2px solid #ccc;'>";
            echo '<img src="'.$row["Image"].'" width="20%"></td><td style="border-top: 2px solid #ccc;">';
            echo $row['BookTitle']."<br>RM".$row['Price']."<br>";
            echo "Quantity: ".$row['Quantity']."<br>";
            echo "</td></tr>";
            $total += $row['TotalPrice'];
        }
        echo "<tr><td style='background-color: #ccc;'></td><td style='text-align: right;background-color: #ccc;'>Total Price: <b>RM".$total."</b></td></tr>";
        echo "</table>";
        echo "</div>";
        
        // Update order status to completed
        $stmt = $conn->prepare("UPDATE `Order` SET Status = 'Y' WHERE CustomerID = ?");
        $stmt->bind_param("i", $cID);
        $stmt->execute();
    }
}

// If user is logged in, process order immediately
if(isset($_SESSION['id'])) {
    $conn = getConnection();
    $cID = processLoggedInUserOrder($conn, $_SESSION['id']);
    if ($cID) {
        displayOrderSummary($conn, $cID);
        $orderProcessed = true;
    }
    $conn->close();
}

// Process form submission for guest checkout
if(isset($_POST['submitButton']) && !$orderProcessed) {
    $valid = true;
    
    // Validate name
    if (empty($_POST["name"])) {
        $nameErr = "Please enter your name";
        $valid = false;
    } else {
        $name = test_input($_POST["name"]);
        if (!preg_match("/^[a-zA-Z ]*$/", $name)) {
            $nameErr = "Only letters and white space allowed";
            $valid = false;
        }
    }
    
    // Validate IC
    if (empty($_POST["ic"])) {
        $icErr = "Please enter your IC number";
        $valid = false;
    } else {
        $ic = test_input($_POST["ic"]);
		/*
        if (!preg_match("/^[0-9-]*$/", $ic)) {
            $icErr = "Please enter a valid IC number";
            $valid = false;
        }
		*/
    }
    
    // Validate email
    if (empty($_POST["email"])) {
        $emailErr = "Please enter your email address";
        $valid = false;
    } else {
        $email = test_input($_POST["email"]);
		/*
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
            $valid = false;
        }
		*/
    }
    
    // Validate contact
    if (empty($_POST["contact"])) {
        $contactErr = "Please enter your phone number";
        $valid = false;
    } else {
        $contact = test_input($_POST["contact"]);
		/*
        if (!preg_match("/^[0-9-]*$/", $contact)) {
            $contactErr = "Please enter a valid phone number";
            $valid = false;
        }
		*/
    }
    
    // Validate gender
    if (empty($_POST["gender"])) {
        $genderErr = "Gender is required";
        $valid = false;
    } else {
        $gender = test_input($_POST["gender"]);
    }
    
    // Validate address
    if (empty($_POST["address"])) {
        $addressErr = "Please enter your address";
        $valid = false;
    } else {
        $address = test_input($_POST["address"]);
    }
    
    // If all validations pass, process the order
    if ($valid) {
        $conn = getConnection();
        
        // Insert customer
        $stmt = $conn->prepare("INSERT INTO Customer(CustomerName, CustomerPhone, CustomerIC, CustomerEmail, CustomerAddress, CustomerGender) VALUES(?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $name, $contact, $ic, $email, $address, $gender);
        $stmt->execute();
        
        // Get the newly created customer ID
        $cID = $conn->insert_id;
        
        // Create orders from cart items (guest cart items have CustomerID = NULL)
        $stmt = $conn->prepare("SELECT * FROM Cart WHERE CustomerID IS NULL");
        $stmt->execute();
        $cartResult = $stmt->get_result();
        
        if ($cartResult->num_rows > 0) {
            while($cartRow = $cartResult->fetch_assoc()) {
                $stmt2 = $conn->prepare("INSERT INTO `Order`(CustomerID, BookID, DatePurchase, Quantity, TotalPrice, Status) VALUES(?, ?, NOW(), ?, ?, 'N')");
                $stmt2->bind_param("isid", $cID, $cartRow['BookID'], $cartRow['Quantity'], $cartRow['TotalPrice']);
                $stmt2->execute();
            }
            
            // Clear guest cart
            $stmt = $conn->prepare("DELETE FROM Cart WHERE CustomerID IS NULL");
            $stmt->execute();
            
            // Display order summary
            displayOrderSummary($conn, $cID);
            $orderProcessed = true;
        }
        
        $conn->close();
    }
}
?>

<style> 
header {
	background-color: rgb(0,51,102);
	width: 100%;
}
header img {
	margin: 1%;
}
header .hi{
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
header .hi:hover{
    background-color: #ccc;
}
form{
	margin-top: 1%;
	float: left;
	width: 40%;
	color: #000;
}
input[type=text] {
	padding: 5px;
    border-radius: 3px;
    box-sizing: border-box;
    border: 2px solid #ccc;
    transition: 0.5s;
    outline: none;
}
input[type=text]:focus {
    border: 2px solid rgb(0,51,102);
}
textarea {
	outline: none;
	border: 2px solid #ccc;
}
textarea:focus {
	border: 2px solid rgb(0,51,102);
}
.button{
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
    width: 60%;
    float: right;
}
th, td {
    text-align: left;
    padding: 8px;
}
tr{background-color: #fff;}

th {
    background-color: rgb(0,51,102);
    color: white;
}
.container {
	width: 50%;
    border-radius: 5px;
    background-color: #f2f2f2;
    padding: 20px;
    margin: 0 auto;
}
.error {
    color: red; 
    font-size: 0.8em;
}
</style>

<blockquote>
<?php
// Show checkout form only if order hasn't been processed and user is not logged in
if(!$orderProcessed && !isset($_SESSION['id'])) {
	echo "<form method='post' action=''>";

	echo 'Name:<br><input type="text" name="name" placeholder="Full Name" value="'.$name.'">';
	echo '<span class="error">'.$nameErr.'</span><br><br>';

	echo 'IC Number:<br><input type="text" name="ic" placeholder="xxxxxx-xx-xxxx" value="'.$ic.'">';
	echo '<span class="error">'.$icErr.'</span><br><br>';

	echo 'E-mail:<br><input type="text" name="email" placeholder="example@email.com" value="'.$email.'">';
	echo '<span class="error">'.$emailErr.'</span><br><br>';

	echo 'Mobile Number:<br><input type="text" name="contact" placeholder="012-3456789" value="'.$contact.'">';
	echo '<span class="error">'.$contactErr.'</span><br><br>';

	echo '<label>Gender:</label><br>';
	echo '<input type="radio" name="gender" value="Male"'.($gender == "Male" ? " checked" : "").'>Male ';
	echo '<input type="radio" name="gender" value="Female"'.($gender == "Female" ? " checked" : "").'>Female<br>';
	echo '<span class="error">'.$genderErr.'</span><br><br>';

	echo '<label>Address:</label><br>';
	echo '<textarea name="address" cols="30" rows="5" placeholder="Address">'.$address.'</textarea><br>';
	echo '<span class="error">'.$addressErr.'</span><br><br>';

	echo '<input class="button" type="button" name="cancel" value="Cancel" onClick="window.location=\'index.php\';" />';
	echo '<input class="button" type="submit" name="submitButton" value="CHECKOUT">';
	echo '</form><br><br>';
}
?>
</blockquote>
</body>
</html>