<?php

use Longman\TelegramBot\Entities\Keyboard;

class Command{
    private $command; 
    private $answer;
    private $text_menu;
    private $description;    
    public $action;
    private $connection; 
    private $chat_id;
    private $user_menu;
    private $buttons;
    private $keyboard_object;
    public $welcome;
    private $do;

    //costruttore che prende in input un record della tabella command e setta tutti i valori del comando
    public function __construct(&$connection, $chat_id, $text, $user_menu) {
        $this->connection = $connection;
        $this->chat_id = $chat_id;
        $this->setCommand($text);
        $this->user_menu = $user_menu;
        $this->do = true;
    }

    public function getCommand(){
        return $this->command;
    }

    public function setCommand($text){
        $command_row = $this->searchCommand($text);
        if($command_row != NULL){
            $this->command = $command_row['command'];
            $this->answer = $command_row['answer'];
            $this->text_menu = $command_row['text_menu'];
            $this->description = $command_row['description'];
            $this->action = $command_row['action'];    
        } else 
            $this->command = NULL;
    }

    public function setDo(){
        $this->do = false;
    }

    private function searchCommand($text){ 
        $sql = "SELECT * FROM commands WHERE command = :command or text_menu = :command";
        $query = $this->connection->prepare($sql);
        $query->execute(['command' => $text]);
        $command = $query->fetchAll();
        
        if (sizeof($command) == 1) {
            return $command[0];;
        }
        return NULL;
    }

    public function makeAction(){
        //prima di fare questo dovrei controllare lo stato dello user
        $next_hop = $this->action;
        return $this->$next_hop();
    }

    public function httpAnswer($text, $keyboard = NULL){
        $http = [
            'chat_id' => $this->chat_id,
            'text'=> $text
        ];
        if($keyboard != NULL)
            $http = [
                'chat_id' => $this->chat_id,
                'text'=> $text,
                'reply_markup' => $keyboard
            ];
        
        return $http;
    }

    private function init(){ //funzione lanciata allo start 
        $answer =  ($this->welcome) ? $this->answer : "Ci siamo già presentati"; //devo settare changeUserMenu il valore di action dell'utente
        return $this->httpAnswer($answer, $this->setKeyboard($this->user_menu)); 
    }

    private function getMessage(){
        return $this->httpAnswer($this->answer);
    }

    public function setR($r){
        $this->r=$r;
    }

    private function sendMessage(){
        return ($this->user_menu != 1 or !$do) ? $this->httpAnswer("Non puoi inviare un messaggio se prima non lo scrivi! Prima attiva il comando /scrivi o /programma
        ") : $this->httpAnswer($this->answer);
    }

    private function editMessage(){
        if (!$do)
            return $this->httpAnswer("Non puoi modificare un messaggio se prima non lo hai inserito...");
        return $this->httpAnswer($this->answer);
    }

    private function scheduleMessage(){
        if (!$do)
            return $this->httpAnswer("Non puoi modificare un messaggio se prima non lo hai inserito...");
        return $this->httpAnswer($this->answer);
    }

    private function writeMessage(){
        if($this->do){
            if($this->user_menu == 0){ 
                return $this->httpAnswer($this->answer, $this->changeUserMenu(1, 'writeMessage'));
            }
        }
        else 
            return $this->httpAnswer("Sei già in modalità inserimento...");
    }

    private function deleteMessage(){
        if($this->user_menu == 1){ 
            return $this->httpAnswer($this->answer, $this->changeUserMenu(0, NULL));
        } else {
            return $this->httpAnswer("sei già al menu principale");
        }
    }

    private function changeUserMenu($menu, $action, $refresh=false){
        if (!$refresh){
            $sql = "UPDATE users SET menu=?, action=? WHERE chat_id=?"; 
            $stmt= $this->connection->prepare($sql);
            $stmt->execute([$menu, $action, $this->chat_id]);
        }
        //return $this->keyboard_object->setKeyboard($this->buttons, $menu);
        return $this->setKeyboard($menu);
    }

    private function setKeyboard($menu){
        $keyboard = new Keyboard([]);

        $sql = "SELECT * FROM keyboards WHERE id = :id";
        $query = $this->connection->prepare($sql);
        $query->execute(['id' => $menu]);
        $buttons = $query->fetchAll();

        usort($buttons, function($a, $b) {
                return $a['position'] - $b['position'];
            });

        $array_buttons = [];
        
        foreach ($buttons as $button){
            $size = sizeof($array_buttons);

            if ($size == 0) {
                array_push($array_buttons, [$button['command']]);
            }
            else{
                if (sizeof($array_buttons[$size-1])<2 && $button['style']=='half') {
                    array_push($array_buttons[$size-1], $button['command']);  
                } else if (sizeof($array_buttons[$size-1])==2 && $button['style']=='half'){
                    array_push($array_buttons, [$button['command']]);  
                } else {
                    array_push($array_buttons, [$button['command']]);  
                    array_push($array_buttons, []);  
                }
            }
        }

        foreach ($array_buttons as $button){
            $keyboard->addRow(...$button); 
        }

        return $keyboard;
    }
}

?>