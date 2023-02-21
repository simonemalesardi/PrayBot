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
    private $privileges;

    public function __construct(&$connection, $chat_id, $text, $user_menu, $privileges) {
        $this->connection = $connection;
        $this->chat_id = $chat_id;
        $this->text = $text;
        $this->setCommand($text);
        $this->user_menu = $user_menu;
        $this->do = true;
        $this->user_action = NULL;
        $this->privileges = $privileges;
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

    public function setDo($user_action){
        $this->do = false;
        $this->user_action = $user_action;
    }

    //it contains the query that allows to obtain the row relating to the command launched by the user
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

    //it's the manager of most of the methods
    public function makeAction(){
        //prima di fare questo dovrei controllare lo stato dello user
        $next_hop = $this->action;
        return $this->$next_hop();
    }

    //it's the json returned for every user request
    public function httpAnswer($text, $keyboard = NULL){
        $http = [
            'chat_id' => $this->chat_id,
            'text'=> $text,
            'parse_mode' => 'HTML'
        ];
        if($keyboard != NULL)
            $http = [
                'chat_id' => $this->chat_id,
                'text'=> $text,
                'reply_markup' => $keyboard,
                'parse_mode' => 'HTML'
            ];
        
        return $http;
    }

    //it's the function run every time the user digit /start or press the initial Start button (the Start button appears also every time the user cleans the conversation)
    private function init(){ 
        $answer =  ($this->welcome) ? $this->answer : "Ci siamo già presentati, ma nel caso in cui non ti ricordassi mi ripresento!\n\n".$this->answer; //devo settare changeUserMenu il valore di action dell'utente
        return $this->httpAnswer($answer, $this->setKeyboard($this->user_menu)); 
    }

    //it obtain the message stored in the command row of the database table
    private function getMessage(){
        return $this->httpAnswer($this->answer);
    }

    //it obtains the info after the user press the relative button
    private function getInfo(){
        $answer = $this->answer;
        if ($this->user_action=='writingMessage')
            $answer = $answer.'<i>Non hai scritto ancora nessuna preghiera, scrivi una preghiera e conferma tramite /invio, altrimenti /annulla</i>';
        
        return $this->httpAnswer($answer);
    }

    //it returns the temporary pray inserted the previous time by the user
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

    //it allows to send pray and to store in the final table where the admin can download prays
    public function sendMessage(){
        if ($this->user_menu != 1)
            return $this->httpAnswer("Non puoi inviare un messaggio se prima non lo scrivi! Attiva il comando /scrivi o /programma e poi scrivi un messaggio!");
        else {
            $this->saveMessage();
            return $this->httpAnswer('Grazie per aver condiviso con me la tua preghiera!', $this->changeUserMenu(0, NULL));
        }
    }

    //method that allows the saving of temporary prays: prays written but not sent
    private function saveMessage(){
        $text = $this->text;
        $date = new DateTime();
        $created_at = $date->format('Y-m-d H-i-s');
        $wednesday = $this->getWednesday();

        $sql = "INSERT INTO prays (text, created_at, wednesday, chat_id) VALUES
            ('$text','$created_at','$wednesday', '$this->chat_id')";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
    }
    
    private function downloadPrays(){
        $wednesday = $this->getWednesday('20:45:00');
        $unique_pray = $this->getPray($wednesday);
        $wednesday = date('d/m/Y', strtotime($wednesday));
        return $this->httpAnswer("<b>Ecco le preghiere di mercoledì $wednesday</b>".$unique_pray);    
    }

    private function getPray($wednesday){
        $sql = "SELECT * FROM prays WHERE wednesday = :wed";
        $query = $this->connection->prepare($sql);
        $query->execute(['wed' => $wednesday]);
        $prays = $query->fetchAll();
        
        $unique_string_of_prays = "";
        $cont = 1;
        foreach($prays as $pray){
            $unique_string_of_prays = $unique_string_of_prays."\n\n<i><b>$cont. </b>".$pray['text']."</i>";
            $cont = $cont +1 ;
        }
        return $unique_string_of_prays;
    }
    
    private function undoEdit(){
        if($this->user_action === 'changingMessage'){
            return $this->httpAnswer("Operazione di modifica annullata", $this->changeUserMenu(1, 'sendMessage'));
        } else 
            return $this->httpAnswer("Non puoi utilizzare questa funzionalità, non hai effettuato alcuna operazione che richiede l'annullamento");    
    }

    //it allows to schedule the date to dedicate the pray
    private function scheduleMessage(){
        if (!$do)
            return $this->httpAnswer("Non puoi modificare un messaggio se prima non lo hai inserito...");
        return $this->httpAnswer($this->answer);
    }

    //it allows to write a pray and to store it in a temporary database table 
    private function writeMessage(){
        if($this->do){
            if($this->user_menu == 0){ 
                return $this->httpAnswer($this->answer, $this->changeUserMenu(1, 'sendMessage'));
            }
        }
        else {
            if ($this->user_action == 'sendMessage')
                return $this->httpAnswer("Sei già in modalità inserimento...");
            return $this->httpAnswer("La preghiera è stata salvata correttamente");
        }
    }

    //it allows to delete a pray that has not yet been sent 
    private function deleteMessage(){
        if($this->user_menu == 1){ 
            return $this->httpAnswer($this->answer, $this->changeUserMenu(0, NULL));
        } else {
            return $this->httpAnswer("sei già al menu principale");
        }
    }

    //method that allows to change the keyboard and to refresh eventually the action of a user
    private function changeUserMenu($menu, $action){ //refresh = true, viene refreshata la keyboard
        $sql = "UPDATE users SET menu=?, action=? WHERE chat_id=?"; 
        $stmt= $this->connection->prepare($sql);
        $stmt->execute([$menu, $action, $this->chat_id]);

        return $this->setKeyboard($menu);
    }

    //it obtains the following wednesday considering the datetime at the operation time
    private function getWednesday($hour='20:30:00'){        
        date_default_timezone_set('Europe/Rome');
        $today = date('l');
        $current_time = time();
        $current_time_formatted = date('H:i:s', $current_time);
        if ($today === 'Wednesday' and $current_time_formatted<$hour) {
            //prendo il current timestamp
            $date = new DateTime();
            return $date->format('Y-m-d H-i-s');
            
        } else{
            $nextWednesday = strtotime('next wednesday');
            $dayOfWeek = date('N', $nextWednesday);
            if ($dayOfWeek != 3) {
            $nextWednesday = strtotime('next wednesday', $nextWednesday);
            }
            return date('Y-m-d', $nextWednesday);
        }
    }
    
    //it returns the keyboard composed of the buttons
    private function setKeyboard($menu){
        $keyboard = new Keyboard([]);

        $sql = "SELECT * FROM keyboards WHERE id = :id and admin <= :privileges";
        $query = $this->connection->prepare($sql);
        $query->execute(['id' => $menu, 'privileges' => $this->privileges]);
        $buttons = $query->fetchAll();

        usort($buttons, function($a, $b) {
                return $a['position'] - $b['position'];
            });

        $array_buttons = [];
        
        foreach ($buttons as $button){
            $size = sizeof($array_buttons);

            if ($size == 0) {
                if ($button['style']=='half')
                    array_push($array_buttons, [$button['command']]);
                if ($button['style']=='full'){
                    array_push($array_buttons, [$button['command']]);
                    array_push($array_buttons, []);  
                }
            }
            else{
                if (sizeof($array_buttons[$size-1])==0){
                    if($button['style']=='half')
                        array_push($array_buttons[$size-1], $button['command']); 
                    else{
                        array_push($array_buttons[$size-1], $button['command']); 
                        array_push($array_buttons, []);  
                    }
                } else if (sizeof($array_buttons[$size-1])==1 && $button['style']=='half') {
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
        
        //$this->r::sendMessage($this->httpAnswer($keyboard));

        return $keyboard;
    }
}

?>