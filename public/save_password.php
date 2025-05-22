<?php 

session_start();
require_once("../config/db.php");
require_once("../classes/PasswordStorage.php");
require_once "../classes/User.php";

// Make sure user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['aes_key'])) {
    die("You must be logged in.");
}

// Step 2: Initialize DB and PasswordStorage
$db = (new Database())->getConnection();
$storage = new PasswordStorage($db);

// Step 3: Get form data and save password
$user_id = $_SESSION['user_id'];
$service = $_POST['service_name'];
$password = $_POST['password'];
$key = $_SESSION['aes_key'];

$success = $storage->save($user_id, $service, $password, $key);

if ($success) {
    echo "Password saved successfully.<br><a href='dashboard.php'>Back to dashboard</a>";
} else {
    echo "Failed to save password.<br><a href='dashboard.php'>Back to dashboard</a>";
}

?>


