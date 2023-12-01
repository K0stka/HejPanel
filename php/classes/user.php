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
    public UserType $type;

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
        $this->type = UserType::from($user["type"]);

        $this->notificationManager = new NotificationManager($this);
    }

    public function __toString(): string {
        return $this->name ?? "Unknown user error";
    }

    public static function getUser(): User|bool {
        if (!isset($_SESSION[self::$sessionKey])) {
            return false;
        }

        $user = new User($_SESSION[self::$sessionKey]);

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

    public static function register(string $name, string $nickname, string $passwordHash, UserType $userType) {
        global $con;
        $con->query("INSERT INTO users (name, nickname, password, type) VALUES (", [$name], ",", [$nickname], ",", [$passwordHash], ", ", [$userType->value], ")");
    }
}
