# Gestion de restaurant — PHP / MySQL / WAMP

Application pédagogique avec deux rôles :
- **Administrateur** : tableau de bord, gestion des menus, commandes, caisse et tables.
- **Client** : inscription, connexion, consultation des plats, panier, commande sur place ou en ligne, suivi de commandes.
- **Paiement** : à la caisse uniquement.
- **Devise** : ariary malgache (MGA / Ar).

## 1. Prérequis
- Windows et WAMP avec Apache, PHP 8.1+ recommandé, MySQL/MariaDB.
- Extension PHP `pdo_mysql` activée.
- Un navigateur récent.

## 2. Installation
1. Décompressez le dossier `restaurant-gestion` dans `C:\wamp64\www\`.
2. Démarrez WAMP et attendez que l'icône soit verte.
3. Ouvrez `http://localhost/phpmyadmin/`.
4. Importez le fichier `database.sql` (onglet **Importer**).
   - Le script crée la base `restaurant_db`, les tables et les données de démonstration.
   - ATTENTION : le script réinitialise les tables de cette application s'il est réimporté. N'utilisez pas ce script pour réinitialiser une base contenant de vraies données.
5. Ouvrez `config/config.php`. Par défaut, la connexion utilise `root` et un mot de passe vide, comme certaines installations WAMP locales. Modifiez `$dbpass` si votre MySQL a un mot de passe.
6. Visitez `http://localhost/restaurant-gestion/`.

## 3. Comptes de démonstration
- Administrateur : `admin@restaurant.local` / `Admin123!`
- Client : `client@restaurant.local` / `Client123!`

Changez les mots de passe de démonstration avant tout usage réel. Ne publiez pas cette application sur Internet sans configuration de production (HTTPS, secrets protégés, sauvegardes, limitation des tentatives, journalisation, politique de confidentialité).

## 4. Parcours de test
### Test client
1. Connectez-vous avec le compte client de démonstration.
2. Ouvrez **Carte**, ajoutez deux ou trois plats au panier.
3. Ouvrez **Panier**, modifiez les quantités et vérifiez le total.
4. Passez une commande **en ligne**. Le paiement doit être marqué « à payer à la caisse ».
5. Recommencez avec **sur place**, en choisissant une table libre.
6. Ouvrez **Mes commandes** et vérifiez les détails.

### Test administrateur
1. Déconnectez-vous puis connectez-vous avec le compte admin.
2. Vérifiez le tableau de bord.
3. Dans **Menus**, ajoutez un plat, modifiez-le puis désactivez-le.
4. Dans **Commandes**, passez une commande en préparation puis prête.
5. Cliquez sur **Encaisser** uniquement après avoir simulé la réception du paiement.
6. Marquez ensuite la commande comme terminée. Une table utilisée par une commande sur place redevient libre à la clôture.
7. Dans **Tables**, ajoutez une table et modifiez son état.

### Test de sécurité
1. Connecté comme client, essayez d'ouvrir `http://localhost/restaurant-gestion/admin/index.php` : l'accès doit être refusé.
2. Déconnectez-vous puis tentez d'ouvrir `client/orders.php` : la page doit rediriger vers la connexion.
3. Envoyez un formulaire sans jeton CSRF ou avec un jeton modifié : la requête doit être rejetée.
4. Saisissez un nom de plat contenant `<script>alert(1)</script>` : le texte doit être affiché échappé, sans exécuter le script.
5. Vérifiez que les mots de passe sont stockés sous forme de hachages dans `users.password_hash`, jamais en clair.

## 5. Structure des fichiers
- `config/config.php` : connexion PDO et session.
- `includes/functions.php` : fonctions de sécurité, rôle, jeton CSRF et formatage MGA.
- `login.php`, `register.php`, `logout.php` : authentification.
- `client/` : carte, panier, commandes.
- `admin/` : tableau de bord, menus, commandes/caisse, tables.
- `assets/` : CSS et JavaScript.
- `database.sql` : base de données et données de démonstration.

## 6. Protections intégrées et limites
- PDO avec requêtes préparées pour les données utilisateur.
- Hachage des mots de passe avec `password_hash()` / vérification avec `password_verify()`.
- Contrôle des rôles côté serveur sur les pages protégées.
- Jetons CSRF sur les formulaires de modification.
- Échappement HTML avec `htmlspecialchars()`.
- Régénération de l'identifiant de session après connexion et cookie HttpOnly/SameSite.
- Prix recalculés depuis MySQL côté serveur au moment de la commande.
- Les paiements sont **enregistrés manuellement** par l'admin ; aucune passerelle bancaire n'est intégrée.
- Application de démonstration : avant un usage professionnel, ajouter une limitation des tentatives de connexion, logs, sauvegardes, gestion des annulations/remboursements, reçus imprimables, politique de confidentialité, HTTPS et tests complets.
