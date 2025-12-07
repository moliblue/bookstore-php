<?php



if (session_status() === PHP_SESSION_NONE) {
    session_start();
}


require __DIR__ . '/../sb_base.php';     // ← 回到 online shop 目录
require_once __DIR__ . '/product_functions.php';

const CART_GUEST_SESSION_KEY = 'cart_guest_user_id';



//CONNECT THE CART ID TO THE USER ID
function cart_user_id():int
{
    //IF USER ID IS EXIST CONNECT TO THE EXISTING ID
    if(!empty($_SESSION['user_id'])){
        return (int) $_SESSION['user_id'];
    }
//if guest (NO USER ID) generate random session id
    if(!isset($_SESSION[CART_GUEST_SESSION_KEY])){
        $_SESSION[CART_GUEST_SESSION_KEY] = random_int(1000000, 9999999);
    }
    return (int) $_SESSION[CART_GUEST_SESSION_KEY];
}

//normalize row
function cart_normalize_row(array $row): array
{
    return[
        //isset($row['id']) ? $row['id'] : 0
        'id' => (int) ($row['id']??0),
        'product_id' => (int) $row['product_id'],
        'qty' => (int) $row['quantity'],
        'quantity' => (int) $row['quantity'],
        'created_at' => $row['created_at'],
        'updated_at' => $row['updated_at'],
    ];
}

//get all cart items
function cart_contents():array{
    global $pdo;
    $userId = cart_user_id();

    $stmt = $pdo->prepare("SELECT id, product_id,quantity,created_at,updated_at 
                                    FROM cart_items 
                                    WHERE user_id = ? ORDER BY id");
    $stmt->execute([$userId]);

    $cart = [];
    while($row = $stmt->fetch()){
     $cart[(string)$row['product_id']]=cart_normalize_row($row);   
    }

    return $cart;

}




//add item to the cart
function cart_add_item(int $productId, int $quantity=1): array
{
    global $pdo;

    $userId = cart_user_id();

    $stmt = $pdo -> prepare("SELECT id, quantity 
                                     FROM cart_items
                                     WHERE user_id = ? AND product_id = ? LIMIT 1 ");
   
   $stmt -> execute([$userId,$productId]);
   $existing = $stmt->fetch();

   //adding quantity to existing cart item
   if($existing){
    $newQty = $existing['quantity'] + $quantity;

    $update = $pdo->prepare(
        "UPDATE cart_items SET quantity = ? WHERE id = ?"
    );
    $update->execute([$newQty,$existing['id']]);

   }else{
    //Insert new
    $insert = $pdo->prepare(
        "INSERT INTO cart_items(user_id, product_id, quantity) VALUES (?,?,?)");
        $insert->execute([$userId,$productId,$quantity]);
    }

    return cart_get_item($productId);
}

function cart_remove_item(int $productId):void{
    global $pdo;
    $userId = cart_user_id();

    $stmt = $pdo -> prepare(
        "DELETE FROM cart_items WHERE user_id = ? AND product_id = ?"
    );
    $stmt->execute([$userId,$productId]);

}

//clear cart
function cart_clear():void{
      global $pdo;
    $userId = cart_user_id();

    $stmt = $pdo -> prepare(
        "DELETE FROM cart_items WHERE user_id = ?"
    );
    $stmt->execute([$userId]);

}


//count items
function cart_item_count():int{
global $pdo;
$userId = cart_user_id();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) AS total 
         FROM cart_items 
         WHERE user_id = ?");

         $stmt->execute([$userId]);

         $row = $stmt->fetch();
         return (int)$row['total'];
}

function cart_subtotal():float{
    $subtotal=0;
    foreach(cart_contents() as $item){
        $product = get_product_by_id($item['product_id']);
        $price = isset($product['price'])?(float)$product['price'] : 0;
        $subtotal += $item['qty'] * $price;
    }
    return $subtotal;
}


//get single cart item
function cart_get_item(int $productId): ?array{

    global $pdo;
    $userId = cart_user_id();
    $stmt = $pdo->prepare("SELECT id, product_id,quantity,created_at,updated_at
    FROM cart_items
    WHERE user_id = ? AND product_id = ? LIMIT 1");

    $stmt->execute([$userId,$productId]);

    $row = $stmt->fetch();
    return $row ? cart_normalize_row($row) : null;

}



?>
