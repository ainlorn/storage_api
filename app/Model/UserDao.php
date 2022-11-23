<?php
namespace App\Model;

class UserDao extends Dao {
    public function getUserByName($username) {
        $result = $this->select('SELECT * FROM users WHERE username=?', [$username]);
        return $result[0];
    }

    public function getUserBySessionId($sid) {
        if (!preg_match('/^[\da-f]{8}-[\da-f]{4}-[\da-f]{4}-[\da-f]{4}-[\da-f]{12}$/i', $sid))
            return null;
        $result = $this->select('SELECT u.* FROM users u JOIN sessions s on u.id = s.user_id 
           WHERE s.sid=? AND current_timestamp<s.valid_until', [$sid]);
        if (count($result) === 0)
            return null;
        return $result[0];
    }
}