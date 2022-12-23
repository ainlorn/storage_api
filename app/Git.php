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

    public function getObjectId($rev, $filename) {
        return $this->run('rev-parse', '--verify', "$rev:$filename")->getOutputAsString();
    }

    public function getBlobStream($objId) {
        $descriptorspec = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $pipes = [];
        $process = proc_open("git show $objId", $descriptorspec, $pipes, $this->getRepositoryPath(), NULL, [
            'bypass_shell' => TRUE,
        ]);

        if (!$process) {
            throw new GitException("Executing of git show failed.");
        }

        // Reset output and error
        stream_set_blocking($pipes[1], FALSE);
        stream_set_blocking($pipes[2], FALSE);

        $stderr = '';
        $resource = fopen('php://temp', 'rw+');

        while (TRUE) {
            // Read standard output
            $stdoutOutput = stream_get_contents($pipes[1], 2 * 1024 * 1024);

            if (is_string($stdoutOutput)) {
                fwrite($resource, $stdoutOutput);
            }

            // Read error output
            $stderrOutput = stream_get_contents($pipes[2]);

            if (is_string($stderrOutput)) {
                $stderr .= $stderrOutput;
            }

            // We are done
            if ((feof($pipes[1]) || $stdoutOutput === FALSE) && (feof($pipes[2]) || $stderrOutput === FALSE)) {
                break;
            }
        }

        $returnCode = proc_close($process);

        if ($returnCode != 0) {
            throw new GitException("git show failed with code $returnCode. $stderr");
        }

        rewind($resource);
        return $resource;
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
