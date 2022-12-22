<?php

namespace App\Dto;

class CommitInfoShortDto {
    public string $id;
    public int $timestamp;
    public string $message;
    public ?string $user;

    /**
     * @param string $id
     * @param int $timestamp
     * @param string $message
     * @param string|null $user
     */
    public function __construct(string $id, int $timestamp, string $message, ?string $user) {
        $this->id = $id;
        $this->timestamp = $timestamp;
        $this->message = $message;
        $this->user = $user;
    }
}