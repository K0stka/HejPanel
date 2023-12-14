<?php

enum PanelType: string {
    case image = "image";
    case text = "text";
}

class Panel {
    private Conn $con;

    public int $id;

    public User $postedBy;
    public DateTime $postedAt;

    public bool $approved;
    public User $approvedBy;
    public DateTime $approvedAt;

    public DateTime $showFrom;
    public DateTime $showTill;

    public PanelType $type;
    public string $content;

    public string $note;

    public function __construct(int|array $data) {
        global $con;
        $this->con = $con;

        if (is_array($data)) {
            $panel = $data;
        } else {
            $panel = $this->con->select(true, "panels")->where(["id" => $data])->fetchRow();
        }

        if (empty($panel)) return;

        $this->id = $panel["id"];

        $this->postedBy = new User($panel["posted_by"]);
        $this->postedAt = new DateTime($panel["posted_at"]);

        $this->approved = $panel["approved"] == 1;
        $this->approvedBy = new User($panel["approved_by"] ?? -1);
        $this->approvedAt = new DateTime($panel["approved_at"]);

        $this->showFrom = new DateTime($panel["show_from"]);
        $this->showTill = new DateTime($panel["show_till"]);

        $this->type = PanelType::from($panel["type"]);
        $this->content = $panel["content"];

        $this->note = $panel["note"];
    }

    public function render(): string {
        switch ($this->type) {
            case PanelType::image:
                global $prefix;
                return "<div class=\"panel panel-image\"><img src=\"$prefix/contentAPI/$this->content\"></div>";
            case PanelType::text:
                return "<div class=\"panel panel-text\">$this->content</div>";
        }
    }

    public function serialize(): array {
        return ["id" => $this->id, "type" => $this->type->value, "content" => $this->content];
    }

    /** @return Panel[] */
    // Warning - Expects already pre-sanitized input
    public static function getPanelsByIds(array $ids): array {
        global $con;
        if (empty($ids)) return [];
        return array_map(fn ($data) => new Panel($data), $con->select(true, "panels")->addSQL("WHERE show_override = 'show' OR (show_override IS NULL AND approved = 'true' AND show_from <= ", [date("Y-m-d")], " AND show_till >= ", [date("Y-m-d")], ") AND (", join(" OR ", array_map(fn ($e) => "id = " . $e, $ids)), ")")->orderBy("show_from", Order::minToMax)->fetchAll());
    }

    /** @return Panel[] */
    public static function getVisiblePanels(): array {
        global $con;
        return array_map(fn ($data) => new Panel($data), $con->select(true, "panels")->addSQL("WHERE show_override = 'show' OR (show_override IS NULL AND approved = 'true' AND show_from <= ", [date("Y-m-d")], " AND show_till >= ", [date("Y-m-d")], ")")->orderBy("show_from", Order::minToMax)->fetchAll());
    }

    public static function getVisiblePanelsIDs(): array {
        global $con;
        return $con->select("id", "panels")->addSQL("WHERE show_override = 'show' OR (show_override IS NULL AND approved = 'true' AND show_from <= ", [date("Y-m-d")], " AND show_till >= ", [date("Y-m-d")], ")")->orderBy("show_from", Order::minToMax)->fetchColumn();
    }

    /** @return Panel[] */
    public static function getExpiredPanels(): array {
        global $con;
        return array_map(fn ($data) => new Panel($data), $con->select(true, "panels")->addSQL("WHERE show_till < ", [date("Y-m-d")], " or (approved = 'false' AND approved_by IS NOT NULL) or show_override = 'false'")->fetchAll());
    }

    /** @return Panel[] */
    public static function getWaitingPanels(): array {
        global $con;
        return array_map(fn ($data) => new Panel($data), $con->select(true, "panels")->addSQL("WHERE approved_at IS NULL")->fetchAll());
    }

    public static function countWaitingPanels(): int {
        global $con;
        return $con->select(["COUNT(*)" => "count"], "panels")->addSQL("WHERE approved_at IS NULL")->fetchRow()["count"];
    }

    public static function getEmptyPanel(): Panel {
        return new Panel([
            "id" => -1,
            "posted_by" => -1,
            "posted_at" => -1,
            "approved" => true,
            "approved_by" => -1,
            "approved_at" => -1,
            "show_from" => -1,
            "show_till" => -1,
            "type" => "text",
            "content" => "Víte, jak zajistit, že zde vždy bude něco ke čtení?<br>Přesně takto :)",
            "note" => ""
        ]);
    }
}
