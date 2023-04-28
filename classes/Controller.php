<?php

require_once __DIR__ . '/Command.php';
require_once __DIR__ . '/User.php';

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
    }

    public function start(){
        $this->command = new Command($this->connection, $this->text, $this->user); //creazione del comando
        //$this->request::sendMessage($this->command->httpAnswer(password_hash($this->chat_id, PASSWORD_BCRYPT)));
        if($this->user->isNew()){ //gestione del nuovo utente: utente creato = new record e set tastiera
            $this->command->setR($this->request);

            $this->request::sendMessage(
                $this->command->makeAction()
            );
        } else { //se l'utente non è nuovo 
            if ($this->user->getAction()==NULL){ //se non è in modalità scrittura
                if ($this->command->getCommand() == NULL)
                    $this->command->setCommand("command_not_found");

                $this->command->setR($this->request);
                $this->request::sendMessage(
                    $this->command->makeAction()
                );
            } else { //se invece sta effettuando un'operazione
                if ($this->command->getCommand() == NULL){ 
                    //$this->command->setR($this->request);
                    $this->request::sendMessage($this->command->makeUserAction());
                } else {
                    //$this->command->setR($this->request);
                    $this->request::sendMessage($this->command->makeAction());
                }
            }
        }
    }
}