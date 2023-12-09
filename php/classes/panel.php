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
            $panel = $this->con->query("SELECT * FROM panels WHERE id = ", [$data])->fetchRow();
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

    /** @return Panel[] */
    public static function getVisiblePanels(): array {
        global $con;
        return array_map(fn ($data) => new Panel($data), $con->query("SELECT * FROM panels WHERE show_override = 'show' OR (show_override IS NULL AND approved = 'true' AND show_from <= ", [date("Y-m-d")], " AND show_till >= ", [date("Y-m-d")], ") ORDER BY show_from ASC")->fetchAll());
    }

    public static function getVisiblePanelsHash(): string {
        return "";
    }

    /** @return Panel[] */
    public static function getExpiredPanels(): array {
        global $con;
        return array_map(fn ($data) => new Panel($data), $con->query("SELECT * FROM panels WHERE show_till < ", [date("Y-m-d")], " or (approved = 'false' AND approved_by IS NOT NULL) or show_override = 'false'")->fetchAll());
    }

    /** @return Panel[] */
    public static function getWaitingPanels(): array {
        global $con;
        return array_map(fn ($data) => new Panel($data), $con->query("SELECT * FROM panels WHERE approved_at IS NULL")->fetchAll());
    }

    public static function countWaitingPanels(): int {
        global $con;
        return $con->query("SELECT COUNT(*) AS count FROM panels WHERE approved_at IS NULL")->fetchRow()["count"];
    }
}
