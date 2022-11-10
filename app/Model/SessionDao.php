<?php
namespace App\Model;

use Exception;

class SessionDao extends Dao {
    public function createSession($userId) {
        $result = $this->select("INSERT INTO sessions(user_id, valid_until) OUTPUT Inserted.sid 
                                           VALUES (?, DATEADD(DAY, 30, current_timestamp))", [$userId]);
        $sid = $result[0]->sid;
        if (!$sid) {
            throw new Exception("Failed to create session");
        }
        return $sid;
    }
}