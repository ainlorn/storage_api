<?php

namespace App\Dto;

class RepositoryFileDto {
    public string $name;
    public string $type;
    public ?RepositoryLockDto $lock;
    public CommitInfoShortDto $last_commit;

    /**
     * @param string $name
     * @param RepositoryLockDto|null $lock
     * @param CommitInfoShortDto $last_commit
     */
    public function __construct(string $name, ?RepositoryLockDto $lock, CommitInfoShortDto $last_commit) {
        $this->name = $name;
        $this->type = "file";
        $this->lock = $lock;
        $this->last_commit = $last_commit;
    }
}