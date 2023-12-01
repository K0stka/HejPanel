<?php

/*

EXAMPLE USECASE

($con = new Conn(...);)

$con->query("UPDATE users SET name =", ["s", "KonzultantKteryJeTajneKlient"], "WHERE id =", ["i", 17]);

$row = $con->query("SELECT * FROM users WHERE id =", ["i", 17])->fetchRow();

$all = $con->query("SELECT * FROM users WHERE name =", ["Konzultant"])->fetchAll(); // Type s automaticaly assumed

*/

enum MYSQL {
    case NULL;
}

class Conn {
    private mysqli $mysqli;
    public mysqli_stmt|null $stmt = null;

    public function __construct(string $host, string $username, string $password, string $databaseName) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->mysqli = new mysqli($host, $username, $password, $databaseName);
            $this->mysqli->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log($e->getMessage());
            exit('Error connecting to database:<br>' . $e->getMessage());
        }
    }

    public function query(string|array ...$args): Conn {
        if ($this->stmt) {
            $this->stmt->close();
            $this->stmt = null;
        }

        $query = "";
        $paramsTypes = "";
        $params = [];
        $exceptionHandler = function (Exception $e) {
            die("MYSQLI QUERY ERROR:\n $e");
        };
        foreach ($args as $arg) {
            if (is_string($arg)) {
                $query .= $arg;
            } elseif (is_array($arg)) {
                if (isset($arg[0]) && isset($arg[1])) {
                    $query .= " ? ";
                    $paramsTypes .= $arg[0];
                    array_push($params, $arg[1]);
                } elseif (isset($arg[0])) {
                    if ($arg[0] === null || $arg[0] == MYSQL::NULL) {
                        $query .= " NULL ";
                    } else {
                        $query .= " ? ";
                        $paramsTypes .= "s";
                        array_push($params, $arg[0]);
                    }
                } else {
                    die("Invalid query arguments");
                }
            } elseif (is_callable($arg)) {
                $exceptionHandler = $arg;
            }
        }

        try {
            $this->stmt = $this->mysqli->prepare($query);
        } catch (Exception $e) {
            $exceptionHandler($e);
        }

        if ($params) {
            $this->stmt->bind_param($paramsTypes, ...$params);
        }

        try {
            $this->stmt->execute();
        } catch (Exception $e) {
            $exceptionHandler($e);
        }

        return $this;
    }

    public function fetchAll(): array {
        return $this->stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchRow(): array {
        return $this->stmt->get_result()->fetch_assoc() ?? [];
    }

    public function __destruct() {
        if ($this->stmt) {
            $this->stmt->close();
        }
    }
}
