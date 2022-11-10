<?php
namespace App\Model;

class UserDao extends Dao {
    public function getUserByName($username) {
        $result = $this->select('SELECT * FROM users WHERE username=?', [$username]);
        return $result[0];
    }
}