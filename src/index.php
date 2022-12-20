<?php

require_once "vendor/autoload.php";

use App\handler\CallbackMessageHandler;
use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$handler = new CallbackMessageHandler($_ENV);
$data = json_decode(file_get_contents("php://input"));
$handler->parse($data);

?>