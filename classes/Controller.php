<?php

require_once __DIR__ . '/Command.php';
require_once __DIR__ . '/User.php';
require_once __DIR__ . '/KeyboardUser.php';

use Longman\TelegramBot\Request;
use Longman\TelegramBot\Entities\Message;
use Longman\TelegramBot\Entities\Keyboard;

class Controller{
    private $connection;

    private $message; //json contenente i dati della chat, dell'utente e del messaggio
    private $chat_id; 
    private $text;

    private $command; //oggetto per gestire i comandi

    private $keyboard;
    private $keyboard_user;

    private $request;
    private $user;
    
    public function __construct($database, $message_received) {
        $this->connection = $database;
        $this->message = new Message($message_received['message']);
    }

    public function setParameters(){
        $this->request = new Request();
        $this->chat_id = $this->message->getChat()->getId();
        $this->text = $this->message->getText();
        $this->user = new User($this->chat_id, $this->message->getChat()->getUsername(), $this->connection);
        $this->keyboard_user = new KeyboardUser($this->connection);
    }

    public function manage(){
        $this->command = new Command($this->connection, $this->chat_id, $this->text, $this->user->getMenu()); //creazione del comando
        
        if($this->user->isNew()){ //gestione del nuovo utente: utente creato = new record e set tastiera
            $keyboard_obj = $this->keyboard_user->setKeyboard(new Keyboard([]), 0);
            $this->command->setParameters(true, $keyboard_obj);
            $this->request::sendMessage(
                $this->command->makeAction()
            );
        } else {
            if ($this->user->getAction()==NULL){ //significa che l'utente non sta effettuando alcuna operazione
                if ($this->command->getCommand() == NULL)
                    $this->command->setCommand("command_not_found");

                $this->request::sendMessage($this->command->makeAction());
            }    
        }
        

    }
    
}