<?php

namespace App\Dto;

class RepositoryListResponse extends BaseResponse {
    public array $repositories;

    /**
     * @param RepositoryDto[] $repositories
     */
    public function __construct(array $repositories) {
        parent::__construct(null);
        $this->repositories = $repositories;
    }
}