<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'save') {
        $id = positive_int($_POST['id'] ?? null);
        $name = trim((string)($_POST['name'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price = filter_var($_POST['price'] ?? null, FILTER_VALIDATE_FLOAT);
        $categoryId = positive_int($_POST['category_id'] ?? null);
        $emoji = trim((string)($_POST['emoji'] ?? '🍽️'));
        $available = isset($_POST['available']) ? 1 : 0;
        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) $error = 'Le nom doit contenir entre 2 et 120 caractères.';
        elseif ($price === false || $price <= 0 || $price > 100000000) $error = 'Prix invalide.';
        elseif (!$categoryId) $error = 'Choisissez une catégorie.';
        else {
            $cat = $pdo->prepare('SELECT id FROM categories WHERE id = ?');
            $cat->execute([$categoryId]);
            if (!$cat->fetch()) $error = 'Catégorie inconnue.';
            else {
                if ($id) {
                    $stmt = $pdo->prepare('UPDATE menu_items SET category_id=?, name=?, description=?, price=?, emoji=?, available=? WHERE id=?');
                    $stmt->execute([$categoryId, $name, $description, $price, $emoji, $available, $id]);
                    flash('success', 'Plat modifié.');
                } else {
                    $stmt = $pdo->prepare('INSERT INTO menu_items (category_id,name,description,price,emoji,available) VALUES (?,?,?,?,?,?)');
                    $stmt->execute([$categoryId, $name, $description, $price, $emoji, $available]);
                    flash('success', 'Plat ajouté.');
                }
                redirect('/restaurant-gestion/admin/menu.php');
            }
        }
    } elseif ($action === 'delete') {
        $id = positive_int($_POST['id'] ?? null);
        if ($id) {
            // On désactive plutôt que supprimer : les anciennes commandes gardent leur historique.
            $pdo->prepare('UPDATE menu_items SET available=0 WHERE id=?')->execute([$id]);
            flash('success', 'Plat désactivé.');
            redirect('/restaurant-gestion/admin/menu.php');
        }
    }
}
$categories = $pdo->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$editItem = null;
if (isset($_GET['edit'])) {
    $editId = positive_int($_GET['edit']);
    if ($editId) { $st=$pdo->prepare('SELECT * FROM menu_items WHERE id=?'); $st->execute([$editId]); $editItem=$st->fetch() ?: null; }
}
$items = $pdo->query('SELECT m.*, c.name AS category_name FROM menu_items m JOIN categories c ON c.id=m.category_id ORDER BY c.name,m.name')->fetchAll();
$pageTitle = 'Gestion des menus';
require __DIR__ . '/../includes/header.php';
?>
<h1>Gestion des menus</h1><p class="muted">Ajoutez des plats ou rendez-les indisponibles. Les anciens détails de commande sont conservés.</p>
<section class="form-card">
    <h2><?= $editItem ? 'Modifier un plat' : 'Ajouter un plat' ?></h2>
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="post" class="form-grid">
        <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($editItem['id'] ?? 0) ?>">
        <label>Nom du plat<input name="name" required maxlength="120" value="<?= e($editItem['name'] ?? '') ?>"></label>
        <label>Catégorie<select name="category_id" required><option value="">-- Choisir --</option><?php foreach ($categories as $c): ?><option value="<?= (int)$c['id'] ?>" <?= (int)($editItem['category_id'] ?? 0)===(int)$c['id']?'selected':'' ?>><?= e($c['name']) ?></option><?php endforeach; ?></select></label>
        <label>Prix (MGA)<input type="number" name="price" min="1" step="1" required value="<?= e($editItem['price'] ?? '') ?>"></label>
        <label>Emoji<input name="emoji" maxlength="16" value="<?= e($editItem['emoji'] ?? '🍽️') ?>"></label>
        <label class="full-width">Description<textarea name="description" rows="2" maxlength="500"><?= e($editItem['description'] ?? '') ?></textarea></label>
        <label class="check-label"><input type="checkbox" name="available" value="1" <?= (!$editItem || $editItem['available'])?'checked':'' ?>> Disponible à la vente</label>
        <div class="full-width actions"><button class="button" type="submit"><?= $editItem ? 'Enregistrer' : 'Ajouter le plat' ?></button><?php if ($editItem): ?><a class="button button-outline" href="/restaurant-gestion/admin/menu.php">Annuler</a><?php endif; ?></div>
    </form>
</section>
<h2 class="section">Tous les plats</h2>
<div class="table-wrap card"><table><thead><tr><th>Plat</th><th>Catégorie</th><th>Prix</th><th>Disponibilité</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($items as $item): ?><tr><td><?= e($item['emoji']) ?> <?= e($item['name']) ?></td><td><?= e($item['category_name']) ?></td><td><?= money($item['price']) ?></td><td><?= $item['available']?'Disponible':'Indisponible' ?></td><td class="actions"><a class="button button-small button-outline" href="?edit=<?= (int)$item['id'] ?>">Modifier</a><?php if ($item['available']): ?><form method="post" class="inline-form" data-confirm="Désactiver ce plat ?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$item['id'] ?>"><button class="button button-small button-danger" type="submit">Désactiver</button></form><?php endif; ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
