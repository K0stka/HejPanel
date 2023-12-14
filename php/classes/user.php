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
            $user = $con->select(true, "users")->where(["id" => $searchParams])->fetchRow();
        } elseif (is_array($searchParams) && count($searchParams) != 0) {
            $user = $con->select(true, "users")->where($searchParams)->fetchRow();
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
        return array_map(fn ($e) => json_decode($e["fingerprint"], true), $this->con->select("fingerprint", "sessions")->where(["user" => $this->id])->fetchAll());
    }

    public function updateLastFingerprint(array $fingerprint) {
        $this->con->update("users", ["last_fingerprint" => utf8json($fingerprint)])->where(["id" => $this->id])->execute();
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
        return $con->insert("users", ["name" => $name, "nickname" => $nickname, "password" => $passwordHash, "type" => $userType->value]);
    }

    /** @return User[] */
    public static function getFingerprintToUsersMap(): array {
        global $con;
        $map = $con->select(["last_fingerprint", "GROUP_CONCAT(id)" => "IDS"], "users")->groupBy("last_fingerprint")->orderBy("id", Order::minToMax)->fetchAll();
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
