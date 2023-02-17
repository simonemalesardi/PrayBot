<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/classes/Database.php';
require_once __DIR__ . '/classes/Command.php';
require_once __DIR__ . '/classes/User.php';
require_once __DIR__ . '/classes/KeyboardUser.php';


include 'config.php';

use Longman\TelegramBot\Telegram;
use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\DB;
use Longman\TelegramBot\Entities\Keyboard;


$telegram = new Telegram($bot_api_key, $bot_username);
$input = Request::getInput();

if ($input) {
    $database = new Database(DB::initialize($db_credentials, $telegram)); //DB::initialize ritorna un obj PDO

    $update = json_decode($input, true);
    $message = new Message($update['message']);
    $chat_id = $message->getChat()->getId();
    $text = $message->getText();

    $command_row = $database->searchCommand($text);

    $request = new Request();
    $user = new User($chat_id, $message->getChat()->getUsername(), $database->getConnection(), $request);
    $keyboard_user = new KeyboardUser($database->getConnection(), $user->getMenu(), $chat_id);
    
    // if($user->isNew){
    $keyboard_user->setKeyboard(new Keyboard([]), $request);
    // }
    
    /*if ($user->getAction()==NULL){
        //leggo il comando
    } */
    
    /*$keyboard_obj = new KeyboardUser($$database->getConnection(), $user, new Keyboard([]))

    Request::sendMessage([
        'chat_id' => $chat_id,
        'text' => 'Seleziona un opzione:',
        'reply_markup' => $keyboard_obj
    ]);*/

    /*
    un utente può essere in modalità inserimento, quindi deve scrivere il testo
    controllare che se c'è una modalità inserita, un utente non inserisca un comando 

    $user = new User('temp', $database->getConnection(), $chat_id, 'temp');
    if ($user->getAction() != NULL){
        //qui così dovrei settare il comando da eseguire
        Request::sendMessage([
            'chat_id' => $chat_id, 
            'text' => $user->getAction(),
        ]);
        //$command = new Command($command_row, $database->getConnection(), $chat_id);
        

    }*/

    // if ($command_row != NULL){
    //     $command = new Command($command_row, $database->getConnection(), $chat_id);
    //     $s = $command->makeAction();

    //     Request::sendMessage([
    //         'chat_id' => $chat_id, 
    //         'text' => $s,
    //     ]);
    // }
}
