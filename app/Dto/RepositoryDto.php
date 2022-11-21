<?php

namespace App\Dto;

class RepositoryDto {
    public int $id;
    public string $name;
    public array $files;

    /**
     * @param int $id
     * @param string $name
     * @param string[] $files
     */
    public function __construct(int $id, string $name, array $files) {
        $this->id = $id;
        $this->name = $name;
        $this->files = $files;
    }
}