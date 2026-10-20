<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('client');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $menuId = positive_int($_POST['menu_id'] ?? null);
    $quantity = positive_int($_POST['quantity'] ?? 1);
    $quantity = min($quantity ?? 1, 20);
    $stmt = $pdo->prepare('SELECT id FROM menu_items WHERE id = ? AND available = 1');
    $stmt->execute([$menuId]);
    if ($menuId && $stmt->fetch()) {
        $_SESSION['cart'] = $_SESSION['cart'] ?? [];
        $_SESSION['cart'][$menuId] = min(20, (int)($_SESSION['cart'][$menuId] ?? 0) + $quantity);
        flash('success', 'Plat ajouté au panier.');
    } else flash('danger', 'Ce plat n’est plus disponible.');
    redirect('/restaurant-gestion/client/menu.php');
}

$stmt = $pdo->query('SELECT m.*, c.name AS category_name FROM menu_items m LEFT JOIN categories c ON c.id = m.category_id WHERE m.available = 1 ORDER BY c.name, m.name');
$items = $stmt->fetchAll();
$pageTitle = 'La carte';
require __DIR__ . '/../includes/header.php';
?>
<div class="page-heading"><div><p class="eyebrow">NOTRE CARTE</p><h1>Choisissez vos plats</h1><p class="muted">Les prix sont affichés en ariary malgache (MGA).</p></div><a class="button" href="/restaurant-gestion/client/cart.php">Voir le panier (<?= array_sum($_SESSION['cart'] ?? []) ?>)</a></div>
<?php if (!$items): ?>
    <div class="empty-state">La carte sera bientôt disponible.</div>
<?php else: ?>
<div class="grid grid-3">
<?php foreach ($items as $item): ?>
    <article class="card menu-card">
        <div class="food-emoji"><?= e($item['emoji'] ?: '🍽️') ?></div>
        <p class="eyebrow"><?= e($item['category_name'] ?? 'Menu') ?></p>
        <h3><?= e($item['name']) ?></h3>
        <p><?= e($item['description']) ?></p>
        <div class="card-bottom"><strong class="price"><?= money($item['price']) ?></strong>
        <form method="post" class="add-form">
            <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
            <input type="hidden" name="menu_id" value="<?= (int)$item['id'] ?>">
            <label class="sr-only" for="qty-<?= (int)$item['id'] ?>">Quantité</label>
            <input id="qty-<?= (int)$item['id'] ?>" type="number" name="quantity" min="1" max="20" value="1">
            <button class="button button-small" type="submit">Ajouter</button>
        </form></div>
    </article>
<?php endforeach; ?>
</div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
