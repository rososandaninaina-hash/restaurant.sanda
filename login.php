<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) redirect(current_user()['role'] === 'admin' ? '/restaurant-gestion/admin/index.php' : '/restaurant-gestion/client/menu.php');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = trim((string)($_POST['email'] ?? ''));
    $password = (string)($_POST['password'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') {
        $error = 'Saisissez une adresse e-mail valide et votre mot de passe.';
    } else {
        $stmt = $pdo->prepare('SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = [
                'id' => (int)$user['id'],
                'name' => $user['name'],
                'email' => $user['email'],
                'role' => $user['role'],
            ];
            // Un nouveau jeton CSRF est créé après la connexion.
            unset($_SESSION['csrf_token']);
            flash('success', 'Connexion réussie. Bienvenue !');
            redirect($user['role'] === 'admin' ? '/restaurant-gestion/admin/index.php' : '/restaurant-gestion/client/menu.php');
        }
        // Message volontairement générique : ne révèle pas si l'e-mail existe.
        $error = 'Identifiants incorrects.';
    }
}
$pageTitle = 'Connexion';
require __DIR__ . '/includes/header.php';
?>
<section class="form-card narrow">
    <h1>Connexion</h1>
    <p class="muted">Connectez-vous à votre espace administrateur ou client.</p>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="stack">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <label>Adresse e-mail<input type="email" name="email" required autocomplete="username" maxlength="190"></label>
        <label>Mot de passe<input type="password" name="password" required autocomplete="current-password"></label>
        <button class="button" type="submit">Se connecter</button>
    </form>
    <p class="muted">Pas encore de compte client ? <a href="/restaurant-gestion/register.php">Inscrivez-vous</a>.</p>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
