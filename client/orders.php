<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('client');

$stmt = $pdo->prepare('SELECT o.*, t.table_number FROM orders o LEFT JOIN restaurant_tables t ON t.id = o.table_id WHERE o.user_id = ? ORDER BY o.created_at DESC');
$stmt->execute([current_user()['id']]);
$orders = $stmt->fetchAll();
$pageTitle = 'Mes commandes';
require __DIR__ . '/../includes/header.php';
?>
<h1>Mes commandes</h1>
<p class="muted">Suivez le traitement de vos commandes et leur état de paiement.</p>
<?php if (!$orders): ?><div class="empty-state">Vous n'avez pas encore passé de commande. <a href="/restaurant-gestion/client/menu.php">Voir la carte</a>.</div>
<?php else: ?>
<div class="table-wrap card"><table><thead><tr><th>N°</th><th>Date</th><th>Type</th><th>Table</th><th>Total</th><th>État</th><th>Paiement</th></tr></thead><tbody>
<?php foreach ($orders as $order): ?>
<tr><td>#<?= (int)$order['id'] ?></td><td><?= e(date('d/m/Y H:i', strtotime($order['created_at']))) ?></td><td><?= $order['order_type'] === 'sur_place' ? 'Sur place' : 'En ligne' ?></td><td><?= $order['table_number'] ? 'Table ' . (int)$order['table_number'] : '—' ?></td><td><?= money($order['total_amount']) ?></td><td><span class="status"><?= e(str_replace('_', ' ', $order['status'])) ?></span></td><td><?= $order['payment_status'] === 'paye' ? 'Payé' : 'À payer à la caisse' ?></td></tr>
<tr class="detail-row"><td colspan="7"><details><summary>Voir les plats</summary><ul>
<?php $lineStmt = $pdo->prepare('SELECT item_name, quantity, unit_price FROM order_items WHERE order_id = ?'); $lineStmt->execute([$order['id']]); foreach ($lineStmt->fetchAll() as $line): ?>
<li><?= e($line['item_name']) ?> × <?= (int)$line['quantity'] ?> — <?= money((float)$line['unit_price'] * (int)$line['quantity']) ?></li>
<?php endforeach; ?></ul></details></td></tr>
<?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
