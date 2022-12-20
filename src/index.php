<?php

require_once "vendor/autoload.php";

use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;
use \PDO;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

class AdminRepository {
    private $PDO;

    public function __construct($pdo){
        $this->PDO = $pdo;

    }

    public function isAdmin($user_id){
        $res = $this->PDO->query("SELECT ID FROM user_admin WHERE ID = $user_id AND IS_ADMIN = true");
        $admins = $res->fetchAll();
        foreach($admins as $item){
            if($item[0] == $user_id){
                return true;
            }
        }
        return false;
    }

    public function loadAdmins(){
        $this->PDO->query("CREATE TABLE IF NOT EXISTS user_admin (ID INT NOT NULL, IS_ADMIN BOOLEAN NOT NULL)");
        $admins = explode(",", $_ENV['ADMINS']);
        foreach($admins as $admin){
            $int_id = intval($admin);
            if(!$this->isAdmin($int_id)){
                $this->PDO->query("INSERT INTO user_admin (ID, IS_ADMIN) VALUES ($int_id, true)");
            }
        }
    }
}

class CatalogRepository{

    private $PDO;

    public function __construct($pdo){
        $this->PDO = $pdo;
    }

    public function getCategories() {
        $result_array = array();
        $query_res = $this->PDO->query("SELECT * FROM category");
        $rows = $query_res->fetchAll();
        foreach($rows as $row) {
            array_push($result_array, array("$row[1] (id: $row[0])\n$row[2]", $row[3]));
        }
        return $result_array;
    }

