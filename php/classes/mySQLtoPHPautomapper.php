
<?php

enum StoredAs {
    case string;
    case int;
    case datetime;
    case bool;
    case json;
    case enum;
    case foreignId;
}

class MySQLtoPHPautomapper {
    protected Conn $con;

    protected string $tableName = "";
    protected string $index = "";

    protected array $mapFromTo = [];
    private array $reverseMap = [];

    protected bool $updateAllOnDestroy = false;

    protected function __construct(&$data) {
        if (is_int($data)) $data = [$this->index => $data];

        foreach ($this->mapFromTo as $key => $value) {
            $this->reverseMap[$value[0]] = [$key, $value[1], $value[2] ?? null];
        }

        $this->applySerializedDataToPHPkeys($data);
    }

    private function applySerializedDataToPHPkeys(array $data) {
        foreach ($data as $PHPkey => $value) {
            $reverseMapping = $this->reverseMap[$PHPkey] ?? null;

            if (!$reverseMapping) return;

            $this->$PHPkey = self::toPHP($reverseMapping[1], $value, $reverseMapping[2] ?? null);
        }
    }

    private function serializeToMySQLValuesAndKeys() {
        $serialized = [];
        foreach ($this->reverseMap as $PHPKey => $reverseMapping) {
            if (isset($this->$PHPKey))
                $serialized[$reverseMapping[0]] = self::toMySQL($reverseMapping[1], $this->$PHPKey);
        }
        return $serialized;
    }

    public function complete(string ...$requiredPHPKeys) {
        if (!empty($requiredPHPKeys)) {
            foreach ($requiredPHPKeys as $requiredPHPKey)
                $missing = [$this->reverseMap[$requiredPHPKey][0] => $requiredPHPKey];
        } else {
            $missing = [];

            foreach ($this->mapFromTo as $mysqlKey => $mapTo)
                if (!isset($this->{$mapTo[0]})) $missing[$mysqlKey] = $mapTo[0];

            if (count($missing) == 0) return;
        }


        $index = $this->index;
        if (isset($this->$index)) {
            $completeData = $this->con->select($missing, $this->tableName)->where([$index => $this->$index])->fetchRow();
        } else {
            $completeData = $this->con->select($missing, $this->tableName)->where($this->serializeToMySQLValuesAndKeys())->fetchAll();

            if (empty($completeData) || count($completeData) > 1) throw new Exception("Cannot complete object");

            $completeData = $completeData[0];
        }

        $this->applySerializedDataToPHPkeys($completeData);
    }

    public function updateAll() {
        if (!isset($this->{$this->index})) $this->complete($this->index);

        $this->con->update($this->tableName, $this->serializeToMySQLValuesAndKeys())->where([$this->index => $this->{$this->index}])->execute();
    }

    public function delete() {
        if (!isset($this->{$this->index})) $this->complete($this->index);
        $this->con->delete(self::$tableName)->where([$this->index => $this->{$this->index}])->execute();
    }

    public function get($name) {
        if (!isset($this->$name) && isset($this->reverseMap[$name])) $this->complete($name);

        return $this->$name ?? null;
    }

    public function update($name, $value) {
        $this->$name = $value;
        if (($reverseMapping = $this->reverseMap[$name]))
            $this->con->update($this->tableName, [$reverseMapping[0] => self::toMySQL($reverseMapping[1], $value)])->where([$this->index => $this->{$this->index}])->execute();
    }

    public function __destruct() {
        if (!$this->updateAllOnDestroy) return;

        $this->updateAll();
    }

    private static function toMySQL(StoredAs $type, $what) {
        switch ($type) {
            case StoredAs::string:
                return $what;
            case StoredAs::int:
                return $what;
            case StoredAs::datetime:
                return $what->format('Y-m-d H:i:s');
            case StoredAs::bool:
                return $what ? 1 : 0;
            case StoredAs::json:
                return utf8json($what);
            case StoredAs::enum:
                return $what->value;
            case StoredAs::foreignId:
                return $what->id;
        }
    }

    private static function toPHP(StoredAs $type, $what, $additionalParam = null) {
        switch ($type) {
            case StoredAs::string:
                return $what;
            case StoredAs::int:
                return intval($what);
            case StoredAs::datetime:
                return DateTime::createFromFormat('Y-m-d H:i:s', $what);
            case StoredAs::bool:
                return $what;
            case StoredAs::json:
                return json_decode($what ?? "[]", true);
            case StoredAs::enum:
                return eval("return $additionalParam::from(\"$what\");");
            case StoredAs::foreignId:
                return eval("return new $additionalParam($what ?? -1);");
        }
    }
}
