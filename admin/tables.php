<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string)($_POST['action'] ?? '');
    if ($action === 'add') {
        $number = positive_int($_POST['table_number'] ?? null);
        $seats = positive_int($_POST['seats'] ?? null);
        if (!$number || $number > 999 || !$seats || $seats > 30) $error = 'Numéro ou nombre de places invalide.';
        else {
            try { $stmt=$pdo->prepare('INSERT INTO restaurant_tables (table_number,seats,status) VALUES (?,?,"libre")');$stmt->execute([$number,$seats]);flash('success','Table ajoutée.');redirect('/restaurant-gestion/admin/tables.php'); }
            catch(PDOException $e) { $error = $e->getCode()==='23000'?'Ce numéro de table existe déjà.':'Impossible d’ajouter la table.'; }
        }
    } elseif ($action === 'status') {
        $id=positive_int($_POST['id']??null);$status=(string)($_POST['status']??'');
        if ($id && in_array($status,['libre','occupee','indisponible'],true)) {
            $stmt=$pdo->prepare('UPDATE restaurant_tables SET status=? WHERE id=?');$stmt->execute([$status,$id]);flash('success','État de table modifié.');redirect('/restaurant-gestion/admin/tables.php');
        } else $error='Données de table invalides.';
    }
}
$tables=$pdo->query('SELECT t.*, (SELECT COUNT(*) FROM orders o WHERE o.table_id=t.id AND o.status NOT IN ("terminee","annulee")) AS active_orders FROM restaurant_tables t ORDER BY table_number')->fetchAll();
$pageTitle='Gestion des tables';
require __DIR__ . '/../includes/header.php';
?>
<h1>Gestion des tables</h1><p class="muted">Les tables associées à une commande active ne doivent pas être libérées manuellement.</p>
<section class="form-card">
<h2>Ajouter une table</h2><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post" class="form-grid"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="add">
<label>Numéro de table<input type="number" name="table_number" min="1" max="999" required></label><label>Nombre de places<input type="number" name="seats" min="1" max="30" required></label><div class="actions"><button class="button" type="submit">Ajouter</button></div></form>
</section>
<div class="grid grid-3 section">
<?php foreach($tables as $t): ?><article class="card table-card"><div class="table-icon">🪑</div><h3>Table <?= (int)$t['table_number'] ?></h3><p><?= (int)$t['seats'] ?> places</p><p><span class="status"><?= e($t['status']) ?></span></p>
<form method="post" class="stack"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="id" value="<?= (int)$t['id'] ?>"><label>État<select name="status" <?= (int)$t['active_orders']>0?'disabled title="Commande active"':'' ?>><option value="libre" <?= $t['status']==='libre'?'selected':'' ?>>Libre</option><option value="occupee" <?= $t['status']==='occupee'?'selected':'' ?>>Occupée</option><option value="indisponible" <?= $t['status']==='indisponible'?'selected':'' ?>>Indisponible</option></select></label><?php if((int)$t['active_orders']===0): ?><button class="button button-small button-outline" type="submit">Enregistrer</button><?php else: ?><small>Commande active : l'état sera libéré à la clôture.</small><?php endif; ?></form>
</article><?php endforeach; ?>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
