<?php

class User{
    private $username;
    private $connection; 
    private $chat_id;
    private $withUsername; //attributo per la configurazione di un utente, se true si configura, altrimenti no
    private $user_row;
    private $is_new;
    private $action;
    private $menu;
    private $privileges;
    
    public function __construct($chat_id, $username, &$connection) {
        $this->chat_id=$chat_id;
        $this->username = $username;
        $this->connection = $connection;
        $this->is_new = false;
        $this->getUser();
    }

    //controlla se lo user esiste: se non c'è crea
    private function getUser(){
        $sql = "SELECT * FROM users WHERE chat_id = :chat_id";
        $query = $this->connection->prepare($sql);
        $query->execute(['chat_id' => $this->chat_id]);
        $user = $query->fetchAll();
        
        if (sizeof($user)==0){
            $this->insertUser();

            $sql = "SELECT * FROM users WHERE chat_id = :chat_id";
            $query = $this->connection->prepare($sql);
            $query->execute(['chat_id' => $this->chat_id]);
            $user = $query->fetchAll();
        }

        $this->action = $user[0]['action'];
        $this->menu = $user[0]['menu'];
        $this->privileges = $user[0]['is_admin'];
        //$this->user_row = $user;
    }

    private function insertUser(){
        $date = new DateTime();
        $converted_date = $date->format('Y-m-d H-i-s');
        $sql = "INSERT INTO users (username, chat_id, created_at, menu) VALUES('$this->username','$this->chat_id', '$converted_date', 0)";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute();
        
        $this->is_new = true;
    }

    public function getAction(){
        return $this->action;
    }
    
    public function getPrivileges(){
        return $this->privileges;
    }

    public function getUsername() : string{
        return $this->username;
    }

    public function getWithUsername() {
        return $this->withUsername;
    }

    public function getMenu(){
        return $this->menu;
    }

    public function isNew(){
        return $this->is_new;
    }

    public function getChatID(){
        return $this->chat_id;
    }

}


?>