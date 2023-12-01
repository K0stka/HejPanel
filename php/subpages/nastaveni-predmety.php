<?php

/** @var Subject $subject */
$jsManager->passToJS([
    "ATTENDS" => $user->subjects
]);
foreach (array_map(fn ($e) => new Subject($e["id"]), $con->query("SELECT id FROM subjects ORDER BY name ASC")->fetchAll()) as $subject) {
?>
    <div class="subject<?= (!in_array($subject->id, $user->subjects) ? " deactivated" : "") ?>">
        <div class="subject-name">
            <?= $subject->name ?> (<?= $subject->getTeacher()->real_name ?>)
        </div>
        <div class="subject-info">
            <span>
                Navštěvuješ?
                <span>
                    <button class="small" data-button-type="attend" data-subject-id="<?= $subject->id ?>">
                        Ano
                    </button>
                    <button class="small" data-button-type="dropout" data-subject-id="<?= $subject->id ?>">
                        Ne
                    </button>
                </span>
            </span>
        </div>
    </div>
<?php
}
?>
<button id="saveSubjects">
    Uložit
</button>