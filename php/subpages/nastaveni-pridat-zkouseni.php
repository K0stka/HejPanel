<?php
if (isset($_GET["uspech"])) {
?>
    <div class="subject" id="successNotice">
        <div class="subject-info" style="text-align: center;">
            Nenaplánované zkoušení bylo úspěšně přidáno
        </div>
    </div>
<?php
}
?>
<label for="date">Datum:</label>
<input type="date" id="date" max="<?= date("Y-m-d") ?>">
<label for="subject">Predmet:</label>
<select id="subject">
    <?php
    foreach ($user->subjectsInfo as $subject) {
    ?>
        <option value="<?= $subject->id ?>"><?= $subject->name ?></option>
    <?php
    }
    ?>
</select>
<button id="addUnplannedClaimBtn">Přidat</button>