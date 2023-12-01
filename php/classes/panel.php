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

    public function __construct(int $id) {
        global $con;
        $this->con = $con;

        $panel = $this->con->query("SELECT * FROM panels WHERE id = ", [$id])->fetchRow();

        if (empty($panel)) return;

        $this->id = $panel["id"];

        $this->postedBy = new User($panel["posted_by"]);
        $this->postedAt = new DateTime($panel["posted_at"]);

        $this->approved = $panel["approved"] == 1;
        $this->approvedBy = new User($panel["approved_by"]);
        $this->approvedAt = new DateTime($panel["approved_at"]);

        $this->showFrom = new DateTime($panel["show_from"]);
        $this->showTill = new DateTime($panel["show_till"]);

        $this->type = PanelType::from($panel["type"]);
        $this->content = $panel["content"];
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
        return [];
    }

    public static function getVisiblePanelsHash(): string {
        return "";
    }
}
