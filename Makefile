.PHONY: help install build deploy maintenance-on maintenance-off \
        restart restart-workers restart-nginx restart-fpm \
        install-configs install-nginx install-supervisor install-cron \
        admin telegram-webhook telegram-webhook-info prune \
        logs logs-workers schedule permissions

# Chemins absolus côté serveur de production
PROJECT_DIR     := /var/www/shoprelle.com
NGINX_SRC       := deploy/nginx/shoprelle.com
NGINX_DEST      := /etc/nginx/sites-available/shoprelle.com
SUPERVISOR_SRC  := deploy/supervisor/shoprelle-worker.conf
SUPERVISOR_DEST := /etc/supervisor/conf.d/shoprelle-worker.conf

# PHP 8.4 : la version installée sur le serveur, confirmée par la config nginx
# en place. Le projet exige ^8.3, donc 8.4 convient — le 8.5 du CLAUDE.md est
# la version de développement local, pas celle de production.
# Doit rester accordé au socket fastcgi_pass de deploy/nginx/shoprelle.com.
PHP_FPM         := php8.4-fpm

# Propriétaire des fichiers écrits par l'application.
#
# `carbon` possède, `www-data` est le groupe : avec un chmod 775, l'utilisateur
# applicatif écrit en tant que propriétaire et le pool FPM comme le worker
# Supervisor écrivent via le groupe. C'est aussi la crontab de cet utilisateur
# que `install-cron` renseigne.
WEB_USER        := carbon
WEB_GROUP       := www-data

help:
	@echo ""
	@echo "  ── Cycle de vie applicatif ──────────────────────────────"
	@echo "  make deploy            Déploiement complet (composer --no-dev, npm build, migrate, cache, workers)"
	@echo "  make install           Installe les dépendances composer + npm + build"
	@echo "  make build             Compile les assets npm"
	@echo "  make maintenance-on    Active le mode maintenance"
	@echo "  make maintenance-off   Désactive le mode maintenance"
	@echo ""
	@echo "  ── Redémarrage des services ─────────────────────────────"
	@echo "  make restart           Redémarre workers + nginx + php-fpm"
	@echo "  make restart-workers   Redémarre les workers Supervisor"
	@echo "  make restart-nginx     Recharge la config nginx"
	@echo "  make restart-fpm       Redémarre $(PHP_FPM)"
	@echo ""
	@echo "  ── Installation serveur (premier déploiement) ───────────"
	@echo "  make install-configs   Copie nginx + supervisor depuis deploy/"
	@echo "  make install-nginx     Copie uniquement la config nginx"
	@echo "  make install-supervisor Copie uniquement la config supervisor"
	@echo "  make install-cron      Installe le cron du planificateur Laravel"
	@echo ""
	@echo "  ── Exploitation ──────────────────────────────────────────"
	@echo "  make admin             Crée ou promeut un administrateur"
	@echo "  make telegram-webhook  Enregistre le webhook Telegram sur l'URL publique"
	@echo "  make telegram-webhook-info  Affiche l'état du webhook Telegram"
	@echo "  make prune             Purge les pièces jointes jamais confirmées"
	@echo "  make schedule          Liste les tâches planifiées"
	@echo "  make permissions       Corrige les permissions storage / bootstrap/cache / sqlite"
	@echo "  make logs              Suit les logs Laravel en temps réel (pail)"
	@echo "  make logs-workers      Suit les logs des workers Supervisor"
	@echo ""

# ── Cycle de vie applicatif ───────────────────────────────────────

install:
	composer install --optimize-autoloader
	npm ci && npm run build

build:
	npm run build

deploy:
	# --no-dev exclut Laravel Boost, Pest, Larastan et le reste des dev deps.
	composer install --optimize-autoloader --no-dev
	# Les caches sont vidés AVANT le build : Wayfinder démarre Laravel pendant
	# le build npm, et un bootstrap/cache/config.php périmé qui référence un
	# provider de dev dep désinstallé (Boost) ferait planter ce build.
	php artisan optimize:clear
	npm ci && npm run build && npm prune --omit=dev
	php artisan migrate --force
	php artisan optimize
	# Les notifications d'achat implémentent ShouldQueue sur la file `database` :
	# sans ce redémarrage le worker continue d'exécuter l'ancien code.
	sudo supervisorctl restart shoprelle-worker:*

