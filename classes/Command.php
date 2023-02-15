<?php

class Command{
    private $command; 
    private $answer;
    private $text_menu;
    private $description;    
    public $action;
    private $connection; 
    private $user;
    //private $func;

    //costruttore che prende in input un record della tabella command e setta tutti i valori del comando
    public function __construct($command, &$connection, $user) {
        $this->command = $command['command'];
        $this->answer = $command['answer'];
        $this->text_menu = $command['text_menu'];
        $this->description = $command['description'];
        $this->action = $command['action'];
        $this->connection = $connection;
        $this->user = $user;
    }

    public function makeAction(){
        $next_hop = $this->action;
        return $this->$next_hop();
    }

    private function init(){ //funzione lanciata allo start
        $sql = "SELECT * FROM users WHERE chat_id = :chat_id";
        $query = $this->connection->prepare($sql);
        $query->execute(['chat_id' => $this->user]);
        $command = $query->fetchAll();
        
        if(sizeof($command)==0){ //solo se l'utente non esiste
            $date = new DateTime();
            $converted_date = $date->format('Y-m-d H-i-s');
            $sql = "INSERT INTO users (chat_id, created_at, menu) VALUES('$this->user', '$converted_date', 0)";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute();
            return "Benvenuto!";
        }
        return "Ci siamo già presentati";
    }



    // private function getMessage(){
    //     return $this->message;
    // }

    // private function getHelpMessage(){
    //     require "connectionDB/Config.php";  
    //     //add new line before commands
    //     $newReplace = "";
        
    //     //cerca i comandi con DescriptionHelp non null
    //     $commands = $this->connection->universalNotNullQuery($table_commands, $descriptionHelp_Commands);
    //     foreach ($commands as $command){
    //         $newReplace = $newReplace.$bullet_point." ".$command[$keyCMD_Commands]." = ".$command[$descriptionHelp_Commands].$new_line;  
    //     }
        
    //     $newMessage = str_replace($replace_string, $newReplace, $this->message);
    //     return $newMessage;
    // }

    // private function getGeneralContacts($value){
    //     require "Contacts.php";
    //     require "connectionDB/Config.php";  

    //     //ottengo tutti i contatti
    //     $contacts = $this->connection->universalPluriQuery($table_contacts, $type_Contacts, $value, $id_Contacts);
        
    //     $list_contacts = [];
        
    //     foreach ($contacts as $contact){ 
    //         $con = new Contacts($contact[$title_Contacts], $contact[$contact_Contacts]);
    //         $founded = false;
    //         //$count = 0;
    //         //cerco nella lista di contatti se c'è già un contatto con lo stesso Title 
    //         foreach ($list_contacts as $single_contact){
    //             //se lo ho già, aggiungo il contatto
    //             if($single_contact->getTitle()==$con->getTitle()){
    //                 //$list_contacts[$count]->setContact($con->getContact());
    //                 $single_contact->setContact($con->getContact());
    //                 $founded = true;
    //             }
    //             //$count += 1;
    //         }    
    //         if (!$founded)
    //             $list_contacts[]=$con;

    //     }

    //     $contacts_string = $this->message.$new_line;
        
    //     foreach ($list_contacts as $contact){
    //         //$con = new Contacts($contact[$title_Contacts], $contact[$contact_Contacts]);
    //         $contacts_string .= $contact->allToString();
    //     }
    //     return $contacts_string;
    // }


    // //ottiene i contatti principali
    // private function getContacts(){ 
    //     require "connectionDB/Config.php";  
    //     return $this->getGeneralContacts(0);
    // }

    // //ottiene i contatti preado1
    // private function getPreado1Contacts(){ 
    //     require "connectionDB/Config.php";  
    //     return $this->getGeneralContacts(5);
    // }

    // //ottiene i contatti preado1
    // private function getPreado2Contacts(){ 
    //     require "connectionDB/Config.php";  
    //     return $this->getGeneralContacts(6);
    // }

    // //ottiene i contatti preado1
    // private function getPreado3Contacts(){ 
    //     require "connectionDB/Config.php";  
    //     return $this->getGeneralContacts(7);
    // }

    // //ottiene i contatti preado1
    // private function getAdoContacts(){ 
    //     require "connectionDB/Config.php";  
    //     return $this->getGeneralContacts(8);
    // }

    // private function getSocial(){
    //     $this->getMessage();
    // }

    // //converte l'azione in metodo
    // public function getAction(){ //ritorna un vettore con: l'azione convertita in metodo e 
    //     //0 se il menu non cambia menu, != 0 altrimenti (il numero rappresenta l'id del menu)
    //     $function_name = $this->action;
    //     if($this->changeMenu != null)
    //         return array($this->$function_name(), $this->changeMenu);
    //     return array($this->$function_name(), 0);
    // }

    // public function getTextMenu(){
    //     return $this->textMenu;
    // }

    // public function getChangeMenu(){
    //     return $this->changeMenu;
    // }

    // //metodo per lo start
    // public function initialize(){
    //     require "connectionDB/Config.php";  
    //     $welcomeMessage = $this->user->getStatusString(); //tupla (stringa, comando)
        
    //     //if (!$this->user->getWithUsername())

    //     if($welcomeMessage[1] != $this->keyCMD){
    //         $this->refresh($welcomeMessage[1]);
    //     }
        
    //     return $welcomeMessage[0].$this->message;
    // }

    // //cambia il comando attuale con un altro comando (usato per lo /start -> /start2)
    // private function refresh($newCMD){
    //     require "connectionDB/Config.php";  
    //     $command = $this->connection->
    //                 universalQuery($table_commands, $keyCMD_Commands, $newCMD);
    //     $this->id = $command[$id_Commands];
    //     $this->keyCMD = $command[$keyCMD_Commands];
    //     $this->message = $command[$message_Commands];
    //     $this->textMenu = $command[$textMenu_Commands];
    //     $this->action = $command[$action_Commands];
    // }
    // private function unsubscribe(){
    //     //return $this->user->getUsername();
    //     //$this->change = true;
    //     $this->connection->deleteQuery($this->user->getUsername());
    //     return "<b>".$this->user->getUsername()."</b> ".$this->message;
    // }

    // private function suggestion(){
    //     require "connectionDB/Config.php";
    //     $this->connection->alterQuery($table_users, $this->user->getUsername(), $suggest_User, 1);
    //     return $this->message;
    // }

    // private function config(){
    //     require "connectionDB/Config.php";  
    //     if(!$this->user->getWithUsername()){
    //         $this->refresh($config_failed);
    //     }
    //     return $this->message;
    // }

}

?>