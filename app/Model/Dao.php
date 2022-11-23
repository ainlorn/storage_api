<?php
namespace App\Model;

use Exception;

class Dao {
    protected $connection = null;

    public function __construct() {
        try {
            $connInfo = array("Database"=>DB_DATABASE, "UID"=>DB_USER, "PWD"=>DB_PASSWORD, "CharacterSet"=>"UTF-8",
                "TrustServerCertificate"=>"yes");
            $this->connection = sqlsrv_connect(DB_SERVER, $connInfo);

            if (!$this->connection) {
                throw new Exception("Could not connect to database.");
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    public function __destruct() {
        if ($this->connection)
            sqlsrv_close($this->connection);
    }


    public function select($query = "", $params = []) {
        try {
            $stmt = $this->executeStatement($query, $params);
            $result = array();

            $i = 0;
            while ($obj = sqlsrv_fetch_object($stmt)) {
                $result[$i] = $obj;
                $i++;
            }

            sqlsrv_free_stmt($stmt);

            return $result;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }

    private function executeStatement($query = "", $params = []) {
        try {
            $stmt = sqlsrv_prepare($this->connection, $query, $params);

            if ($stmt === false) {
                throw new Exception("Unable to do prepared statement: " . $query);
            }

            if(sqlsrv_execute($stmt) === false) {
                throw new Exception("SQL execution failed: " . $query);
            }

            return $stmt;
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
    }
}