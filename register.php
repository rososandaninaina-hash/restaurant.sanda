<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) redirect('/restaurant-gestion/index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim((string)($_POST['name'] ?? ''));
    $email = strtolower(trim((string)($_POST['email'] ?? '')));
    $password = (string)($_POST['password'] ?? '');
    $confirm = (string)($_POST['confirm_password'] ?? '');

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $error = 'Le nom doit contenir entre 2 et 100 caractères.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) {
        $error = 'Adresse e-mail invalide.';
    } elseif (strlen($password) < 10 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        $error = 'Le mot de passe doit avoir au moins 10 caractères, une majuscule, une minuscule et un chiffre.';
    } elseif ($password !== $confirm) {
        $error = 'Les deux mots de passe ne correspondent pas.';
    } else {
        try {
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password_hash, role) VALUES (?, ?, ?, "client")');
            $stmt->execute([$name, $email, password_hash($password, PASSWORD_DEFAULT)]);
            flash('success', 'Compte créé. Vous pouvez maintenant vous connecter.');
            redirect('/restaurant-gestion/login.php');
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') $error = 'Cette adresse e-mail est déjà utilisée.';
            else throw $e;
        }
    }
}
$pageTitle = 'Inscription client';
require __DIR__ . '/includes/header.php';
?>
<section class="form-card narrow">
    <h1>Créer un compte client</h1>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Nom complet<input name="name" required minlength="2" maxlength="100" value="<?= e($_POST['name'] ?? '') ?>"></label>
        <label>Adresse e-mail<input type="email" name="email" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>"></label>
        <label>Mot de passe<input type="password" name="password" required minlength="10" autocomplete="new-password"><small>10 caractères minimum, avec majuscule, minuscule et chiffre.</small></label>
        <label>Confirmer le mot de passe<input type="password" name="confirm_password" required autocomplete="new-password"></label>
        <button class="button" type="submit">Créer mon compte</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
