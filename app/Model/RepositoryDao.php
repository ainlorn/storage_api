<?php

namespace App\Model;

class RepositoryDao extends Dao {
    public function getAllRepositories() {
        return $this->select("SELECT * FROM repositories");
    }

    public function getRepositoriesForUser(int $id) {
        return $this->select("SELECT r.* FROM repositories r JOIN repository_users u on r.id = u.repo_id 
                                        WHERE u.user_id=?", [$id]);
    }

    public function getLocks(int $repoId) {
        return $this->select(
            "SELECT \"filename\", user_id, created_on, username, real_name, role_id 
                    FROM repository_locks r JOIN users u on u.id = r.user_id WHERE r.repo_id=?",
            [$repoId]
        );
    }
}