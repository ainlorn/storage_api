<?php

namespace App\Dto;

class AuthResponse extends BaseResponse {
    public UserDto $user;
    public string $session_id;

    /**
     * @param UserDto $user
     * @param string $session_id
     */
    public function __construct(UserDto $user, string $session_id) {
        parent::__construct(null);
        $this->user = $user;
        $this->session_id = $session_id;
    }
}