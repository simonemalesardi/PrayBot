<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/Database.php';
include 'config.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\DB;

$telegram = new Telegram($bot_api_key, $bot_username);
$input = Request::getInput();

if ($input) {
    $database = new Database(DB::initialize($db_credentials, $telegram)); //ritorna un obj PDO

    $update = json_decode($input, true);
    $message = new Message($update['message']);
    $chat_id = $message->getChat()->getId();
    $text = $message->getText();

    $commandAction = $database->searchCommand($text);
    if ($commandAction != NULL){
        Request::sendMessage([
            'chat_id' => $chat_id, 
            'text' => $commandAction,
        ]);
    }
}