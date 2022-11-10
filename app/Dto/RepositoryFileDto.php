<?php

namespace App\Dto;

class RepositoryFileDto {
    public string $name;
    public ?RepositoryLockDto $lock;

    /**
     * @param string $name
     * @param RepositoryLockDto|null $lock
     */
    public function __construct(string $name, ?RepositoryLockDto $lock) {
        $this->name = $name;
        $this->lock = $lock;
    }
}