<?php

namespace App\Dto;

class RepositoryDto {
    public int $id;
    public string $name;
    public array $files;
    public array $locks;

    /**
     * @param int $id
     * @param string $name
     * @param string[] $files
     * @param RepositoryLockDto[] $locks
     */
    public function __construct(int $id, string $name, array $files, array $locks) {
        $this->id = $id;
        $this->name = $name;
        $this->files = $files;
        $this->locks = $locks;
    }
}