maintenance-on:
	php artisan down --retry=60

maintenance-off:
	php artisan up

# ── Redémarrage des services ─────────────────────────────────────

restart-workers:
	sudo supervisorctl restart shoprelle-worker:*

restart-nginx:
	sudo nginx -t && sudo systemctl reload nginx

restart-fpm:
	sudo systemctl restart $(PHP_FPM)

restart: restart-workers restart-nginx restart-fpm

# ── Installation des configs serveur ─────────────────────────────

install-configs: install-nginx install-supervisor install-cron
	@echo "→ Configs installées. Pensez à 'make restart-nginx'."

install-nginx:
	@test -f $(NGINX_SRC) || { echo "✗ $(NGINX_SRC) est introuvable — la config nginx de ce site n'a pas encore été écrite."; exit 1; }
	sudo cp $(NGINX_SRC) $(NGINX_DEST)
	@if [ ! -L /etc/nginx/sites-enabled/shoprelle.com ]; then \
		sudo ln -s $(NGINX_DEST) /etc/nginx/sites-enabled/shoprelle.com; \
	fi
	sudo nginx -t
	sudo systemctl reload nginx

install-supervisor:
	@test -f $(SUPERVISOR_SRC) || { echo "✗ $(SUPERVISOR_SRC) est introuvable — la config du worker n'a pas encore été écrite."; exit 1; }
	sudo cp $(SUPERVISOR_SRC) $(SUPERVISOR_DEST)
	sudo supervisorctl reread
	sudo supervisorctl update

# Le planificateur, que les deux autres sites n'ont pas : `routes/console.php`
# purge chaque nuit à 03:00 les captures d'écran déposées par des conversations
# jamais confirmées. Sans cette ligne de cron, elles s'accumulent indéfiniment.
# Idempotent : la crontab n'est réécrite que si la ligne en est absente.
install-cron:
	@line="* * * * * cd $(PROJECT_DIR) && php artisan schedule:run >> /dev/null 2>&1"; \
	if sudo crontab -u $(WEB_USER) -l 2>/dev/null | grep -Fq "$(PROJECT_DIR) && php artisan schedule:run"; then \
		echo "→ Cron du planificateur déjà en place pour $(WEB_USER)."; \
	else \
		{ sudo crontab -u $(WEB_USER) -l 2>/dev/null; echo "$$line"; } | sudo crontab -u $(WEB_USER) -; \
		echo "→ Cron du planificateur installé pour $(WEB_USER)."; \
	fi

# ── Exploitation ──────────────────────────────────────────────────

admin:
	php artisan shoprelle:make-admin

# Le bot n'écoute qu'une fois son webhook déclaré chez Telegram, et l'URL
# change à chaque changement de domaine — donc après chaque premier déploiement.
telegram-webhook:
	php artisan shoprelle:telegram-webhook set

telegram-webhook-info:
	php artisan shoprelle:telegram-webhook info

prune:
	php artisan shoprelle:prune-pending-attachments

schedule:
	php artisan schedule:list

permissions:
	mkdir -p storage/pail storage/logs storage/framework/cache storage/framework/sessions storage/framework/views
	sudo chown -R $(WEB_USER):$(WEB_GROUP) storage bootstrap/cache
	# 775 sur les dossiers, 664 sur les fichiers, et non un `chmod -R 775` sur
	# les deux. Le mode récursif pose aussi le bit exécutable sur les fichiers,
	# y compris les .gitignore que git suit dans storage/ et bootstrap/cache/ ;
	# git enregistre ce bit, et le serveur se retrouve avec des fichiers
	# « modifiés » sans qu'une seule ligne ait changé — ce qui bloque le
	# prochain git pull.
	sudo find storage bootstrap/cache -type d -exec chmod 775 {} +
	sudo find storage bootstrap/cache -type f -exec chmod 664 {} +
	sudo chown $(WEB_USER):$(WEB_GROUP) database/database.sqlite
	sudo chmod 664 database/database.sqlite
	sudo chown $(WEB_USER):$(WEB_GROUP) database
	sudo chmod 775 database

logs:
	php artisan pail

logs-workers:
	sudo supervisorctl tail -f shoprelle-worker:shoprelle-worker_00
