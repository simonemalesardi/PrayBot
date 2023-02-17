<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/Controller.php';

include 'config.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Keyboard;


$telegram = new Telegram($bot_api_key, $bot_username);
$input = Request::getInput();

if ($input) {
    $database = DB::initialize($db_credentials, $telegram); //DB::initialize ritorna un obj PDO
    $update = json_decode($input, true);

    $controller = new Controller($database, $update);
    $controller->setParameters();
    $controller->manage();

    
}
