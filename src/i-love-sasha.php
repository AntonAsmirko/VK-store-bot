<?php

require_once('vendor/autoload.php');

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

echo "Я люблю Сашу😍";
echo $_ENV['GROUP_SECRET'];
echo "yo";
?>