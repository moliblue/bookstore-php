
<?php
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "sbonline";

try{
    $pdo = new PDO('mysql:dbname=sbonline', 'root', '', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // 返回关联数组，兼容 MySQLi
            PDO::ATTR_EMULATE_PREPARES => false,
 ]);

}catch(PDOException $e){ 
    die("Connection failed: ".$e->getMessage());
}
?>
