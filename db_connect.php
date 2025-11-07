<?php
$servername = "localhost";
$username   = "root";
$password   = ""; // default XAMPP password
$dbname     = "inventory_db";

$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success'=>false,'message'=>'Database Connection Failed: '.$conn->connect_error]));
}
?>
