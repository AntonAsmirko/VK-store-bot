<?php

require_once('vendor/autoload.php');

use \PDO;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();
try {
    $myPDO = new PDO("pgsql:host=pgdb;dbname=anton1", "anton1", "anton");
    printf("connected\n");
    $res = $myPDO->query("SELECT * FROM item");
    $rows = $res->fetchAll();
    foreach($rows as $row) {
        printf("$row[0]\n$row[1]\n$row[2]\n$row[3]\n\n\n");
    }
} catch(PDOException $e){
    echo $e->getMessage();
}

printf("Я люблю Сашу😍\n");
?>