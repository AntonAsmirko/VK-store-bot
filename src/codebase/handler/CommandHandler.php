<?php

namespace App\handler;

require_once "vendor/autoload.php";

use \PDO;
use App\repository\CatalogRepository;
use App\repository\AdminRepository;
use Dotenv\Dotenv;
class CommandHandler {

    private $vkApi;
    private $catalogRepository;

    private $adminRepository;

    private $env;

    private $onboardingInfo = "
        Привет! Я бот магазина SUPERSHOP!
У меня вы можете посмотреть каталог магазина и описание товаров.
Список команд, которые я понимаю:
💥 каталог - посмотреть категории товаров в магазине
💥 категория <id категории> - посмотреть товары, представленные в категории
💥 товар <id товара> - посмотреть описание товара с данным id
    ";

    public function __construct($vkApi, $env)
    {
        $this->env = $env;
        $this->vkApi = $vkApi;
        $pdo = new PDO("pgsql:host=pgdb;dbname=anton1", $this->env['POSTGRES_USER'], $this->env['POSTGRES_PASSWORD']);
        $this->catalogRepository = new CatalogRepository($pdo);
        $this->adminRepository = new AdminRepository($pdo, $this->env['ADMINS']);
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

        $this->vkApi->messages()->send($this->env['BOT_TOKEN'], $params);
    }
}
?>