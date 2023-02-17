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

    private $allowed_actions = ['/elimina', '/info', '/aiuto', '/start'];
    
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
        $this->command = new Command($this->connection, $this->chat_id, $this->text, $this->user->getMenu()); //creazione del comando
        
        if($this->user->isNew()){ //gestione del nuovo utente: utente creato = new record e set tastiera
            $this->command->welcome = true;
            $this->request::sendMessage(
                $this->command->makeAction()
            );
        } else { //se l'utente non è nuovo 
            if ($this->user->getAction()==NULL){ //se non sta effettuando alcuna operazione (come scrivi, modifica o programma)
                if ($this->command->getCommand() == NULL)
                    $this->command->setCommand("command_not_found");

                $this->request::sendMessage(['chat_id'=> $this->chat_id,
                'text' => "test",]);
                $this->command->setR($this->request);
                $this->request::sendMessage(
                    $this->command->makeAction()
                );
            } else { //se invece sta effettuando un'operazione
                $this->command->httpAnswer($this->user->getAction());
                if ($this->command->getCommand() == NULL){
                    $this->request::sendMessage($this->command->httpAnswer("Grazie per il messaggio!"));
                } else {
                    $this->command->setDo();
                    $this->command->setR($this->request);
                    $this->request::sendMessage($this->command->makeAction());
                }
            }
        }
    }


        /*$this->request::sendMessage([
            'chat_id' => $this->chat_id,
            'text' => "test",
            'reply_markup' => json_encode([
                'remove_keyboard' => true,
            ]),
        ]);*/
        
    
}