<?php
class PanelReview {
    public static function render(Panel $panel, array $fingerprintMap, $buttorsGenerator = null): void {
        $panel->postedBy->categorizeByFingerprint($fingerprintMap);
?>
        <div class="panel-review">
            <div class="panel-review-preview">
                <div class="object-fit-fill" style="width:1920px;height:1080px">
                    <?= $panel->render() ?>
                </div>
            </div>
            <div>
                <div class="two-columns-grid">
                    <span style="font-weight: var(--semibold);">Uživatel:</span><span><?= $panel->postedBy->renderChip() ?></span>
                    <span style="font-weight: var(--semibold);">Přidáno:</span><span><?= $panel->postedAt->format(DATE_DM_FORMAT . " " . TIME_HM_FORMAT) ?></span>
                    <span style="font-weight: var(--semibold);">Vyvěsit:</span><span><?= getWeekDay($panel->showFrom) . " " . $panel->showFrom->format(DATE_DM_FORMAT) ?> - <?= getWeekDay($panel->showTill) . " " . $panel->showTill->format(DATE_DM_FORMAT) ?></span>
                </div>
                <div class="input-label">
                    <label>Poznámka:</label>
                    <textarea readonly style="width: 100%;"><?= str_replace("<br>", "\n", $panel->note) ?></textarea>
                </div>
            </div>
            <div class="panel-review-buttons">
                <?= $buttorsGenerator ? $buttorsGenerator() : "" ?>
            </div>
        </div>
<?php
    }
}
