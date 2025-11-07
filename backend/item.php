<?php
header('Content-Type: application/json');

// Convert PHP errors/warnings to JSON
set_error_handler(function($severity, $message, $file, $line) {
    echo json_encode(['success'=>false, 'message'=>"PHP Error: $message"]);
    exit;
});
set_exception_handler(function($e) {
    echo json_encode(['success'=>false, 'message'=>"Exception: ".$e->getMessage()]);
    exit;
});

include __DIR__ . '/../db_connect.php';


// Get the HTTP method
$method = $_SERVER['REQUEST_METHOD'];

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Function to safely cast numbers
function castNumber($value, $type = 'int') {
    return $type === 'float' ? (float)$value : (int)$value;
}

// ------------------------
// POST - Add new item
// ------------------------
if($method === 'POST'){
    $item_name   = trim($data['item_name'] ?? '');
    $quantity    = castNumber($data['quantity'] ?? 0);
    $price       = castNumber($data['price'] ?? 0, 'float');

    if(empty($item_name) || $quantity <= 0 || $price < 0){
        echo json_encode(['success'=>false,'message'=>'Invalid input']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO items (item_name, quantity, price) VALUES (?, ?, ?)");
    if(!$stmt){
        echo json_encode(['success'=>false,'message'=>'Prepare failed: '.$conn->error]);
        exit;
    }

    $stmt->bind_param("sid", $item_name, $quantity, $price);

    if($stmt->execute()){
        echo json_encode(['success'=>true,'message'=>'Item added successfully']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Database error: '.$stmt->error]);
    }
    exit;
}

// ------------------------
// GET - Fetch all items
// ------------------------
if($method === 'GET'){
    $result = $conn->query("SELECT * FROM items ORDER BY id DESC");
    if(!$result){
        echo json_encode(['success'=>false,'message'=>'Database error: '.$conn->error]);
        exit;
    }
    $items = [];
    while($row = $result->fetch_assoc()){
        $items[] = $row;
    }
    echo json_encode($items);
    exit;
}

// ------------------------
// PUT - Update item
// ------------------------
if($method === 'PUT'){
    $id          = castNumber($data['id'] ?? 0);
    $item_name   = trim($data['item_name'] ?? '');
    $description = trim($data['description'] ?? '');
    $quantity    = castNumber($data['quantity'] ?? 0);
    $price       = castNumber($data['price'] ?? 0, 'float');

    if(!$id || empty($item_name) || $quantity <= 0 || $price < 0){
        echo json_encode(['success'=>false,'message'=>'Invalid input']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE items SET item_name=?, description=?, quantity=?, price=? WHERE id=?");
    if(!$stmt){
        echo json_encode(['success'=>false,'message'=>'Prepare failed: '.$conn->error]);
        exit;
    }

    $stmt->bind_param("ssidi", $item_name, $description, $quantity, $price, $id);

    if($stmt->execute()){
        echo json_encode(['success'=>true,'message'=>'Item updated successfully']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Database error: '.$stmt->error]);
    }
    exit;
}

// ------------------------
// DELETE - Remove item
// ------------------------
if($method === 'DELETE'){
    $id = castNumber($data['id'] ?? 0);
    if(!$id){
        echo json_encode(['success'=>false,'message'=>'Invalid ID']);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM items WHERE id=?");
    if(!$stmt){
        echo json_encode(['success'=>false,'message'=>'Prepare failed: '.$conn->error]);
        exit;
    }

    $stmt->bind_param("i", $id);

    if($stmt->execute()){
        echo json_encode(['success'=>true,'message'=>'Item deleted successfully']);
    } else {
        echo json_encode(['success'=>false,'message'=>'Database error: '.$stmt->error]);
    }
    exit;
}

// ------------------------
// Default response for unsupported methods
// ------------------------
echo json_encode(['success'=>false,'message'=>'Invalid request method']);
exit;
?>
