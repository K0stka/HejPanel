<?php

enum PanelType: string {
    case image = "image";
    case text = "text";
}

enum ShowOverride: string {
    case show = "show";
    case hide = "hide";
    case null = "";
}

class Panel extends MySQLtoPHPautomapper {
    protected Conn $con;

    public int $id;

    public User $postedBy;
    public DateTime $postedAt;

    public ?bool $approved;
    public User $approvedBy;
    public ?DateTime $approvedAt;

    public DateTime $showFrom;
    public DateTime $showTill;

    public ?ShowOverride $showOverride;

    public PanelType $type;
    public ?string $url;
    public string $content;

    public string $note;

    // Automapper settings
    protected string $tableName = "panels";
    protected string $index = "id";
    protected array $mapFromTo = [
        "id" => ["id", StoredAs::int],
        "posted_by" => ["postedBy", StoredAs::foreignId, "User"],
        "posted_at" => ["postedAt", StoredAs::datetime],
        "approved" => ["approved", StoredAs::bool],
        "approved_by" => ["approvedBy", StoredAs::foreignId, "User"],
        "approved_at" => ["approvedAt", StoredAs::nullableDatetime],
        "show_from" => ["showFrom", StoredAs::datetime],
        "show_till" => ["showTill", StoredAs::datetime],
        "show_override" => ["showOverride", StoredAs::enum, "ShowOverride"],
        "type" => ["type", StoredAs::enum, "PanelType"],
        "content" => ["content", StoredAs::string],
        "url" => ["url", StoredAs::nullableString],
        "note" => ["note", StoredAs::string]
    ];


    public function __construct(array|int|bool $data = false, bool $shallow = false) {
        global $con;
        $this->con = $con;

        parent::__construct($data);

        if ($shallow || $data == false) return;

        $this->complete();
    }

    public function render(): string {
        switch ($this->type) {
            case PanelType::image:
                global $prefix;
                return "<div class=\"panel panel-image\" id=\"panel-$this->id\"><img src=\"$prefix/api/content/$this->id\" class=\"backdrop\"><img src=\"$prefix/api/content/$this->id\"></div>";
            case PanelType::text:
                return "<div class=\"panel panel-text\" id=\"panel-$this->id\">$this->content</div>";
        }
    }

    public function serializePanel(): array {
        return ["i" => $this->id, "t" => $this->type->value, "c" => $this->content, "u" => $this->url];
    }

    /** @return Panel[] */
    // Warning - Expects already pre-sanitized input
    public static function getPanelsByIds(array $ids): array {
        global $con;
        if (empty($ids)) return [];
        return array_map(fn ($data) => new Panel($data, true), $con->select(["id", "type", "content", "url"], "panels")->addSQL("WHERE (show_override = 'show' OR (show_override IS NULL AND approved = TRUE AND show_from <= ", [date(MYSQL_DATETIME)], " AND show_till >= ", [date(MYSQL_DATETIME)], ")) AND (", join(" OR ", array_map(fn ($e) => "id = " . $e, $ids)), ")")->orderBy("show_from", Order::minToMax)->fetchAll());
    }

    /** @return Panel[] */
    public static function getVisiblePanels(): array {
        global $con;
        return array_map(fn ($data) => new Panel($data), $con->select(true, "panels")->addSQL("WHERE show_override = 'show' OR (show_override IS NULL AND approved = TRUE AND show_from <= ", [date(MYSQL_DATETIME)], " AND show_till >= ", [date(MYSQL_DATETIME)], ")")->orderBy("show_from", Order::minToMax)->fetchAll());
    }

    public static function getVisiblePanelsIDs(): array {
        global $con;
        return $con->select("id", "panels")->addSQL("WHERE show_override = 'show' OR (show_override IS NULL AND approved = TRUE AND show_from <= ", [date(MYSQL_DATETIME)], " AND show_till >= ", [date(MYSQL_DATETIME)], ")")->orderBy("show_from", Order::minToMax)->fetchColumn();
    }

    /** @return Panel[] */
    public static function getExpiredPanels(): array {
        global $con;
        return array_map(fn ($data) => new Panel($data), $con->select(true, "panels")->addSQL("WHERE show_override = 'hide' OR (show_override IS NULL AND show_till <= ", [date(MYSQL_DATETIME)], " or (approved = FALSE AND approved_by IS NOT NULL) or show_override = 'false')")->orderBy("id", Order::maxToMin)->fetchAll());
    }

    /** @return Panel[] */
    public static function getWaitingPanels(): array {
        global $con;
        return array_map(fn ($data) => new Panel($data), $con->select(true, "panels")->addSQL("WHERE show_till >= ", [date(MYSQL_DATETIME)], " AND approved_at IS NULL")->fetchAll());
    }

    public static function countWaitingPanels(): int {
        global $con;
        return $con->select(["COUNT(*)" => "count"], "panels")->addSQL("WHERE show_till >= ", [date(MYSQL_DATETIME)], " AND approved_at IS NULL")->fetchRow()["count"];
    }

    public static function getEmptyPanel(): Panel {
        return new Panel([
            "id" => -1,
            "type" => "text",
            "content" => "Víte, jak zajistit, že zde vždy bude něco ke čtení?<br>Přesně takto :)",
            "url" => null
        ], true);
    }
}
