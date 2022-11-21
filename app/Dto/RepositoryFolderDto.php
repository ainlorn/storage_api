<?php

namespace App\Dto;

class RepositoryFolderDto {
    public string $name;
    public string $type;
    public array $files;

    /**
     * @param string $name
     * @param array $files
     */
    public function __construct(string $name, array $files) {
        $this->name = $name;
        $this->type = "folder";
        $this->files = $files;
    }
}