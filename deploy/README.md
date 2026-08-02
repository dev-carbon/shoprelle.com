# Déploiement shoprelle.com

Ce dossier contient la configuration serveur de production. Les commandes
courantes sont câblées dans le `Makefile` à la racine (`make help`).

## Cible

- **Serveur web** : Nginx
- **PHP** : 8.4 (FPM), socket `/run/php/php8.4-fpm.sock`. Le projet exige
  `^8.3` : 8.4 convient. Le 8.5 mentionné dans le `CLAUDE.md` est la version de
  développement local, pas celle de production. Le socket dans la config nginx
  et la variable `PHP_FPM` du Makefile doivent rester accordés.
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

1. **`client_max_body_size 100M`.** Les clients joignent des captures d'écran à
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

## Brancher Telegram

Le canal est entièrement codé — moteur, clavier, webhook, déduplication. Il ne
manque que la configuration, et elle est intégralement côté serveur.

### 1. Créer le bot

Dans Telegram, écrire à **@BotFather** : `/newbot`, un nom affiché, puis un nom
d'utilisateur qui doit finir par `bot`. BotFather renvoie le token.

Le token ne doit transiter que par le `.env` du serveur : quiconque l'a peut
lire et écrire toutes les conversations du bot.

### 2. Renseigner le `.env` du serveur

```dotenv
APP_URL=https://shoprelle.com
TELEGRAM_BOT_TOKEN=…            # donné par BotFather
TELEGRAM_BOT_USERNAME=…         # avec ou sans @, le code retire l'arobase
TELEGRAM_WEBHOOK_SECRET=…       # php -r 'echo bin2hex(random_bytes(24)), PHP_EOL;'
```

`APP_URL` n'est pas décoratif : c'est lui qui fabrique l'URL déclarée à
Telegram. Restée sur `localhost`, la commande refuse — Telegram n'accepte que
le HTTPS.

Le secret n'est pas optionnel. L'endpoint est public et non authentifié ; ce
secret, renvoyé par Telegram dans `X-Telegram-Bot-Api-Secret-Token`, est la
seule chose entre le bot et qui devine l'URL. Sans lui le middleware répond 403
à tout, plutôt que d'accepter n'importe quoi.

### 3. Recharger la configuration, puis déclarer le webhook

```bash
php artisan optimize      # sinon le .env est ignoré, voir ci-dessous
make telegram-webhook
make telegram-webhook-info
```

⚠️ **`make deploy` met la configuration en cache.** Modifier le `.env` sans
relancer `php artisan optimize` ne change rien : la commande répondra
« TELEGRAM_BOT_TOKEN is not set » alors que la variable est bien là, et la carte
Telegram de la page d'accueil restera en « Bientôt disponible ».

### 4. Vérifier

`make telegram-webhook-info` affiche le bot et l'état du webhook. Puis écrire
`/start` au bot : il doit répondre. Côté site, la carte Telegram de la section
« L'assistant » devient cliquable — elle n'apparaît comme lien que si le token
**et** le nom d'utilisateur sont tous deux définis : le nom fabrique l'URL
`t.me`, le token garantit que quelqu'un écoute derrière.

Le webhook est à redéclarer à chaque changement de domaine.

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

- Que `www-data` appartienne bien au groupe `www-data` sur ce serveur : les
  fichiers sont possédés par `carbon` et c'est par le groupe, en 775, que le
  pool FPM et le worker Supervisor obtiennent le droit d'écrire.

La config nginx de ce dossier reflète celle qui tourne déjà, à trois écarts
près, chacun signalé dans le fichier par « AJOUT » ou « DURCI » : HTTP/2, la
mise en cache définitive des assets `/build`, et le passage à FPM restreint au
seul contrôleur frontal. Relire ces trois points avant le premier
`make install-nginx`, puisque cette cible écrase le fichier du serveur.

## Installation de Node

Node doit être installé système via APT plutôt qu'avec `nvm`, pour que
`www-data` y accède sans dépendre du PATH d'un home. La procédure complète
figure dans `halaye.com/deploy/README.md` et n'a pas à être refaite par site.
