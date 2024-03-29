<?php
class MysqlSession implements SessionHandlerInterface {
    private Conn $con;
    private int $lifetime;

    // PHP Key => MySQL Key
    private array $saveSeparately = [
        User::SESSION_KEY_ID => "user",
        User::SESSION_KEY_AUTH => "auth",
        "subscription" => "subscription",
        "fingerprint" => "fingerprint",
    ];

    public function __construct(int $lifetime) {
        global $con;
        $this->con = $con;

        $this->lifetime = $lifetime;
    }

    public function open(string $savePath, string $sessionName): bool {
        return true;
    }

    public function close(): bool {
        return true;
    }

    public function read(string $id): string {
        $data = $this->con->select(["data", ...$this->saveSeparately], "sessions")->where(["session_id" => $id])->addSQL("AND expires > ", [time()])->fetchRow();
        if (empty($data) || $data["data"] == null) $data["data"] = "";
        return $data["data"];
    }

    public function write(string $id, string $data): bool {
        $separateData = [];
        foreach ($this->saveSeparately as $sessionKey => $dbKey) {
            $separateData[] = ", $dbKey = ";
            if (isset($_SESSION[$sessionKey])) {
                if (is_array($_SESSION[$sessionKey])) {
                    $separateData[] = [utf8json($_SESSION[$sessionKey])];
                } else {
                    $separateData[] = [$_SESSION[$sessionKey]];
                }
            } else {
                $separateData[] = [MYSQL::NULL];
            }
        }

        $expires = time() + $this->lifetime;

        $this->con->query(...["REPLACE INTO sessions SET session_id = ", [$id], ", expires = $expires", ...$separateData, ", data = ", [$data]]);

        return true;
    }

    public function destroy(string $id): bool {
        $this->con->delete("sessions")->where(["session_id" => $id])->execute();
        return true;
    }

    #[\ReturnTypeWillChange]
    public function gc(int $maxlifetime): bool {
        $this->con->delete("sessions")->addSQL("WHERE expires < ", [time()])->execute();
        return true;
    }
}

$lifetime = 7 * 24 * 60 * 60; // in seconds

session_set_save_handler(new MysqlSession($lifetime), true);

ini_set("session.use_only_cookies", 1);
ini_set("session.use_strict_mode", 1);

session_set_cookie_params([
    "lifetime" => $lifetime,
    // "domain" => $rootDir, // Breaks because browsers do not thing it's the correct address?
    "path" => "/",
    "secure" => true, // Breaks if using localhost over IP (or anything without HTTPS)
    "httponly" => true,
]);

session_start();

function endSession() {
    session_unset();
    session_destroy();
}
