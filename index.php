<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

if (current_user()) {
    redirect(current_user()['role'] === 'admin' ? '/restaurant-gestion/admin/index.php' : '/restaurant-gestion/client/menu.php');
}
$pageTitle = 'Accueil';
require __DIR__ . '/includes/header.php';
?>
<section class="hero">
    <div>
        <p class="eyebrow">BIENVENUE CHEZ NOUS</p>
        <h1>Les bons plats, simplement.</h1>
        <p>Consultez la carte, passez une commande et payez à la caisse du restaurant.</p>
        <div class="actions">
            <a class="button" href="/restaurant-gestion/register.php">Créer un compte client</a>
            <a class="button button-outline" href="/restaurant-gestion/login.php">Se connecter</a>
        </div>
    </div>
    <div class="hero-emoji" aria-hidden="true">🍲</div>
</section>
<section class="section">
    <h2>Comment ça marche ?</h2>
    <div class="grid grid-3">
        <article class="card"><div class="icon-circle">📋</div><h3>1. Choisissez</h3><p>Parcourez les plats disponibles et ajoutez-les au panier.</p></article>
        <article class="card"><div class="icon-circle">🛒</div><h3>2. Commandez</h3><p>Choisissez une commande sur place ou en ligne.</p></article>
        <article class="card"><div class="icon-circle">💵</div><h3>3. Payez à la caisse</h3><p>Le paiement est enregistré par le personnel du restaurant.</p></article>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
