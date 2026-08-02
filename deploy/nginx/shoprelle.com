# ──────────────────────────────────────────────────────────────────
#  SHOPRELLE — Configuration nginx de production
#  Hôte    : shoprelle.com / www.shoprelle.com
#  PHP-FPM : 8.5   (doit correspondre à PHP_FPM dans le Makefile)
#  Racine  : /var/www/shoprelle.com/public
#
#  Installer / mettre à jour via `make install-nginx`.
#
#  ⚠️ Le fichier est volontairement nommé `shoprelle.com`, sans extension
#     `.conf` : certbot le retrouve par nom de domaine lors des
#     renouvellements automatiques.
#  ⚠️ Vérifier que les chemins ssl_certificate correspondent à ceux générés
#     par certbot avant de réinstaller, pour ne pas casser le SSL.
# ──────────────────────────────────────────────────────────────────

# ── HTTP → HTTPS, en laissant passer le renouvellement certbot ─────
server {
    listen 80;
    listen [::]:80;
    server_name shoprelle.com www.shoprelle.com;

    location ^~ /.well-known/acme-challenge/ {
        root /var/www/html;
        allow all;
    }

    location / {
        return 301 https://$host$request_uri;
    }
}

# ── HTTPS ──────────────────────────────────────────────────────────
server {
    # `listen … http2` et non `http2 on;` : la seconde forme n'existe qu'à
    # partir de nginx 1.25.1, et la cible est Ubuntu 22.04 LTS, qui livre
    # nginx 1.18. `nginx -t` rejetterait la directive.
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name shoprelle.com www.shoprelle.com;
    root /var/www/shoprelle.com/public;

    index index.php;
    charset utf-8;

    # Certificats Let's Encrypt (gérés par certbot)
    ssl_certificate     /etc/letsencrypt/live/shoprelle.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/shoprelle.com/privkey.pem;
    include             /etc/letsencrypt/options-ssl-nginx.conf;
    ssl_dhparam         /etc/letsencrypt/ssl-dhparams.pem;

    # Sécurité
    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    # Les clients joignent des captures d'écran à leur demande : jusqu'à
    # 5 Mo par fichier et trois par article (cf. config/shoprelle.php).
    # La valeur par défaut de nginx est de 1 Mo, ce qui ferait échouer
    # l'envoi par un 413 avant même que Laravel ne voie la requête.
    client_max_body_size 24m;

    # Compression
    gzip on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml image/svg+xml;
    gzip_min_length 1024;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Vite empreinte le nom de chaque asset construit, donc un fichier de
    # /build ne change jamais de contenu sous un même nom : il peut être mis
    # en cache définitivement, et un déploiement en publie de nouveaux plutôt
    # que d'invalider les anciens.
    location ^~ /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 4 256k;
        fastcgi_busy_buffers_size 256k;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
