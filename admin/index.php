<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_role('admin');

$today = $pdo->query("SELECT COUNT(*) AS n, COALESCE(SUM(total_amount),0) AS total FROM orders WHERE DATE(created_at) = CURDATE()")->fetch();
$unpaid = $pdo->query("SELECT COUNT(*) AS n FROM orders WHERE payment_status = 'non_paye'")->fetch()['n'];
$clients = $pdo->query("SELECT COUNT(*) AS n FROM users WHERE role = 'client'")->fetch()['n'];
$tables = $pdo->query("SELECT COUNT(*) AS total, SUM(status = 'libre') AS free FROM restaurant_tables")->fetch();
$recent = $pdo->query("SELECT o.id, o.created_at, o.total_amount, o.status, o.payment_status, u.name FROM orders o JOIN users u ON u.id=o.user_id ORDER BY o.created_at DESC LIMIT 8")->fetchAll();
$pageTitle = 'Administration';
require __DIR__ . '/../includes/header.php';
?>
<p class="eyebrow">ESPACE ADMINISTRATEUR</p><h1>Tableau de bord</h1><p class="muted">Vue d'ensemble de l'activité du restaurant.</p>
<div class="grid grid-4 stats">
    <article class="stat-card"><span>Commandes aujourd'hui</span><strong><?= (int)$today['n'] ?></strong><small>toutes commandes</small></article>
    <article class="stat-card"><span>Montant commandé aujourd'hui</span><strong><?= money($today['total']) ?></strong><small>recettes commandées, pas uniquement encaissées</small></article>
    <article class="stat-card"><span>À encaisser</span><strong><?= (int)$unpaid ?></strong><small>commandes non payées</small></article>
    <article class="stat-card"><span>Tables libres</span><strong><?= (int)($tables['free'] ?? 0) ?>/<?= (int)$tables['total'] ?></strong><small><?= (int)$clients ?> compte(s) client</small></article>
</div>
<div class="page-heading section"><h2>Commandes récentes</h2><a class="button button-outline" href="/restaurant-gestion/admin/orders.php">Gérer les commandes</a></div>
<?php if (!$recent): ?><div class="empty-state">Aucune commande pour le moment.</div><?php else: ?>
<div class="table-wrap card"><table><thead><tr><th>Commande</th><th>Client</th><th>Date</th><th>Montant</th><th>État</th><th>Paiement</th></tr></thead><tbody>
<?php foreach ($recent as $r): ?><tr><td>#<?= (int)$r['id'] ?></td><td><?= e($r['name']) ?></td><td><?= e(date('d/m/Y H:i', strtotime($r['created_at']))) ?></td><td><?= money($r['total_amount']) ?></td><td><?= e(str_replace('_', ' ', $r['status'])) ?></td><td><?= $r['payment_status'] === 'paye' ? 'Payé' : 'Non payé' ?></td></tr><?php endforeach; ?>
</tbody></table></div>
<?php endif; ?>
<?php require __DIR__ . '/../includes/footer.php'; ?>
