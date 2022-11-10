<?php

namespace App\Dto;

class RepositoryLockDto {
    public string $filename;
    public UserDto $user;
    public bool $this_user;
    public int $lock_timestamp;

    /**
     * @param string $filename
     * @param UserDto $user
     * @param bool $this_user
     * @param int $lock_timestamp
     */
    public function __construct(string $filename, UserDto $user, bool $this_user, int $lock_timestamp) {
        $this->filename = $filename;
        $this->user = $user;
        $this->this_user = $this_user;
        $this->lock_timestamp = $lock_timestamp;
    }
}