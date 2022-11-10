<?php

namespace App\Dto;

class BaseResponse {
    public ?string $message;

    /**
     * @param string|null $message
     */
    public function __construct(?string $message) {
        $this->message = $message;
    }
}