    public function getItemsByCategory($categoryId) {
        $result_array = array();
        $query_res = $this->PDO->query("SELECT * from 
            item JOIN category_to_item ON item.ID = category_to_item.ITEM_ID 
            WHERE category_to_item.CAT_ID = $categoryId");
        $rows = $query_res->fetchAll();
        foreach($rows as $row) {
            array_push($result_array,
             array("$row[1]\nОписание товара:\n$row[2]\nСтоимость:$row[3]₽", $row[4]));
        }
        return $result_array;
    }

    public function getItemInfo($itemName) {
        $result_array = array();
        $query_res = $this->PDO->query("SELECT * FROM item WHERE item.ITEM_NAME = '$itemName'");
        $rows = $query_res->fetchAll();
        foreach($rows as $row) {
            array_push($result_array, "$row[1]\nОписание товара:\n$row[2]\nСтоимость:$row[3]₽");
        }
        return $result_array[0];
    }

    public function addItem($itemId, $itemName,
                            $itemDescription, $itemPrice,
                             $itemMedia, $categoryId) {
        $this->PDO->query("INSERT INTO item (ID, ITEM_NAME, ITEM_DESCRIPTION, ITEM_PRICE, MEDIA_ID) 
        VALUES ($itemId,
         '$itemName',
          '$itemDescription',
          $itemPrice,
          '$itemMedia'
          )");
        $this->PDO->query("INSERT INTO category_to_item (CAT_ID, ITEM_ID)
                             VALUES ($categoryId, $itemId);");
    }

    public function addCategory($id, $catName, $description, $mediaId){
        $this->PDO->query("INSERT INTO category (ID, CAT_NAME, CAT_DESCRIPTION, MEDIA_ID)
        VALUES ($id,'$catName','$description', '$mediaId')");
    }

    public function removeItem($itemId) {
        $this->PDO->query("DELETE FROM item WHERE ID = $itemId");
        $this->PDO->query("DELETE FROM category_to_item WHERE ITEM_ID = $itemId");
    }

    public function removeCategory($catId){
        $this->PDO->query("DELETE FROM category WHERE ID = $catId");
        $this->PDO->query("DELETE FROM category_to_item WHERE CAT_ID = $catId");
    }
}

class CommandHandler {

    private $vkApi;
    private $catalogRepository;

    private $adminRepository;

    private $onboardingInfo = "
        Привет! Я бот магазина SUPERSHOP!
У меня вы можете посмотреть каталог магазина и описание товаров.
Список команд, которые я понимаю:
💥 каталог - посмотреть категории товаров в магазине
💥 категория <id категории> - посмотреть товары, представленные в категории
💥 товар <id товара> - посмотреть описание товара с данным id
    ";

    public function __construct($vkApi)
    {
        $this->vkApi = $vkApi;
        $pdo = new PDO("pgsql:host=pgdb;dbname=anton1", "anton1", "anton");
        $this->catalogRepository = new CatalogRepository($pdo);
        $this->adminRepository = new AdminRepository($pdo);
        $this->adminRepository->loadAdmins();
    }

    public function handleCommand(array $object)
    {
        $msq = $object["message"];
        $command = $msq->text;

        if (str_starts_with($command, "каталог")){
            $categories = $this->catalogRepository->getCategories();
            if(!empty($categories)){
                foreach($categories as $category){
                    $this->sendMessage($object, $category[0], $category[1]);    
                }
            } else {
                $this->sendMessage($object, "категории пусты");    
            }
        } elseif (str_starts_with($command, "начать")) {
            $this->sendMessage($object, $this->onboardingInfo);
        }
         elseif (str_starts_with($command, "категория")) {
            $categoryId = explode(" ", $command)[1];
            $items = $this->catalogRepository->getItemsByCategory(intval($categoryId));
            if(!empty($items)){
                foreach($items as $item) {
                    $this->sendMessage($object, $item[0], $item[1]);
                }
            } else {
                $this->sendMessage($object, "эта категория товаров пуста");
            }
        } elseif (str_starts_with($command, "товар")) {
            $itemName = explode(" ", $command)[1];
            $item = $this->catalogRepository->getItemInfo($itemName);
            $this->sendMessage($object, $item);
            
        } elseif (str_starts_with($command, "Сашу")) {
            $this->sendMessage($object, "люблю❤️");
        } elseif (str_starts_with($command, "AddItem")) {
            if($this->adminRepository->isAdmin($msq->from_id)){
                $items = explode("$", $command);
                $categoryId = $items[1];
                $itemId = $items[2];
                $itemName = $items[3];
                $itemDescription = $items[4];
                $itemPrice = $items[5];
                $itemMedia = $items[6];
                $this->catalogRepository->addItem(
                    $itemId,
                    $itemName,
                    $itemDescription,
                    $itemPrice,
                    $itemMedia,
                    $categoryId
                );
                $this->sendMessage($object, "🥳Товар успешно добавлен🥳");
            } else {
                $this->sendMessage($object, "Ты не админ🤬");
            }
        } elseif(str_starts_with($command, "AddCat")) {
            if($this->adminRepository->isAdmin($msq->from_id)){
                $items = explode("$", $command);
                $categoryId = $items[1];
                $catName = $items[2];
                $description = $items[3];
                $mediaId = $items[4];
                $this->catalogRepository->addCategory($categoryId, $catName, $description, $mediaId);
                $this->sendMessage($object, "🥳Категория товаров успешно добавлена🥳");
            } else {
                $this->sendMessage($object, "Ты не админ🤬");
            }
        } else if(str_starts_with($command, "RmCat")){
            if ($this->adminRepository->isAdmin($msq->from_id)) {
                $items = explode("$", $command);
                $catId = $items[1];
                $this->catalogRepository->removeCategory(intval($catId));
                $this->sendMessage($object, "🗑️Категория товаров успешно удалена🗑️");
            } else {
                $this->sendMessage($object, "Ты не админ🤬");
            }
        } else if(str_starts_with($command, "RmItem")) {
            if ($this->adminRepository->isAdmin($msq->from_id)) {
                $items = explode("$", $command);
                $itemId = $items[1];
                $this->catalogRepository->removeItem(intval($itemId));
                $this->sendMessage($object, "🗑️Товаров успешно удален🗑️");
            } else {
                $this->sendMessage($object, "Ты не админ🤬");
            }
        }
    }

    private function sendMessage(array $object, string $responce, ?string $attachment = null)
    {
        $message = $object["message"];
        $user_id = $message->from_id;

        $params = [
            "user_id" => $user_id,
            "peer_id" => $user_id,
            "random_id" => random_int(0, PHP_INT_MAX),
            "message" => $responce
        ];

        if($attachment != null) {
            $params += ["attachment" => $attachment];
        }

        $this->vkApi->messages()->send($_ENV['BOT_TOKEN'], $params);
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