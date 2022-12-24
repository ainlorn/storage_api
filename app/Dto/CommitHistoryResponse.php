<?php

namespace App\Dto;

class CommitHistoryResponse extends BaseResponse {
    public array $commits;

    /**
     * @param CommitInfoShortDto[] $commits
     */
    public function __construct(array $commits) {
        parent::__construct(null);
        $this->commits = $commits;
    }
}