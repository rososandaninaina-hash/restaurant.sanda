<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = positive_int($_POST['order_id'] ?? null);
    $action = (string)($_POST['action'] ?? '');
    $allowedStatus = ['nouvelle','en_preparation','prete','terminee','annulee'];
    if (!$id) $error = 'Commande invalide.';
    else {
        $stmt = $pdo->prepare('SELECT id, table_id, status, payment_status FROM orders WHERE id=?');
        $stmt->execute([$id]); $order = $stmt->fetch();
        if (!$order) $error = 'Commande introuvable.';
        elseif ($action === 'status') {
            $status = (string)($_POST['status'] ?? '');
            if (!in_array($status, $allowedStatus, true)) $error = 'État invalide.';
            elseif ($status === 'terminee' && $order['payment_status'] !== 'paye') $error = 'Encaissez d’abord le paiement à la caisse avant de terminer la commande.';
            else {
                $pdo->prepare('UPDATE orders SET status=? WHERE id=?')->execute([$status,$id]);
                if (in_array($status, ['terminee','annulee'], true) && $order['table_id']) {
                    $pdo->prepare('UPDATE restaurant_tables SET status="libre" WHERE id=?')->execute([$order['table_id']]);
                }
                flash('success', 'État de la commande mis à jour.');
                redirect('/restaurant-gestion/admin/orders.php');
            }
        } elseif ($action === 'pay') {
            if ($order['status'] === 'annulee') $error = 'Une commande annulée ne peut pas être encaissée.';
            else {
                $pdo->prepare('UPDATE orders SET payment_status="paye", paid_at=NOW() WHERE id=?')->execute([$id]);
                flash('success', 'Paiement enregistré à la caisse.');
                redirect('/restaurant-gestion/admin/orders.php');
            }
        }
    }
}
$orders = $pdo->query('SELECT o.*, u.name AS customer_name, u.email, t.table_number FROM orders o JOIN users u ON u.id=o.user_id LEFT JOIN restaurant_tables t ON t.id=o.table_id ORDER BY o.created_at DESC')->fetchAll();
$pageTitle = 'Gestion des commandes';
require __DIR__ . '/../includes/header.php';
?>
<h1>Commandes et caisse</h1><p class="muted">Le paiement est enregistré par l'administrateur après réception de l'argent à la caisse.</p>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<?php if (!$orders): ?><div class="empty-state">Aucune commande enregistrée.</div><?php else: ?>
<div class="table-wrap card"><table><thead><tr><th>N° / Client</th><th>Date / Type</th><th>Détail</th><th>Total</th><th>État</th><th>Caisse</th></tr></thead><tbody>
<?php foreach ($orders as $o): ?>
<tr>
<td><strong>#<?= (int)$o['id'] ?></strong><br><?= e($o['customer_name']) ?><br><small><?= e($o['email']) ?></small></td>
<td><?= e(date('d/m/Y H:i',strtotime($o['created_at']))) ?><br><?= $o['order_type']==='sur_place'?'Sur place'.($o['table_number']?' — table '.(int)$o['table_number']:''):'En ligne' ?></td>
<td><details><summary>Voir les plats</summary><ul><?php $ls=$pdo->prepare('SELECT item_name,quantity,unit_price FROM order_items WHERE order_id=?');$ls->execute([$o['id']]);foreach($ls->fetchAll() as $l): ?><li><?= e($l['item_name']) ?> × <?= (int)$l['quantity'] ?> — <?= money((float)$l['unit_price']*(int)$l['quantity']) ?></li><?php endforeach; ?></ul></details></td>
<td><strong><?= money($o['total_amount']) ?></strong></td>
<td><form method="post" class="stack compact"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="status"><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><select name="status"><?php foreach(['nouvelle'=>'Nouvelle','en_preparation'=>'En préparation','prete'=>'Prête','terminee'=>'Terminée','annulee'=>'Annulée'] as $v=>$label): ?><option value="<?= e($v) ?>" <?= $o['status']===$v?'selected':'' ?>><?= e($label) ?></option><?php endforeach; ?></select><button class="button button-small" type="submit">Mettre à jour</button></form></td>
<td><?php if($o['payment_status']==='paye'): ?><span class="paid">✓ Payé</span><br><small><?= $o['paid_at']?e(date('d/m/Y H:i',strtotime($o['paid_at']))):'' ?></small><?php else: ?><span class="unpaid">Non payé</span><form method="post" class="section-small" data-confirm="Confirmer que l'argent a bien été reçu à la caisse ?"><input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>"><input type="hidden" name="action" value="pay"><input type="hidden" name="order_id" value="<?= (int)$o['id'] ?>"><button class="button button-small" type="submit">Encaisser</button></form><?php endif; ?></td>
</tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
