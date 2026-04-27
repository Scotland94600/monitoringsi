# Changelog

Toutes les évolutions notables du projet sont documentées ici.

Le format suit [Keep a Changelog](https://keepachangelog.com/fr/1.1.0/) et la version suit [SemVer](https://semver.org/lang/fr/).

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
