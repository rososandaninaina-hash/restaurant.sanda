<?php
$pageTitle = $pageTitle ?? 'Restaurant Mada';
$user = current_user();
$flashMessage = get_flash();
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> — Restaurant Mada</title>
    <link rel="stylesheet" href="/restaurant-gestion/assets/style.css">
    <script src="/restaurant-gestion/assets/app.js" defer></script>
</head>
<body>
<header class="topbar">
    <a class="brand" href="/restaurant-gestion/index.php">🍽️ Restaurant Mada</a>
    <nav>
        <?php if ($user && $user['role'] === 'admin'): ?>
            <a href="/restaurant-gestion/admin/index.php">Tableau de bord</a>
            <a href="/restaurant-gestion/admin/menu.php">Menus</a>
            <a href="/restaurant-gestion/admin/orders.php">Commandes</a>
            <a href="/restaurant-gestion/admin/tables.php">Tables</a>
        <?php elseif ($user): ?>
            <a href="/restaurant-gestion/client/menu.php">Carte</a>
            <a href="/restaurant-gestion/client/cart.php">Panier</a>
            <a href="/restaurant-gestion/client/orders.php">Mes commandes</a>
        <?php else: ?>
            <a href="/restaurant-gestion/index.php">Accueil</a>
            <a href="/restaurant-gestion/login.php">Connexion</a>
            <a href="/restaurant-gestion/register.php">Créer un compte</a>
        <?php endif; ?>
        <?php if ($user): ?>
            <span class="nav-user"><?= e($user['name']) ?></span>
            <form class="inline-form" action="/restaurant-gestion/logout.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
                <button class="button button-small button-outline" type="submit">Déconnexion</button>
            </form>
        <?php endif; ?>
    </nav>
</header>
<main class="container">
<?php if ($flashMessage): ?>
    <div class="alert alert-<?= e($flashMessage['type']) ?>" role="status"><?= e($flashMessage['message']) ?></div>
<?php endif; ?>
