<?php
require_once __DIR__ . '/../sb_base.php';

function get_product_by_id($id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch();
}
