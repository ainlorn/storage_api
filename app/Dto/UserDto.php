<?php

namespace App\Dto;

class UserDto {
    public int $id;
    public string $username;
    public string $real_name;
    public int $role_id;

    /**
     * @param int $id
     * @param string $username
     * @param string $real_name
     * @param int $role_id
     */
    public function __construct(int $id, string $username, string $real_name, int $role_id) {
        $this->id = $id;
        $this->username = $username;
        $this->real_name = $real_name;
        $this->role_id = $role_id;
    }

    public static function fromSql($sqlObj): UserDto {
        return new UserDto($sqlObj->id, $sqlObj->username, $sqlObj->real_name, $sqlObj->role_id);
    }
}