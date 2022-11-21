<?php

namespace App\Dto;

class RepositoryFileDto {
    public string $name;
    public string $type;
    public bool $lock;
    public ?string $user;

    /**
     * @param string $name
     * @param bool $lock
     * @param string|null $user
     */
    public function __construct(string $name, bool $lock, ?string $user) {
        $this->name = $name;
        $this->lock = $lock;
        $this->type = "file";
        $this->user = $user;
    }
}