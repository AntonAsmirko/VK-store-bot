<?php

namespace App\repository;

require_once "vendor/autoload.php";

class AdminRepository {
    private $PDO;

    private $admins;

    public function __construct($pdo, $admins){
        $this->PDO = $pdo;
        $this->admins = $admins;
    }

    public function isAdmin($user_id){
        $res = $this->PDO->query("SELECT ID FROM user_admin WHERE ID = $user_id AND IS_ADMIN = true");
        $admins = $res->fetchAll();
        foreach($admins as $item){
            if($item[0] == $user_id){
                return true;
            }
        }
        return false;
    }

    public function loadAdmins(){
        $this->PDO->query("CREATE TABLE IF NOT EXISTS user_admin (ID INT NOT NULL, IS_ADMIN BOOLEAN NOT NULL)");
        $admins = explode(",", $this->admins);
        foreach($admins as $admin){
            $int_id = intval($admin);
            if(!$this->isAdmin($int_id)){
                $this->PDO->query("INSERT INTO user_admin (ID, IS_ADMIN) VALUES ($int_id, true)");
            }
        }
    }
}

?>