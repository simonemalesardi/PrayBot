<?php

class Database{
    private $connection;  
    
    function __construct($pdo) {
        $this->connection = $pdo;
    }

    public function searchCommand($text){
        $sql = "SELECT * FROM commands WHERE command = :command";
        $query = $this->connection->prepare($sql);
        $query->execute(['command' => $text]);

        $command = $query->fetchAll();
        if (sizeof($command) == 1) {
            $command = $command[0];
            return $command['action'];
        }
        return NULL;
    }
}

?>
