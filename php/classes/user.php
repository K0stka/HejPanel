<?php

enum UserType: string {
    case temp = "temp";
    case admin = "admin";
    case superadmin = "superadmin";
}

class User {
    private Conn $con;

    private static string $sessionKey = "user";

    public bool $exists = false;

    public int $id;
    public string $name;
    public string $nickname;
    public string $password;
    public UserType $type;
    public array $lastFingerprint;

    public int $fingerprintGroupId = -1;

    public NotificationManager $notificationManager;

    public function __construct(array|int $searchParams) {
        global $con;
        $this->con = $con;

        if (is_int($searchParams)) {
            $user = $con->query("SELECT * FROM users WHERE id = ", ["i", $searchParams])->fetchRow();
        } elseif (is_array($searchParams) && count($searchParams) != 0) {
            $query = ["SELECT * FROM users WHERE "];
            foreach ($searchParams as $searchKey => $searchParam) {
                array_push($query, "$searchKey = ");
                array_push($query, [$searchParam]);
                array_push($query, " AND ");
            }
            array_pop($query);
            $user = $con->query(...$query)->fetchRow();
        } else {
            return;
        }

        if (count($user) == 0)
            return;

        $this->exists = true;

        $this->id = $user["id"];
        $this->name = $user["name"];
        $this->nickname = $user["nickname"];
        $this->password = $user["password"];
        $this->type = UserType::from($user["type"]);
        $this->lastFingerprint = json_decode($user["last_fingerprint"] ?? "[]", true);

        $this->notificationManager = new NotificationManager($this);
    }

    public function __toString(): string {
        return $this->name ?? "Unknown user error";
    }

    public function renderChip() {
?>
        <div class="user-chip auto-color" style="background: <?= assignColorById($this->fingerprintGroupId) ?>;">
            <?= $this->name ?> (skupina <?= $this->fingerprintGroupId ?>)
            <div class="tooltip" style="white-space: nowrap;">
                IP: <?= $this->lastFingerprint["ip"] ?><br>
                Zařízení: <?= $this->lastFingerprint["mobile"] == "true" ? $this->lastFingerprint["model"] : "Neznámé" ?><br>
                Architektura: <?= $this->lastFingerprint["architecture"] ?><br>
                Platforma: <?= $this->lastFingerprint["platform"] . " v" . $this->lastFingerprint["platformVersion"] ?><br>
                User agenti:<br><?= join(",<br>", array_map(fn ($e) => "&nbsp;&nbsp;" . $e["brand"] . " v" . $e["version"], $this->lastFingerprint["brands"])) ?>
            </div>
        </div>
<?php
    }

    public function getFingerprints(): array {
        return array_map(fn ($e) => json_decode($e["fingerprint"], true), $this->con->query("SELECT fingerprint FROM sessions WHERE user = ", [$this->id])->fetchAll());
    }

    public function updateLastFingerprint(array $fingerprint) {
        $this->con->query("UPDATE users SET last_fingerprint = ", [utf8json($fingerprint)], " WHERE id = ", [$this->id]);
    }

    public static function getUser(): User|bool {
        if (!isset($_SESSION[self::$sessionKey])) {
            return false;
        }

        $user = new User($_SESSION[self::$sessionKey]);

        if (isset($_SESSION["fingerprint"])) $user->updateLastFingerprint($_SESSION["fingerprint"]);

        if (!$user->exists) {
            return false;
        }

        return $user;
    }

    public static function login(int $userId) {
        $_SESSION[self::$sessionKey] = $userId;
    }

    public static function logout() {
        unset($_SESSION[self::$sessionKey]);
    }

    public static function register(string $name, string $nickname, string $passwordHash, UserType $userType): int {
        global $con;
        $con->query("INSERT INTO users (name, nickname, password, type) VALUES (", [$name], ",", [$nickname], ",", [$passwordHash], ", ", [$userType->value], ")");
        return $con->stmt->insert_id;
    }

    /** @return User[] */
    public static function getFingerprintToUsersMap(): array {
        global $con;
        $map = $con->query("SELECT last_fingerprint, GROUP_CONCAT(id) AS IDS FROM users GROUP BY last_fingerprint ORDER BY id")->fetchAll();
        array_walk($map, fn (&$e, $f) => $e = [
            "id" => $f,
            "fingerprint" => $e["last_fingerprint"],
            "users" => json_decode("[" . $e["IDS"] . "]", true),
        ]);
        return $map;
    }

    public function categorizeByFingerprint(array $fingerprintMap): int {
        if ($this->fingerprintGroupId != -1)
            return $this->fingerprintGroupId;

        foreach ($fingerprintMap as $group) {
            if (in_array($this->id, $group["users"])) {
                $this->fingerprintGroupId = $group["id"];
                return $group["id"];
            }
        }
        $this->fingerprintGroupId = -1;
        return -1;
    }
}
