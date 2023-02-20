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
    private $text;
    private $user_action;

    //costruttore che prende in input un record della tabella command e setta tutti i valori del comando
    public function __construct(&$connection, $chat_id, $text, $user_menu) {
        $this->connection = $connection;
        $this->chat_id = $chat_id;
        $this->text = $text;
        $this->setCommand($text);
        $this->user_menu = $user_menu;
        $this->do = true;
        $this->user_action = NULL;
    }

    public function getCommand(){
        return $this->command;
    }

    public function checkWednesday(){
        $now = time();
        $day = date("h:i:s");
        echo(date("h:i:s"));    
        
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

    public function setDo($user_action){
        $this->do = false;
        $this->user_action = $user_action;
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

    private function getInfo(){
        if ($this->user_action=='sendMessage'){
            return $this->httpAnswer($this->answer.' Eri rimasto qui: '.$this->getTemporaryPray());
        }
        return $this->httpAnswer($this->answer);
    }

    private function getTemporaryPray(){
        $sql = "SELECT * FROM temporary_prays WHERE chat_id = :id";
        $query = $this->connection->prepare($sql);
        $query->execute(['id' => $this->chat_id]);
        $temporaryPray = $query->fetchAll();

        return $temporaryPray[0]['pray'];
    }

    public function setR($r){
        $this->r=$r;
    }

    private function sendMessage(){
        if (($this->user_menu != 1 or !$do) and $this->user_action != 'sendMessage'){
            return $this->httpAnswer("Non puoi inviare un messaggio se prima non lo scrivi! Prima attiva il comando /scrivi o /programma e poi scrivi un messaggio!");
        } else {
            //return $this->httpAnswer($this->saveMessage());
            $this->saveMessage();
            return $this->httpAnswer($this->answer, $this->changeUserMenu(0, NULL));
            //return $this->httpAnswer($this->answer);
        }
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
                return $this->httpAnswer($this->answer, $this->changeUserMenu(1, 'writingMessage'));
            }
        }
        else 
            return $this->httpAnswer("Sei già in modalità inserimento...");
    }

    private function deleteMessage(){
        if($this->user_menu == 1){ 
            if ($this->user_action=='sendMessage') $this->deleteTemporary();
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
        return $this->setKeyboard($menu);
    }

    private function saveMessage(){
        $sql = "SELECT * FROM temporary_prays WHERE chat_id = :user";
        $query = $this->connection->prepare($sql);
        $query->execute(['user' => $this->chat_id]);
        $temporary_pray = $query->fetchAll();

        $text = $temporary_pray[0]['pray'];
        $date = new DateTime();
        $created_at = $date->format('Y-m-d H-i-s');
        $wednesday = $this->getWednesday();

        $sql = "INSERT INTO prays (text, created_at, wednesday, chat_id) VALUES
            ('$text','$created_at','$wednesday', '$this->chat_id')";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        $this->deleteTemporary();
    }

    private function deleteTemporary(){
        $sql = "DELETE FROM temporary_prays WHERE chat_id = :user";
        $query = $this->connection->prepare($sql);
        $query->execute(['user' => $this->chat_id]);
    }

    public function temporarySaveMessage($action){
        $operation = $action;
        return $this->$operation();
    }

    private function getWednesday(){
        $this->r::sendMessage($this->httpAnswer('Controllo che siano prima delle 20:30 di mercoledì...'));
        
        date_default_timezone_set('Europe/Rome');
        $today = date('l');
        $current_time = time();
        $current_time_formatted = date('H:i:s', $current_time);
        if ($today === 'Wednesday' and $current_time_formatted<'20:30:00') {
            //prendo il current timestamp
            $date = new DateTime();
            $wednesday = $date->format('Y-m-d H-i-s');
            
            $this->r::sendMessage([
                'chat_id' => $this->chat_id,
                'text'=> 'siamo sul giorno stesso '.$wednesday,
            ]);
        } else{
            $nextWednesday = strtotime('next wednesday');
            $dayOfWeek = date('N', $nextWednesday);
            if ($dayOfWeek != 3) {
            $nextWednesday = strtotime('next wednesday', $nextWednesday);
            }
            $wednesday = date('Y-m-d', $nextWednesday);
            
            $this->r::sendMessage([
                'chat_id' => $this->chat_id,
                'text'=> 'prossimo mercoledì '.$wednesday,
            ]);
        }
        
        return $wednesday;
        
        /*
        CODICE FUNZIONANTE SULLA DOMENICA
        $today = date('l');
        $current_time = time();
        $current_time_formatted = date('H:i:s', $current_time);
        if ($today === 'Sunday' and $current_time_formatted<'17:04:00') 
            echo('ok lo accetto domenica'.'<br>');
        else{
            $nextWednesday = strtotime('next sunday');
            $dayOfWeek = date('N', $nextWednesday);
            if ($dayOfWeek != 7) {
            $nextWednesday = strtotime('next sunday', $nextWednesday);
            }
            $wednesday = date('Y-m-d', $nextWednesday);
            echo('prossima domenica'.'<br>');
        }
        */

        // Calcola la data del prossimo mercoledì
        // $nextWednesday = strtotime('next wednesday');
        // // Calcola il numero del giorno della settimana per la data del prossimo mercoledì
        // $dayOfWeek = date('N', $nextWednesday);
        // // Se il giorno della settimana non è mercoledì, aggiungi il numero di giorni necessari per raggiungere il primo mercoledì successivo
        // if ($dayOfWeek != 3) {
        //     $nextWednesday = strtotime('next wednesday', $nextWednesday);
        // }
        // // Formatta la data in un formato leggibile
        // $wednesday = date('Y-m-d', $nextWednesday);
        // return $wednesday;
    }

    private function writingMessage(){
        $wednesday = $this->getWednesday();

        $date = new DateTime();
        $created_at = $date->format('Y-m-d H-i-s');
        $sql = "INSERT INTO temporary_prays (chat_id, pray, day, created_at) VALUES
            ('$this->chat_id', '$this->text', '$wednesday', '$created_at')";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        $sql = "UPDATE users SET action='sendMessage' WHERE chat_id=?"; 
        $stmt= $this->connection->prepare($sql);
        $stmt->execute([$this->chat_id]);
        
        //se si riesce a fare un'unica transazione sarebbe top


        return $this->httpAnswer('Grazie per la preghiera!');
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