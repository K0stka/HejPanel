<form>
    <label for="realName">Jméno:</label>
    <input id="realName" value="<?= $user->real_name ?>" data-type="name">
    <label for="mail">Mail:</label>
    <input id="mail" value="<?= $user->mail ?>" data-type="mail">
    <label for="password">Nové heslo:</label>
    <input id="password" value="" type="password" data-type="passwordOrEmpty">
    <button id="saveUser">
        Uložit změny
    </button>
</form>