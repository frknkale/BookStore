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

if(isset($_SESSION['id'])){
	$servername = $mysql_host;
	$username = $mysql_username;
	$password = $mysql_password;

	$conn = new mysqli($servername, $username, $password); 

	if ($conn->connect_error) {
	    die("Connection failed: " . $conn->connect_error);
	} 

	$sql = "USE BookStore";
	$conn->query($sql);

	$sql = "SELECT CustomerID from Customer WHERE UserID = ".$_SESSION['id']."";
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()){
		$cID = $row['CustomerID'];
	}

	$sql = "UPDATE Cart SET CustomerID = ".$cID." WHERE 1";
	$conn->query($sql);

	$sql = "SELECT * FROM Cart";
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()){
		$sql = "INSERT INTO `Order`(CustomerID, BookID, DatePurchase, Quantity, TotalPrice, Status) 
		VALUES(".$row['CustomerID'].", '".$row['BookID']."', CURRENT_TIME, ".$row['Quantity'].", ".$row['TotalPrice'].", 'N')";
		if ($conn->query($sql) === FALSE) {
		    echo "Error: " . $conn->error;
		}
	}

	$sql = "DELETE FROM Cart";
	$conn->query($sql);

	$sql = "SELECT Customer.CustomerName, Customer.CustomerIC, Customer.CustomerGender, Customer.CustomerAddress, Customer.CustomerEmail, Customer.CustomerPhone, Book.BookTitle, Book.Price, Book.Image, `Order`.`DatePurchase`, `Order`.`Quantity`, `Order`.`TotalPrice`
		FROM Customer, Book, `Order`
		WHERE `Order`.`CustomerID` = Customer.CustomerID AND `Order`.`BookID` = Book.BookID AND `Order`.`CustomerID` = ".$cID."";
	$result = $conn->query($sql);
	echo '<div class="container">';
	echo '<blockquote>';
?>
<input class="button" style="float: right;" type="button" name="cancel" value="Continue Shopping" onClick="window.location='index.php';" />
<?php
	echo '<h2 style="color: #000;">Order Successful</h2>';
	echo "<table style='width:100%'>";
	echo "<tr><th>Order Summary</th>";
	echo "<th></th></tr>";
	$row = $result->fetch_assoc();
	echo "<tr><td>Name: </td><td>".$row['CustomerName']."</td></tr>";
	echo "<tr><td>No.Number: </td><td>".$row['CustomerIC']."</td></tr>";
	echo "<tr><td>E-mail: </td><td>".$row['CustomerEmail']."</td></tr>";
	echo "<tr><td>Mobile Number: </td><td>".$row['CustomerPhone']."</td></tr>";
	echo "<tr><td>Gender: </td><td>".$row['CustomerGender']."</td></tr>";
	echo "<tr><td>Address: </td><td>".$row['CustomerAddress']."</td></tr>";
	echo "<tr><td>Date: </td><td>".$row['DatePurchase']."</td></tr>";
	echo "</blockquote>";

	$sql = "SELECT Customer.CustomerName, Customer.CustomerIC, Customer.CustomerGender, Customer.CustomerAddress, Customer.CustomerEmail, Customer.CustomerPhone, Book.BookTitle, Book.Price, Book.Image, `Order`.`DatePurchase`, `Order`.`Quantity`, `Order`.`TotalPrice`
		FROM Customer, Book, `Order`
		WHERE `Order`.`CustomerID` = Customer.CustomerID AND `Order`.`BookID` = Book.BookID AND `Order`.`CustomerID` = ".$cID."";
	$result = $conn->query($sql);
	$total = 0;
	while($row = $result->fetch_assoc()){
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

	$sql = "UPDATE `Order` SET Status = 'y' WHERE CustomerID = ".$cID."";
	$conn->query($sql);
}

$nameErr = $emailErr = $genderErr = $addressErr = $icErr = $contactErr = "";
$name = $email = $gender = $address = $ic = $contact = "";
$cID;

if(isset($_POST['submitButton'])){
	if (empty($_POST["name"])) {
		$nameErr = "Please enter your name";
	}else{
		$name = $_POST['name'];
		if (empty($_POST["ic"])){
			$icErr = "Please enter your IC number";
		}else{
			$ic = $_POST['ic'];
			if (empty($_POST["email"])){
				$emailErr = "Please enter your email address";
			}else{
				$email = $_POST['email'];
				if (empty($_POST["contact"])){
					$contactErr = "Please enter your phone number";
				}else{
					$contact = $_POST['contact'];
					if (empty($_POST["gender"])){
						$genderErr = "Gender is required!";
					}else{
						$gender = $_POST['gender'];
						if (empty($_POST["address"])){
							$addressErr = "Please enter your address";
						}else{
							$address = $_POST['address'];

							$servername = $mysql_host;
							$username = $mysql_username;
							$password = $mysql_password;

							$conn = new mysqli($servername, $username, $password); 

							if ($conn->connect_error) {
							    die("Connection failed: " . $conn->connect_error);
							} 

							$sql = "USE BookStore";
							$conn->query($sql);

							$sql = "INSERT INTO Customer(CustomerName, CustomerPhone, CustomerIC, CustomerEmail, CustomerAddress, CustomerGender) 
							VALUES('".$name."', '".$contact."', '".$ic."', '".$email."', '".$address."', '".$gender."')";
							$conn->query($sql);

							$sql = "SELECT CustomerID from Customer WHERE CustomerName = '".$name."' AND CustomerIC = '".$ic."'";
							$result = $conn->query($sql);
							while($row = $result->fetch_assoc()){
								$cID = $row['CustomerID'];
							}

							$sql = "UPDATE Cart SET CustomerID = ".$cID." WHERE 1";
							$conn->query($sql);

							$sql = "SELECT * FROM Cart";
							$result = $conn->query($sql);
							while($row = $result->fetch_assoc()){
								$sql = "INSERT INTO `Order`(CustomerID, BookID, DatePurchase, Quantity, TotalPrice, Status) 
								VALUES(".$row['CustomerID'].", '".$row['BookID']."', CURRENT_TIME, ".$row['Quantity'].", ".$row['TotalPrice'].", 'N')";
								$conn->query($sql);
							}
							$sql = "DELETE FROM Cart";
							$conn->query($sql);
						}
					}
				}
			}
		}
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
    font-size: 1.2em;
	width: 60%;
}
input[type=text]:focus {
    border: 2px solid #6699ff;
}
input[type=button] {
	width: 100%;
}
button {
	width: 100%;
}
button:hover{
	color: #fff;
	background-color: rgb(0,51,102);
	border: none;
	border-radius: 8px;
}
input[type=button] {
	background-color: rgb(0,51,102);
    color: #fff;
    border-radius: 8px;
}
input[type=button]:hover{
	background-color: rgb(0,102,204);
}
input[type="radio"] {
	width: 20px;
	height: 20px;
	cursor: pointer;
}
input[type="radio"]:focus {
	outline: none;
}
h2 {
	text-align: center;
	color: rgb(0,51,102);
}
blockquote {
	margin: 2%;
	padding: 3%;
}
table, th, td {
	border: 1px solid black;
	border-collapse: collapse;
}
th, td {
	padding: 10px;
	text-align: left;
}
</style>
</body>
</html>
