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
        if ($this->user_action=='writingMessage' or $this->user_action=="schedulingMessage")
            $answer = $answer.'<i>Non hai scritto ancora nessuna preghiera, scrivi una preghiera premi invio per confermare, altrimenti /annulla</i>';
        
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
        if($this->user_action == NULL){
            //update dello stato
            return $this->httpAnswer('Inserisci la data per cui vuoi programmare la preghiera:', $this->changeUserMenu(1, 'schedulingMessage'));
        } else if ($this->user_action == 'schedulingMessage'){
            return $this->httpAnswer('Stai già programmando l\'inserimento. Inserisci la data ');
        } else {
            if ($this->user_action == 'sendMessage')
                return $this->httpAnswer('Prima di procedere con il comando /programma, termina l\'operazione che hai iniziato con /scrivi. Scrivi quindi una preghiera');    
            return $this->httpAnswer('Prima di procedere con il comando, finisci di programmare la preghiera. Inserisci la preghiera');
        }
    }

    public function schedulingMessage(){
        $answer = $this->checkWednesday();
        return $this->httpAnswer($answer);
    }

    public function sendingMessage(){
        $sql = "SELECT * FROM scheduled_prays WHERE chat_id = :chat_id";
        $query = $this->connection->prepare($sql);
        $query->execute(['chat_id' => $this->chat_id]);
        $scheduled = $query->fetchAll()[0];

        $created_at = $scheduled['created_at'];
        $schedule = $scheduled['scheduled'];
        $sql = "INSERT INTO prays (text, created_at, wednesday, chat_id) VALUES
        ('$this->text','$created_at','$schedule', '$this->chat_id')";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();

        $sql = "DELETE FROM scheduled_prays WHERE chat_id = :chat_id";
        $query = $this->connection->prepare($sql);
        $query->execute(['chat_id' => $this->chat_id]);

        return $this->httpAnswer('Grazie per aver condiviso con me la tua preghiera!', $this->changeUserMenu(0, NULL));
    }

    private function is_valid_date($date_string) {
        $date = DateTime::createFromFormat('d/m/Y', $date_string);
        return $date && $date->format('d/m/Y') === $date_string;
    }

    private function checkWednesday(){
        $next_wednesday = date('d/m/Y', strtotime($this->getWednesday()));
        // controlla che la stringa sia nel formato gg/mm/aaaa
        if (!preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $this->text)) {
            return "Ops! La data che hai inserito non è in un formato corretto. Il formato corretto è gg/mm/aaaa (es: $next_wednesday)";
        } 

        // verifica se la stringa è una data valida
        if ($this->is_valid_date($this->text)){
            $this->text = DateTime::createFromFormat('d/m/Y', $this->text);
            $isWednesday = $this->isWednesdayAndLesser($next_wednesday);
            if ($isWednesday[0]){
                $this->updateUser(1, 'sendingMessage');
                
                $text = $this->text->format('Y-m-d');
                $date = new DateTime();
                $created_at = $date->format('Y-m-d H-i-s');

                $sql = "INSERT INTO scheduled_prays (chat_id, created_at, scheduled) VALUES
                    ('$this->chat_id','$created_at','$text')";
                $stmt = $this->connection->prepare($sql);
                $stmt->execute();
                
                return 'Inserisci ora la preghiera che ti sta a cuore';
            }
            else{
                if($isWednesday[1] != NULL)
                    return 'Non puoi programmare una preghiera per questa data '.$isWednesday[1];
                else
                    return 'Non puoi programmare una preghiera per questa data perché è una data passata';
            }          
        } else {
            return 'La data che hai inserito è nel formato corretto ma hai inserito una data inesistente, reinserisci!';
        }
        
    }       

    function isWednesdayAndLesser($next_wednesday) {    
        if ($this->text->format('N') != 3) {
            return [false, 'perchè non è un mercoledì'];
        }

        $next_wednesday = DateTime::createFromFormat('d/m/Y', $next_wednesday);
        return [$this->text >= $next_wednesday, NULL];
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
            else if($this->user_action == 'schedulingMessage' or $this->user_action == 'sendingMessage')
                return $this->httpAnswer("Prima di procedere con l'inserimento di una nuova preghiera, termina la programmazione che stavi facendo");

            return $this->httpAnswer("La preghiera è stata salvata correttamente");
        }
    }

    //it allows to delete a pray that has not yet been sent 
    private function deleteMessage(){
        if($this->user_menu == 1){ 
            if($this->user_action == 'sendingMessage'){
                $sql = "DELETE FROM scheduled_prays WHERE chat_id = :chat_id";
                $query = $this->connection->prepare($sql);
                $query->execute(['chat_id' => $this->chat_id]);
            }
            return $this->httpAnswer($this->answer, $this->changeUserMenu(0, NULL));
        } else {
            return $this->httpAnswer("Sei già al menu principale");
        }
    }

    //method that allows to change the keyboard and to refresh eventually the action of a user
    private function changeUserMenu($menu, $action){ //refresh = true, viene refreshata la keyboard
        $this->updateUser($menu, $action);
        return $this->setKeyboard($menu);
    }

    private function updateUser($menu, $action){
        $sql = "UPDATE users SET menu=?, action=? WHERE chat_id=?"; 
        $stmt= $this->connection->prepare($sql);
        $stmt->execute([$menu, $action, $this->chat_id]);
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