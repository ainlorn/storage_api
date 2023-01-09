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

    public function getRepositoryById(int $id) {
        return $this->selectOne("SELECT * FROM repositories WHERE id=?", [$id]);
    }

    public function getLocks(int $repoId) {
        return $this->select(
            "SELECT \"filename\", user_id, created_on, username, real_name, role_id 
                    FROM repository_locks r JOIN users u on u.id = r.user_id WHERE r.repo_id=?",
            [$repoId]
        );
    }

    public function userHasAccessToRepository(int $userId, int $repoId) {
        return $this->selectOne(
            "SELECT COUNT(*) AS result FROM repository_users WHERE user_id=? AND repo_id=?",
            [$userId, $repoId]
        )->result == 1;
    }

    public function isFileLocked(int $repoId, string $filename) {
        return $this->selectOne(
            "SELECT COUNT(*) AS result FROM repository_locks WHERE repo_id=? AND \"filename\"=?",
            [$repoId, $filename]
        )->result == 1;
    }

    public function createLock(int $repoId, string $filename, int $userId) {
        $this->select(
            "INSERT INTO repository_locks(repo_id, \"filename\", user_id, created_on) 
                    VALUES (?, ?, ?, current_timestamp)",
            [$repoId, $filename, $userId]
        );
    }

    public function getLockByFilename(int $repoId, string $filename) {
        return $this->selectOne(
            "SELECT * FROM repository_locks WHERE repo_id=? AND \"filename\"=?",
            [$repoId, $filename]
        );
    }

    public function removeLock(int $repoId, string $filename) {
        $this->select("DELETE FROM repository_locks WHERE repo_id=? AND filename=?", [$repoId, $filename]);
    }

    public function getRepositoryByPath(string $path) {
        return $this->selectOne("SELECT * FROM repositories WHERE path=?", [$path]);
    }

    public function createRepository(string $name, string $path) {
        $this->select("INSERT INTO repositories(name, path) VALUES (?, ?)", [$name, $path]);
    }
}