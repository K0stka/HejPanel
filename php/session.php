<?php
class MysqlSession implements SessionHandlerInterface {
    private Conn $con;
    private int $lifetime;

    private array $saveSeparately = [
        "user" => "user",
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
        $data = $this->con->query("SELECT " . join("", array_map(fn ($e) => $e . ", ", $this->saveSeparately)) . " data FROM sessions WHERE session_id = ", [$id], " AND expires > ", [time()])->fetchRow();
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
        $this->con->query("DELETE FROM sessions WHERE session_id =", [$id]);
        return true;
    }

    public function gc(int $maxlifetime): bool {
        $this->con->query("DELETE FROM sessions WHERE expires < ", [time()]);
        return true;
    }
}

$lifetime = 7 * 24 * 60 * 60; // in seconds

session_set_save_handler(new MysqlSession($lifetime), true);

ini_set("session.use_only_cookies", 1);
ini_set("session.use_strict_mode", 1);

session_set_cookie_params([
    "lifetime" => $lifetime,
    // "domain" => $prefix, FOR SOME REASON BREAKS EVERYTHING?
    "path" => "/",
    // "secure" => true, // Breaks everything when using localhost over IP
    "httponly" => true,
]);

session_start();

function endSession() {
    session_unset();
    session_destroy();
}
