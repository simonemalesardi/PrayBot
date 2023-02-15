<?php

class Database{
    private $connection;  

    
    function __construct($pdo) {
        $this->connection = $pdo;
    }

    public function getConnection(){
        return $this->connection;
    }

    public function searchCommand($text){ 
        $sql = "SELECT * FROM commands WHERE command = :command";
        $query = $this->connection->prepare($sql);
        $query->execute(['command' => $text]);
        $command = $query->fetchAll();

        if (sizeof($command) == 1) {
            $command = $command[0];
            return $command;
        }
        return NULL;
    }

    public function makeQuery($query){
        
    }

    public function insertQuery($value, $chat_id){
        $date = new DateTime();
        $converted_date = $date->format('Y-m-d H-i-s');
        $sql = "INSERT INTO user(IDChat, Username, SubscribtionDate, Active, Menu, Suggest) VALUES('$chat_id', '$value', '$converted_date', 1, 1, 0)";
        $stmt = $this->connection->prepare($sql);
        //$stmt->bindParam(':nome', $nome, PDO::PARAM_STR);
        $stmt->execute();
    }


}

?>
