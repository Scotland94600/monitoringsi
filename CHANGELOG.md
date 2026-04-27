# Changelog

Toutes les évolutions notables du projet sont documentées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et la version suit [SemVer](https://semver.org/lang/fr/).

## [1.0.0] - 2026-04-27

### Ajouté
- Base applicative MonitoringSI prête pour hébergement mutualisé PHP + MariaDB.
- Installateur web `install.php` pour l’installation complète (configuration DB, création des tables, création super administrateur, génération `config/config.php`).
- Authentification et rôles (`user`, `admin`, `super_admin`) avec permissions par module.
- Espace d’administration avec éditeur dashboard drag-and-drop, gestion des widgets et upload d’assets.
- Gestion des utilisateurs administrateurs et attribution des droits modules par le super administrateur.
- Dashboard public protégé par authentification.
- Script de release `scripts/bump_version.php` + fichier `VERSION` pour la montée de version.

### Sécurité
- Mots de passe hashés (`password_hash` / `password_verify`).
- Contrôles d’accès par rôle et par module.
- Protection CSRF sur les formulaires et endpoints d’administration.
- Journalisation des actions d’authentification dans `auth_logs`.

## [0.2.0] - 2026-04-27

### Ajouté
- Installateur web `install.php` pour la configuration complète (DB + super admin + config générée).
- Gestion des rôles utilisateur/admin/super_admin et permissions par module.
- Espace admin avec éditeur dashboard drag-and-drop et upload d'assets.

### Sécurité
- Hash des mots de passe (`password_hash`) et vérification (`password_verify`).
- Contrôles d'accès par rôle/module et protections CSRF sur les endpoints admin.

## [0.1.0] - 2026-04-27

### Ajouté
- Première base applicative PHP + MariaDB pour MonitoringSI.
- Dashboard public avec widgets persistés en base.
