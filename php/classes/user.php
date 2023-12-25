<?php

enum UserType: string {
    case temp = "temp";
    case admin = "admin";
    case superadmin = "superadmin";
}

class User extends MySQLtoPHPautomapper {
    protected Conn $con;

    public const SESSION_KEY = "user";

    public bool $exists = false;

    public int $id;
    public string $name;
    public string $nickname;
    public string $password;
    public UserType $type;
    public array $lastFingerprint;

    public int $fingerprintGroupId = -1;

    // Automapper settings
    protected string $tableName = "users";
    protected string $index = "id";
    protected array $mapFromTo = [
        "id" => ["id", StoredAs::int],
        "name" => ["name", StoredAs::string],
        "nickname" => ["nickname", StoredAs::string],
        "password" => ["password", StoredAs::string],
        "type" => ["type", StoredAs::enum, "UserType"],
        "last_fingerprint" => ["lastFingerprint", StoredAs::json]
    ];

    public function __construct(array|int $data, bool $shallow = false) {
        global $con;
        $this->con = $con;

        parent::__construct($data);

        if ($shallow) return;

        try {
            $this->complete();
        } catch (Exception) {
            return;
        }

        $this->exists = true;
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

    public static function getUser(): ?User {
        if (!isset($_SESSION[self::SESSION_KEY])) {
            return null;
        }

        $user = new User($_SESSION[self::SESSION_KEY]);

        if (isset($_SESSION["fingerprint"])) $user->update("lastFingerprint", $_SESSION["fingerprint"]);

        if (!$user->exists) {
            return null;
        }

        return $user;
    }

    public static function login(int $userId) {
        $_SESSION[self::SESSION_KEY] = $userId;
    }

    public static function logout() {
        unset($_SESSION[self::SESSION_KEY]);
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
