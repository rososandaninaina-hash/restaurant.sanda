<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('client');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    $id = positive_int($_POST['menu_id'] ?? null);
    if ($action === 'remove' && $id) {
        unset($_SESSION['cart'][$id]);
        flash('success', 'Plat retiré du panier.');
        redirect('/restaurant-gestion/client/cart.php');
    }
    if ($action === 'update') {
        $newCart = [];
        foreach (($_POST['quantities'] ?? []) as $key => $value) {
            $menuId = positive_int($key);
            $qty = filter_var($value, FILTER_VALIDATE_INT);
            if ($menuId && $qty !== false && $qty >= 1 && $qty <= 20) $newCart[$menuId] = (int)$qty;
        }
        $_SESSION['cart'] = $newCart;
        flash('success', 'Panier mis à jour.');
        redirect('/restaurant-gestion/client/cart.php');
    }
    if ($action === 'checkout') {
        $cart = $_SESSION['cart'] ?? [];
        if (!$cart) {
            flash('warning', 'Votre panier est vide.');
            redirect('/restaurant-gestion/client/menu.php');
        }
        $orderType = (string)($_POST['order_type'] ?? 'en_ligne');
        if (!in_array($orderType, ['en_ligne', 'sur_place'], true)) $orderType = 'en_ligne';
        $tableId = positive_int($_POST['table_id'] ?? null);
        if ($orderType === 'sur_place' && !$tableId) {
            flash('danger', 'Pour une commande sur place, choisissez une table.');
            redirect('/restaurant-gestion/client/cart.php');
        }
        try {
            $pdo->beginTransaction();
            if ($orderType === 'sur_place') {
                $tableStmt = $pdo->prepare('SELECT id FROM restaurant_tables WHERE id = ? AND status = "libre" FOR UPDATE');
                $tableStmt->execute([$tableId]);
                if (!$tableStmt->fetch()) throw new RuntimeException('Cette table n’est plus disponible. Choisissez-en une autre.');
            }
            $ids = array_values(array_filter(array_map('intval', array_keys($cart)), fn($v) => $v > 0));
            if (!$ids) throw new RuntimeException('Votre panier est vide.');
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $menuStmt = $pdo->prepare("SELECT id, name, price FROM menu_items WHERE available = 1 AND id IN ($placeholders)");
            $menuStmt->execute($ids);
            $dbItems = [];
            foreach ($menuStmt->fetchAll() as $row) $dbItems[(int)$row['id']] = $row;
            $total = 0;
            $lines = [];
            foreach ($cart as $id => $qty) {
                $id = (int)$id; $qty = max(1, min(20, (int)$qty));
                if (!isset($dbItems[$id])) continue;
                $price = (float)$dbItems[$id]['price'];
                $total += $price * $qty;
                $lines[] = [$id, $dbItems[$id]['name'], $qty, $price];
            }
            if (!$lines) throw new RuntimeException('Aucun plat du panier n’est disponible.');
            $orderStmt = $pdo->prepare('INSERT INTO orders (user_id, table_id, order_type, status, payment_status, total_amount) VALUES (?, ?, ?, "nouvelle", "non_paye", ?)');
            $orderStmt->execute([current_user()['id'], $orderType === 'sur_place' ? $tableId : null, $orderType, $total]);
            $orderId = (int)$pdo->lastInsertId();
            $lineStmt = $pdo->prepare('INSERT INTO order_items (order_id, menu_item_id, item_name, quantity, unit_price) VALUES (?, ?, ?, ?, ?)');
            foreach ($lines as [$id, $name, $qty, $price]) $lineStmt->execute([$orderId, $id, $name, $qty, $price]);
            if ($orderType === 'sur_place') {
                $pdo->prepare('UPDATE restaurant_tables SET status = "occupee" WHERE id = ?')->execute([$tableId]);
            }
            $pdo->commit();
            unset($_SESSION['cart']);
            flash('success', 'Commande n°' . $orderId . ' enregistrée. Le paiement se fera à la caisse.');
            redirect('/restaurant-gestion/client/orders.php');
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            flash('danger', $e instanceof RuntimeException ? $e->getMessage() : 'Impossible d’enregistrer la commande. Réessayez.');
            redirect('/restaurant-gestion/client/cart.php');
        }
    }
}

$cart = $_SESSION['cart'] ?? [];
$items = [];
$total = 0;
if ($cart) {
    $ids = array_values(array_filter(array_map('intval', array_keys($cart)), fn($v) => $v > 0));
    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("SELECT id, name, price FROM menu_items WHERE id IN ($placeholders) AND available = 1");
        $stmt->execute($ids);
        $items = $stmt->fetchAll();
    }
}
$tables = $pdo->query('SELECT id, table_number, seats FROM restaurant_tables WHERE status = "libre" ORDER BY table_number')->fetchAll();
$pageTitle = 'Mon panier';
require __DIR__ . '/../includes/header.php';
?>
<h1>Mon panier</h1>
<?php if (!$items): ?>
    <div class="empty-state"><p>Votre panier est vide.</p><a class="button" href="/restaurant-gestion/client/menu.php">Découvrir la carte</a></div>
<?php else: ?>
<form method="post" class="card">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="action" value="update">
    <div class="table-wrap"><table><thead><tr><th>Plat</th><th>Prix unitaire</th><th>Quantité</th><th>Sous-total</th></tr></thead><tbody>
    <?php foreach ($items as $item): $qty = (int)($cart[$item['id']] ?? 1); $line = (float)$item['price'] * $qty; $total += $line; ?>
        <tr><td><?= e($item['name']) ?></td><td><?= money($item['price']) ?></td><td><input class="qty-input" type="number" min="1" max="20" name="quantities[<?= (int)$item['id'] ?>]" value="<?= $qty ?>"></td><td><?= money($line) ?></td></tr>
    <?php endforeach; ?>
    </tbody></table></div>
    <div class="cart-actions"><button class="button button-outline" type="submit">Mettre à jour</button><strong>Total : <?= money($total) ?></strong></div>
</form>
<section class="form-card">
    <h2>Confirmer la commande</h2>
    <p class="muted">Le paiement sera effectué à la caisse du restaurant.</p>
    <form method="post" class="stack" data-confirm="Confirmer cette commande ?">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
        <input type="hidden" name="action" value="checkout">
        <label>Type de commande
            <select name="order_type" id="order-type">
                <option value="en_ligne">Commande en ligne (à récupérer)</option>
                <option value="sur_place">Sur place (à table)</option>
            </select>
        </label>
        <label id="table-choice" hidden>Choisir une table
            <select name="table_id">
                <option value="">-- Sélectionner --</option>
                <?php foreach ($tables as $table): ?><option value="<?= (int)$table['id'] ?>">Table <?= (int)$table['table_number'] ?> — <?= (int)$table['seats'] ?> places</option><?php endforeach; ?>
            </select>
            <?php if (!$tables): ?><small>Aucune table libre pour le moment.</small><?php endif; ?>
        </label>
        <button class="button" type="submit">Confirmer et commander</button>
    </form>
</section>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
