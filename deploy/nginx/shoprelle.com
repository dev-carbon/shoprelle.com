# ──────────────────────────────────────────────────────────────────
#  SHOPRELLE — Configuration nginx de production
#  Hôte    : shoprelle.com / www.shoprelle.com
#  PHP-FPM : 8.4  (doit correspondre à PHP_FPM dans le Makefile)
#  Racine  : /var/www/shoprelle.com/public
#
#  Installer / mettre à jour via `make install-nginx`.
#
#  Ce fichier est le reflet de ce qui tourne sur le serveur, à trois écarts
#  près, tous signalés ci-dessous par un commentaire « AJOUT » ou « DURCI ».
#  Le reste — tailles de corps, tampons et délais FastCGI — est repris tel
#  quel : ce sont des valeurs éprouvées en production, pas des défauts.
#
#  ⚠️ Le fichier est volontairement nommé `shoprelle.com`, sans extension
#     `.conf` : certbot le retrouve par nom de domaine lors des
#     renouvellements automatiques.
#  ⚠️ Les lignes SSL sont gérées par certbot. Vérifier qu'elles correspondent
#     toujours à ce qu'il a généré avant de réinstaller ce fichier, sous peine
#     de casser le HTTPS.
#  ⚠️ La compression n'est pas déclarée ici : elle l'est globalement dans
#     /etc/nginx/nginx.conf. Ne pas la dupliquer.
# ──────────────────────────────────────────────────────────────────

server {
    # AJOUT — `http2` : la forme `listen … http2` et non `http2 on;`, qui
    # n'existe qu'à partir de nginx 1.25.1. Gain net sur une page qui charge
    # plusieurs chunks JS et une police.
    listen 443 ssl http2;
    server_name shoprelle.com www.shoprelle.com;
    root /var/www/shoprelle.com/public;

    ssl_certificate /etc/letsencrypt/live/shoprelle.com/fullchain.pem; # managed by Certbot
    ssl_certificate_key /etc/letsencrypt/live/shoprelle.com/privkey.pem; # managed by Certbot
    include /etc/letsencrypt/options-ssl-nginx.conf; # managed by Certbot
    ssl_dhparam /etc/letsencrypt/ssl-dhparams.pem; # managed by Certbot

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;
    charset utf-8;

    # Les clients joignent des captures d'écran à leur demande : jusqu'à 5 Mo
    # par fichier et trois par article (cf. config/shoprelle.php). Le défaut
    # de nginx est de 1 Mo, ce qui ferait échouer l'envoi sur un 413 avant même
    # que Laravel ne voie la requête.
    client_max_body_size 100M;
    client_body_buffer_size 128k;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # AJOUT — Vite empreinte le nom de chaque asset construit, donc un fichier
    # de /build ne change jamais de contenu sous un même nom : il peut être mis
    # en cache définitivement, et un déploiement en publie de nouveaux plutôt
    # que d'invalider les anciens. `^~` arrête l'évaluation des locations
    # regex, pour qu'aucune ne vienne intercepter un asset.
    location ^~ /build/ {
        expires 1y;
        add_header Cache-Control "public, immutable";
        access_log off;
        try_files $uri =404;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    # DURCI — `^/index\.php(/|$)` plutôt que `\.php$`, qui est la forme
    # recommandée par Laravel. La seconde passe à FPM tout chemin finissant par
    # `.php`, y compris ceux qui n'existent pas ; celle-ci n'y passe que le
    # contrôleur frontal. Sans risque ici : `public/index.php` est le seul
    # fichier PHP de la racine web, vérifié.
    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;

        fastcgi_buffering on;
        fastcgi_buffers 16 16k;
        fastcgi_buffer_size 32k;
        fastcgi_busy_buffers_size 32k;
        fastcgi_temp_file_write_size 32k;

        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}

server {
    listen 80;
    server_name shoprelle.com www.shoprelle.com;
    return 301 https://$host$request_uri;
}
