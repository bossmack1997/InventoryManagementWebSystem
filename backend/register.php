<?php
include __DIR__ . '/../db_connect.php';


$company  = $_POST['company'] ?? '';
$username = $_POST['username'] ?? '';
$email    = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (!$company || !$username || !$email || !$password) {
    echo "Please fill in all fields.";
    exit;
}

// Check duplicate username
$stmt = $conn->prepare("SELECT * FROM users WHERE username=?");
$stmt->bind_param("s", $username);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    echo "Username already exists!";
    exit;
}

// Insert new user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (company_name, username, email, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $company, $username, $email, $hashedPassword);

if ($stmt->execute()) {
    echo "success";
} else {
    echo "Error: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>
