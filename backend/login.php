<?php
session_start();
include __DIR__ . '/../db_connect.php';

$company  = trim($_POST['company'] ?? '');
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (!$company || !$username || !$password) {
    echo "Please fill in all fields.";
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT id, company_name, username, password FROM users WHERE company_name=? AND username=?");
$stmt->bind_param("ss", $company, $username);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $row = $result->fetch_assoc();
    if (password_verify($password, $row['password'])) {
        $_SESSION['username'] = $row['username'];
        $_SESSION['company_name'] = $row['company_name'];
        $_SESSION['user_id'] = $row['id'];
        echo "success";
    } else echo "Invalid password!";
} else echo "User not found!";

$stmt->close();
$conn->close();
?>
