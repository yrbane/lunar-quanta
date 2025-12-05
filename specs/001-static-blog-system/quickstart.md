# Quickstart: Blog Éco-Responsable

**Feature**: 001-static-blog-system
**Date**: 2025-12-05

## Prérequis

- PHP 8.3+ avec extensions : json, mbstring, gd
- Serveur web (Nginx recommandé ou Apache)
- Clé API Pexels (optionnel)
- Clé API OpenAI (optionnel)
- Clé API Google Gemini (optionnel)

## Configuration

### 1. Variables d'environnement

Créer/modifier `.env` à la racine :

```bash
# APIs Images (optionnel)
PEXELS_API_KEY=your_pexels_api_key
OPENAI_API_KEY=your_openai_api_key
GEMINI_API_KEY=your_gemini_api_key

# Blog configuration
BLOG_TITLE="Mon Blog"
BLOG_DESCRIPTION="Blog éco-responsable"
BLOG_URL=https://blog.example.com
BLOG_STATIC_PATH=public/blog
```

### 2. Configuration Nginx (Production)

```nginx
server {
    listen 80;
    server_name blog.example.com;
    root /var/www/lunar-quanta/public;

    # Blog statique - servi directement sans PHP
    location /blog {
        try_files $uri $uri/index.html =404;
        expires 1h;
        add_header Cache-Control "public, immutable";
    }

    # Admin - nécessite PHP
    location /admin {
        try_files $uri /index.php$is_args$args;
    }

    # PHP-FPM (seulement pour /admin)
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### 3. Configuration Apache (Alternative)

```apache
<VirtualHost *:80>
    ServerName blog.example.com
    DocumentRoot /var/www/lunar-quanta/public

    # Blog statique
    <Directory /var/www/lunar-quanta/public/blog>
        Options -Indexes
        DirectoryIndex index.html
        <IfModule mod_expires.c>
            ExpiresActive On
            ExpiresDefault "access plus 1 hour"
        </IfModule>
    </Directory>

    # Admin - PHP
    <Directory /var/www/lunar-quanta/public>
        <FilesMatch \.php$>
            SetHandler "proxy:unix:/var/run/php/php8.3-fpm.sock|fcgi://localhost"
        </FilesMatch>
    </Directory>

    # Rewrite pour admin
    RewriteEngine On
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_URI} ^/admin
    RewriteRule ^ index.php [L]
</VirtualHost>
```

## Utilisation

### Administration

1. Accéder à `/admin` (authentification requise)
2. **Articles** : créer, éditer, publier
3. **Catégories** : organiser en hiérarchie
4. **Images** : upload, Pexels, ou génération IA
5. **Publier** : génère automatiquement les HTML statiques

### Workflow de publication

```
┌─────────────────────────────────────────────────────────────────┐
│                     WORKFLOW PUBLICATION                        │
├─────────────────────────────────────────────────────────────────┤
│                                                                 │
│  1. Rédaction          2. Enrichissement       3. Publication   │
│     ┌─────────┐           ┌─────────┐            ┌─────────┐   │
│     │ Markdown│           │ Tags    │            │ Generate│   │
│     │ + HTML  │ ────────► │ Catégorie│ ────────► │ Static  │   │
│     │ Preview │           │ Image   │            │ HTML    │   │
│     └─────────┘           └─────────┘            └─────────┘   │
│                                                       │         │
│                                                       ▼         │
│                                               ┌─────────────┐   │
│                                               │ public/blog │   │
│                                               │ (statique)  │   │
│                                               └─────────────┘   │
└─────────────────────────────────────────────────────────────────┘
```

### URLs générées

| URL | Description |
|-----|-------------|
| `/blog/` | Page d'accueil (derniers articles) |
| `/blog/posts/{slug}.html` | Article individuel |
| `/blog/categories/{slug}/` | Articles par catégorie |
| `/blog/tags/{slug}/` | Articles par tag |
| `/blog/feed.xml` | Flux RSS |

### API Admin

Endpoints disponibles (voir `contracts/api-blog.yaml`) :

```bash
# Créer un article
POST /admin/api/posts

# Publier (génère HTML)
POST /admin/api/posts/{id}/publish

# Suggestions de tags
POST /admin/api/suggestions/tags

# Recherche Pexels
GET /admin/api/images/pexels/search?query=nature

# Générer image IA
POST /admin/api/images/generate
```

## Tests

```bash
# Tous les tests
./vendor/bin/phpunit

# Tests blog uniquement
./vendor/bin/phpunit tests/Service/Blog/
./vendor/bin/phpunit tests/Service/Content/
./vendor/bin/phpunit tests/Service/StaticSite/

# Avec couverture
./vendor/bin/phpunit --coverage-html coverage/
```

## Vérifications Éco-Responsabilité

Pour vérifier que le blog statique fonctionne sans PHP :

```bash
# 1. Vérifier les logs Nginx (pas d'appel PHP pour /blog)
tail -f /var/log/nginx/access.log | grep "blog"

# 2. Tester un article publié
curl -I https://blog.example.com/blog/posts/mon-article.html
# Doit retourner 200 sans X-Powered-By: PHP

# 3. Valider le RSS
curl https://blog.example.com/blog/feed.xml | xmllint --noout -
```

## Troubleshooting

### Génération HTML échoue

```bash
# Vérifier les permissions
ls -la public/blog/
chmod -R 755 public/blog/

# Vérifier l'espace disque
df -h
```

### Images IA non générées

```bash
# Tester la clé API
curl -H "Authorization: Bearer $OPENAI_API_KEY" \
     https://api.openai.com/v1/models

# Vérifier les logs
tail -f data/logs/blog.log
```

### Suggestions de tags lentes

- Vérifier que le cache IDF est généré
- Réduire le nombre de mots analysés
- Augmenter le seuil de pertinence
