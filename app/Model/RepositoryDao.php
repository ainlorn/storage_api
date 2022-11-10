<?php

namespace App\Model;

class RepositoryDao extends Dao {
    public function getAllRepositories() {
        return $this->select("SELECT * FROM repositories");
    }

    public function getLocks($repoId) {
        return $this->select(
            "SELECT \"filename\", user_id, created_on, username, real_name, role_id 
                    FROM repository_locks r JOIN users u on u.id = r.user_id WHERE r.repo_id=?",
            [$repoId]
        );
    }
}