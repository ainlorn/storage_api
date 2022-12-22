<?php

namespace App;

use CzProject\GitPhp\Exception;
use CzProject\GitPhp\Git as OriginalGit;
use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository as OriginalRepository;

class Git extends OriginalGit {
    public function open($directory) {
        return new GitRepository(REPOS_PATH . '/' . $directory, $this->runner);
    }
}

class GitRepository extends OriginalRepository {
    public function listFolders() {
        return $this->extractFromCommand(['ls-tree', '-d', '-r', 'HEAD', '--name-only'], function ($value) {
            if (str_starts_with($value, "\"") && str_ends_with($value, "\""))
                $value = substr($value, 1, strlen($value) - 2);
            return $value;
        });
    }

    public function listFiles() {
        return $this->extractFromCommand(['ls-tree', '-r', 'HEAD', '--name-only'], function ($value) {
            if (str_starts_with($value, "\"") && str_ends_with($value, "\""))
                $value = substr($value, 1, strlen($value) - 2);
            return $value;
        });
    }

    public function addNote($message) {
        $this->run('notes', 'add', [
            '-m' => $message,
        ]);
        return $this;
    }

    public function getLastCommitDataForFile($filename) {
        $output = $this->run(
            'log', '--pretty=format:%H^^^^%n%at^^^^%n%s^^^^%n%N%nend', '-n', '1', $filename
        )->getOutputAsString();
        $arr = explode("^^^^\n", $output);
        $notes = explode("\n", $arr[3]);
        $notesMap = [];
        foreach ($notes as $note) {
            $spl = explode(': ', $note, 2);
            $notesMap[$spl[0]] = $spl[1];
        }

        return [
            'id' => $arr[0],
            'timestamp' => intval($arr[1]),
            'message' => $arr[2],
            'author' => $notesMap['Author'] ?? null
        ];
    }

    protected function run(...$args) {
        $result = $this->runner->run($this->repository, $args);

        if (!$result->isOk()) {
            $res_txt = var_export($result, true);
            throw new GitException("Command '{$result->getCommand()}' failed (exit-code {$result->getExitCode()}).\n $res_txt", $result->getExitCode(), NULL, $result);
        }

        return $result;
    }
}
