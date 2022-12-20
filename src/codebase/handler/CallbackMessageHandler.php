<?php

namespace App\handler;

require_once "vendor/autoload.php";

use VK\CallbackApi\Server\VKCallbackApiServerHandler;
use VK\Client\VKApiClient;
class CallbackMessageHandler extends VKCallbackApiServerHandler
{
    private $vkApi;
    private $commandHandler;

    private $env;
    
    public function __construct($env)
    {
        $this->env = $env;
        $this->vkApi = new VKApiClient("5.130");
        $this->commandHandler = new CommandHandler($this->vkApi, $this->env);
    }

    function confirmation(int $group_id, ?string $secret)
    {
        if ($secret == $this->env['GROUP_SECRET'] && $group_id == $this->env['GROUP_ID']) {
            echo $this->env['API_CONFIRMATION_TOKEN'];
        }
    }

    public function messageNew(int $group_id, ?string $secret, array $object)
    {
        if ($secret != $this->env['GROUP_SECRET']) {
            echo "nok";
            return;
        }
        $this->commandHandler->handleCommand($object);
        echo "ok";
    }
}

