<?php

require_once "vendor/autoload.php";

use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;
use \PDO;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class Repository{

    private $PDO;

    public function __construct(){
        $this->PDO = new PDO("pgsql:host=pgdb;dbname=anton1", "anton1", "anton");
    }

    public function getCategories(){
        $result_array = array();
        $query_res = $this->PDO->query("SELECT * FROM categories");
        $rows = $query_res->fetchAll();
        foreach($rows as $row) {
            array_push($result_array, "
                $row[1] (id: $row[0])\n
                $row[2]
            ");
        }
        return $result_array;
    }
}

class CommandHandler {

    private $vkApi;
    private $repository;

    private $onboardingInfo = "
        Привет! Я бот магазина SUPERSHOP!
        У меня вы можете посмотреть каталог магазина и описание товаров.
        Список команд, которые я понимаю:
        💥 категории - посмотреть категории товаров в магазине
        💥 каталог <категория> - посмотреть товары, представленные в категории
        💥 товар <id> - посмотреть описание товара с данным id
    ";

    public function __construct($vkApi)
    {
        $this->vkApi = $vkApi;
        $this->repository = new Repository();
    }

    public function handleCommand(array $object)
    {
        $msq = $object["message"];
        $command = $msq->text;

        if (str_starts_with($command, "каталог")){
            $categories = $this->repository->getCategories();
            foreach($categories as $category){
                $this->sendMessage($object, $category);    
            }
        } elseif (str_starts_with($command, "начать")) {
            $this->sendMessage($object, $this->onboardingInfo);
        }
         elseif (str_starts_with($command, "категории")) {
            $categoryId = explode(" ", $command)[1];
            $this->sendMessage($object, "Вывожу категорию $categoryId");
        } elseif (str_starts_with($command, "товар")) {
            $itemId = explode(" ", $command)[1];
            $this->sendMessage($object, "интфа про товар $itemId");
        } elseif (str_starts_with($command, "Сашу")) {
            $this->sendMessage($object, "люблю❤️");
        }        
    }

    private function sendMessage(array $object, string $responce)
    {
        $message = $object["message"];
        $user_id = $message->from_id;

        $this->vkApi->messages()->send($_ENV['BOT_TOKEN'], [
            "user_id" => $user_id,
            "peer_id" => $user_id,
            "random_id" => random_int(0, PHP_INT_MAX),
            "message" => $responce
        ]);
    }
}

class CallbackMessageHandler extends VKCallbackApiServerHandler
{
    private $vkApi;
    private $commandHandler;
    
    public function __construct()
    {
        $this->vkApi = new VKApiClient("5.130");
        $this->commandHandler = new CommandHandler($this->vkApi);
    }

    function confirmation(int $group_id, ?string $secret)
    {
        if ($secret == $_ENV['GROUP_SECRET'] && $group_id == $_ENV['GROUP_ID']) {
            echo $_ENV['API_CONFIRMATION_TOKEN'];
        }
    }

    public function messageNew(int $group_id, ?string $secret, array $object)
    {
        if ($secret != $_ENV['GROUP_SECRET']) {
            echo "nok";
            return;
        }
        $this->commandHandler->handleCommand($object);
        echo "ok";
    }
}

$handler = new CallbackMessageHandler();
$data = json_decode(file_get_contents("php://input"));
$handler->parse($data);


?>