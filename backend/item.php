<?php
session_start();
header('Content-Type: application/json');

// =============================
// AUTH CHECK
// =============================
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// =============================
// ERROR HANDLING
// =============================
set_error_handler(function($severity, $message, $file, $line) {
    echo json_encode(['success'=>false, 'message'=>"PHP Error: $message"]);
    exit;
});
set_exception_handler(function($e) {
    echo json_encode(['success'=>false, 'message'=>"Exception: ".$e->getMessage()]);
    exit;
});

include __DIR__ . '/../db_connect.php';

// =============================
// HELPERS
// =============================
function castNumber($value, $type = 'int') {
    return $type === 'float' ? (float)$value : (int)$value;
}

$method  = $_SERVER['REQUEST_METHOD'];
$data    = json_decode(file_get_contents("php://input"), true);
$user_id = $_SESSION['user_id'];

// =============================
// POST - Add new item
// =============================
if ($method === 'POST') {
    $item_name = trim($data['item_name'] ?? '');
    $quantity  = castNumber($data['quantity'] ?? 0);
    $price     = castNumber($data['price'] ?? 0, 'float');

    if (empty($item_name) || $quantity <= 0 || $price < 0) {
        echo json_encode(['success'=>false,'message'=>'Invalid input']);
        exit;
    }

    $stmt = $conn->prepare("
        INSERT INTO items (item_name, quantity, price, date_added, user_id)
        VALUES (?, ?, ?, NOW(), ?)
    ");
    if (!$stmt) {
        echo json_encode(['success'=>false,'message'=>'Prepare failed: '.$conn->error]);
        exit;
    }

    $stmt->bind_param("sidi", $item_name, $quantity, $price, $user_id);
    $stmt->execute();

    echo json_encode(['success'=>true,'message'=>'Item added successfully']);
    exit;
}

// =============================
// GET - Fetch all items for current user
// =============================
if ($method === 'GET') {
    $stmt = $conn->prepare("
        SELECT id, item_name, quantity, price, date_added 
        FROM items 
        WHERE user_id = ? 
        ORDER BY id DESC
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }

    // ✅ Return only an array (fixes items.filter issue)
    echo json_encode($items);
    exit;
}

// =============================
// PUT - Update item
// =============================
if ($method === 'PUT') {
    $id        = castNumber($data['id'] ?? 0);
    $item_name = trim($data['item_name'] ?? '');
    $quantity  = castNumber($data['quantity'] ?? 0);
    $price     = castNumber($data['price'] ?? 0, 'float');

    if (!$id || empty($item_name) || $quantity <= 0 || $price < 0) {
        echo json_encode(['success'=>false,'message'=>'Invalid input']);
        exit;
    }

    // Ensure item belongs to the logged-in user
    $check = $conn->prepare("SELECT id FROM items WHERE id=? AND user_id=?");
    $check->bind_param("ii", $id, $user_id);
    $check->execute();
    $res = $check->get_result();
    if ($res->num_rows === 0) {
        echo json_encode(['success'=>false,'message'=>'Access denied or item not found']);
        exit;
    }

    $stmt = $conn->prepare("
        UPDATE items 
        SET item_name=?, quantity=?, price=? 
        WHERE id=? AND user_id=?
    ");
    $stmt->bind_param("sidii", $item_name, $quantity, $price, $id, $user_id);
    $stmt->execute();

    echo json_encode(['success'=>true,'message'=>'Item updated successfully']);
    exit;
}

// =============================
// DELETE - Remove item
// =============================
if ($method === 'DELETE') {
    $id = castNumber($data['id'] ?? 0);
    if (!$id) {
        echo json_encode(['success'=>false,'message'=>'Invalid ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM items WHERE id=? AND user_id=?");
    $stmt->bind_param("ii", $id, $user_id);
    $stmt->execute();

    echo json_encode(['success'=>true,'message'=>'Item deleted successfully']);
    exit;
}

// =============================
// DEFAULT - Invalid Method
// =============================
echo json_encode(['success'=>false,'message'=>'Invalid request method']);
exit;
?>
