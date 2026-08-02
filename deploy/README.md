# Déploiement shoprelle.com

Ce dossier contient la configuration serveur de production. Les commandes
courantes sont câblées dans le `Makefile` à la racine (`make help`).

## Cible

- **Serveur web** : Nginx
- **PHP** : 8.5 (FPM) — c'est la version que déclare ce projet, et ce n'est pas
  celle des deux autres sites du serveur. Le socket dans la config nginx et la
  variable `PHP_FPM` du Makefile doivent s'accorder.
- **Base** : SQLite (`database/database.sqlite`)
- **File d'attente** : `database`, traitée par Supervisor
- **Planificateur** : requis — voir plus bas
- **Racine du projet** : `/var/www/shoprelle.com`

## Contenu

```
deploy/
├── nginx/
│   └── shoprelle.com            → /etc/nginx/sites-available/shoprelle.com
└── supervisor/
    └── shoprelle-worker.conf    → /etc/supervisor/conf.d/shoprelle-worker.conf
```

> Le fichier nginx est nommé `shoprelle.com` sans extension `.conf`, pour que
> **certbot** le retrouve par nom de domaine lors des renouvellements.

## Ce qui diffère des deux autres sites

Trois points ne se recopient pas de `halaye.com` ni de `elearning.halaye.com` :

1. **`client_max_body_size 24m`.** Les clients joignent des captures d'écran à
   leur demande — jusqu'à 5 Mo par fichier, trois par article. La valeur par
   défaut de nginx est de 1 Mo : sans cette ligne l'envoi échoue sur un 413
   avant même que Laravel ne voie la requête.
2. **Un cron de planificateur.** `routes/console.php` purge chaque nuit à 03:00
   les captures déposées par des conversations jamais confirmées. Aucun des
   deux autres sites n'a de tâche planifiée, donc aucun n'a ce cron. Sans lui
   les fichiers s'accumulent indéfiniment.
3. **Pas de SSR.** Il n'y a pas d'entrée `ssr.tsx` ici, donc pas de second
   programme Supervisor à installer, contrairement à elearning.

## Installation initiale

```bash
cd /var/www/shoprelle.com

make install-configs    # nginx + supervisor + cron du planificateur
make permissions        # storage, bootstrap/cache, database.sqlite
make install            # composer + npm ci + build
sudo supervisorctl start shoprelle-worker:*
```

Puis, une fois le domaine résolu et le certificat émis :

```bash
make admin              # crée le premier compte du back-office
make telegram-webhook   # déclare l'URL du bot chez Telegram
```

Le webhook Telegram est à rejouer **à chaque changement de domaine** : le bot
n'écoute que l'URL qui lui a été déclarée. `make telegram-webhook-info` dit
laquelle est enregistrée.

## Vérifier que le planificateur tourne

```bash
make schedule                       # doit lister la purge quotidienne
sudo crontab -u www-data -l         # doit contenir la ligne schedule:run
```

## Mises à jour de la config

Après modification d'un fichier de ce dossier :

```bash
make install-configs
make restart            # workers + nginx + php-fpm
```

## Déploiement applicatif

Sur le serveur, après un `git pull` :

```bash
make deploy
```

La cible installe les dépendances de production, vide les caches **avant** le
build npm — Wayfinder démarre Laravel pendant ce build, et un cache de config
périmé qui référence un provider de dev dep désinstallé le ferait planter —
construit les assets, migre, régénère les caches et redémarre le worker.

## À vérifier avant le premier déploiement

Deux valeurs ne sont pas devinables depuis le dépôt et sont à confirmer sur le
serveur :

- Le socket `php8.5-fpm.sock` dans la config nginx, si le serveur fait tourner
  une autre version. Il doit s'accorder avec `PHP_FPM` dans le `Makefile`.
- Que `www-data` appartienne bien au groupe `www-data` sur ce serveur : les
  fichiers sont possédés par `carbon` et c'est par le groupe, en 775, que le
  pool FPM et le worker Supervisor obtiennent le droit d'écrire.

## Installation de Node

Node doit être installé système via APT plutôt qu'avec `nvm`, pour que
`www-data` y accède sans dépendre du PATH d'un home. La procédure complète
figure dans `halaye.com/deploy/README.md` et n'a pas à être refaite par site.
