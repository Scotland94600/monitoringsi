# MonitoringSI (PHP + MariaDB) – mutualisé IONOS

Application de monitoring installable en PHP/MariaDB avec authentification obligatoire, rôles utilisateurs, permissions par module, et administration dashboard drag-and-drop.

## Ce qui est en place

- Authentification obligatoire pour accéder à l'application.
- Rôles : `user`, `admin`, `super_admin`.
- Le `super_admin` a tous les droits et attribue les droits modules aux admins.
- Logs de connexion et actions admin (`auth_logs`).
- Dashboard public (accès après login).
- Espace admin pour éditer les widgets en drag-and-drop.
- Gestion des assets (si permission module).

## Arborescence

- `public/` : interface dashboard (nécessite login).
- `admin/` : login, administration, gestion utilisateurs/droits.
- `app/` : bootstrap, base de données, auth, repository dashboard.
- `install/sql/schema.sql` : schéma + seed modules + seed super admin.
- `modules/` : futurs connecteurs.

## Installation rapide (IONOS)

1. Créer une base MariaDB.
2. Importer `install/sql/schema.sql`.
3. Copier `config/config.php.example` vers `config/config.php` et renseigner la DB.
4. Déployer via FTP/SFTP.
5. Ouvrir `/admin/login.php`.

## Compte initial

- Utilisateur: `superadmin`
- Mot de passe initial: `ChangeMe123!`
- ⚠️ Changer le mot de passe immédiatement après installation.

## Gestion des droits modules

Depuis `/admin/users.php` (super_admin uniquement), vous pouvez:

- Créer des comptes `admin`.
- Activer leurs droits par module installé (dashboard_editor, asset_manager, etc.).

## Étapes suivantes recommandées

1. Ajouter un écran de changement de mot de passe et politique forte.
2. Ajouter un CRUD des modules installés depuis l'interface.
3. Ajouter rôles fins (lecture seule, opérateur réseau, etc.).
4. Connecter les modules de télémétrie réels (Free Pro, SNMP, agents OS).
