<?php

class ActionManager {
    public User $user;
    private Conn $con;

    public int|null $id;
    public string|null $type;
    public array|null $data;

    public bool $requiredAction = false;

    public function __construct(User|bool $user) {
        if (!$user) {
            return;
        }

        global $con;
        $this->con = $con;

        $this->user = $user;

        [$this->id, $this->type, $this->data] = $this->getAction();

        if ($this->id) {
            $this->requiredAction = true;
        }
    }

    private function getAction(): array {
        $actionData = $this->con->query("SELECT id, type, data FROM actions WHERE user_id =", [$this->user->id], "AND fulfilled = false ORDER BY priority DESC LIMIT 1")->fetchRow();

        if (empty($actionData)) return [null, null, null];

        return [$actionData["id"], $actionData["type"], json_decode($actionData["data"], true)];
    }

    public function requestAction(string $actionType, array $actionData, int $actionPriority = 0, bool $serverSide = false): void {
        $this->con->query("INSERT INTO actions (`user_id`, `type`, `data`, `priority`) VALUES (", [$this->user->id], ", ", [$actionType], ", ", [utf8json($actionData)], ", ", [$actionPriority], ")");
        if (!$serverSide) header("Refresh:0");
    }
}
