<?php

namespace App\Dto;

class RepositoryLockDto {
    public string $user;
    public int $timestamp;

    /**
     * @param string $user
     * @param int $timestamp
     */
    public function __construct(string $user, int $timestamp) {
        $this->user = $user;
        $this->timestamp = $timestamp;
    }
}