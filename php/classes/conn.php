<?php
enum MYSQL {
    case NULL;
}

enum Order: string {
    case minToMax = "ASC";
    case maxToMin = "DESC";
}

class Conn {
    private mysqli $mysqli;
    public mysqli_stmt|null $stmt = null;

    private array $queryTemp = [];

    public function __construct(string $host, string $username, string $password, string $databaseName) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $this->mysqli = new mysqli($host, $username, $password, $databaseName);
            $this->mysqli->set_charset("utf8mb4");
        } catch (Exception $e) {
            error_log($e->getMessage());
            if (DEV || true) {
                global $query;
                printError("Error querying the database", ["Message" => $e->getMessage()]);
            } else {
                printError("Error connecting to the database");
            }
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
        $exceptionHandler = function (Exception $e, array $args, string $query, array $params) {
            if (DEV || true) {
                printError("Error querying the database", ["Message" => $e->getMessage(), "Args" => $args, "Query" => $query, "Params" => $params]);
            } else {
                printError("Error querying the database");
            }
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
            $exceptionHandler($e, $args, $query, $params);
        }

        if ($params) {
            try {
                $this->stmt->bind_param($paramsTypes, ...$params);
            } catch (Exception $e) {
                $exceptionHandler($e, $args, $query, $params);
            }
        }

        try {
            $this->stmt->execute();
        } catch (Exception $e) {
            $exceptionHandler($e, $args, $query, $params);
        }

        return $this;
    }

    public function execute(): Conn {
        $this->query(...$this->queryTemp);

        $this->queryTemp = [];

        return $this;
    }

    public function fetchAll(): array {
        if (!empty($this->queryTemp)) $this->execute();
        return $this->stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    }

    public function fetchRow(): array {
        if (!empty($this->queryTemp)) $this->execute();
        return $this->stmt->get_result()->fetch_assoc() ?? [];
    }

    public function fetchColumn(): array {
        if (!empty($this->queryTemp)) $this->execute();
        return array_map(fn ($e) => reset($e), $this->stmt->get_result()->fetch_all(MYSQLI_ASSOC));
    }

    public function insertID(): int {
        return $this->stmt->insert_id;
    }

    public function select(string|array|bool $what, string $from): Conn {
        if (is_bool($what)) $whatQuery = "*";
        elseif (is_array($what)) {
            $whatQuery = [];
            foreach ($what as $key => $id) {
                if (is_numeric($key)) {
                    $whatQuery[] = "$id";
                } else {
                    $whatQuery[] = "$key AS $id";
                }
            }
            $whatQuery = implode(", ", $whatQuery);
        } elseif (is_string($what)) $whatQuery = $what;

        $this->queryTemp = ["SELECT $whatQuery FROM $from "];

        return $this;
    }

    public function update(string $what, array $values): Conn {
        $this->queryTemp = ["UPDATE $what SET "];

        foreach ($values as $key => $value) {
            $this->queryTemp[] = "$key = ";
            $this->queryTemp[] = [$value];
            $this->queryTemp[] = ", ";
        }

        array_pop($this->queryTemp);

        return $this;
    }

    public function insert(string $into, array $values): int {
        $this->queryTemp = ["INSERT INTO $into " . join(", ", array_keys($values)) . " VALUES ("];

        foreach ($values as $value) {
            $this->queryTemp[] = [$value];
            $this->queryTemp[] = ", ";
        }

        array_pop($this->queryTemp);

        $this->queryTemp[] = ")";

        $this->query($this->queryTemp);
        $this->queryTemp = [];

        return $this->insertID();
    }

    public function delete(string $from): Conn {
        $this->queryTemp = ["DELETE FROM $from "];

        return $this;
    }

    public function where(array $condition): Conn {
        $this->queryTemp[] = "WHERE ";

        foreach ($condition as $key => $value) {
            $this->queryTemp[] = "$key = ";
            $this->queryTemp[] = [$value];
            $this->queryTemp[] = " AND ";
        }

        array_pop($this->queryTemp);

        return $this;
    }

    public function groupBy(string $what): Conn {
        $this->queryTemp[] = "GROUP BY $what ";

        return $this;
    }

    public function orderBy(string $what, Order $order): Conn {
        $this->queryTemp[] = "ORDER BY $what $order->value";

        return $this;
    }

    public function limit(int $limit): Conn {
        $this->queryTemp[] = " LIMIT $limit";

        return $this;
    }

    public function addSQL(string|array ...$query): Conn {
        array_push($this->queryTemp, ...[...$query, " "]);

        return $this;
    }

    public function __destruct() {
        if ($this->stmt) {
            $this->stmt->close();
        }
    }
}
