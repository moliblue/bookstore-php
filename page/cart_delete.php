<?php if(session_status() === PHP_SESSION_NONE){
    session_start();
}

// check if user already log in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo 'Please login to manage your cart.';
    exit;
}

require __DIR__ . '/../sb_base.php';
require __DIR__ . '/cart.php'; // load cart_user_id()

if(!isset($_GET['product_id'])){
    exit('no product id');
}

$userId = cart_user_id();
$productId = filter_var($_GET['product_id'],FILTER_VALIDATE_INT);



try{
$stmt = $pdo->prepare("DELETE FROM cart_items WHERE user_id = ? AND product_id = ?");
$stmt->execute([$userId,$productId]);

echo "deleted";
}catch(PDOException $e){
    echo "failed to deleted";
}


?>