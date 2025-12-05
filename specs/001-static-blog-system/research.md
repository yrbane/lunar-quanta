# Research: Blog Éco-Responsable avec HTML Statique

**Feature**: 001-static-blog-system
**Date**: 2025-12-05

## 1. Markdown Parser PHP Natif

### Decision
Implémenter un parser Markdown natif en PHP supportant CommonMark (subset) avec extension HTML inline.

### Rationale
- Constitution II (Zero Dependencies) interdit les dépendances runtime
- CommonMark est le standard le plus répandu et bien documenté
- Support HTML inline requis par la clarification utilisateur

### Alternatives Considered
| Alternative | Rejetée car |
|------------|-------------|
| league/commonmark | Dépendance externe - viole Constitution II |
| Parsedown | Dépendance externe - viole Constitution II |
| Michelf/php-markdown | Dépendance externe - viole Constitution II |

### Implementation Notes
- Supporter : headings, bold, italic, links, images, code blocks, lists, blockquotes
- HTML inline : passthrough avec sanitization (HtmlSanitizer)
- Preview live : endpoint AJAX retournant HTML rendu

---

## 2. NLP pour Suggestion de Tags

### Decision
Utiliser TF-IDF (Term Frequency-Inverse Document Frequency) implémenté en PHP natif pour l'extraction de mots-clés.

### Rationale
- TF-IDF est efficace pour identifier les termes significatifs d'un document
- Implémentable en PHP pur sans dépendance
- Performant sur textes de taille article de blog (< 10 000 mots)

### Alternatives Considered
| Alternative | Rejetée car |
|------------|-------------|
| API externe NLP (OpenAI) | Latence réseau, coût, dépendance externe |
| RAKE algorithm | Plus complexe, gain marginal pour ce cas d'usage |
| Simple frequency count | Moins précis (favorise les mots communs) |

### Implementation Notes
- Stopwords français et anglais intégrés
- Stemming basique (suffixes communs)
- Score de pertinence normalisé [0-1]
- Cache des IDF pour performances

---

## 3. API Pexels Integration

### Decision
Wrapper HTTP natif utilisant `file_get_contents` avec stream context pour l'API Pexels.

### Rationale
- API REST simple (GET requests)
- Pas besoin de SDK complet
- file_get_contents disponible partout

### Alternatives Considered
| Alternative | Rejetée car |
|------------|-------------|
| Guzzle HTTP | Dépendance externe - viole Constitution II |
| cURL direct | Plus verbeux, file_get_contents suffit |
| SDK officiel Pexels | Dépendance externe - viole Constitution II |

### Implementation Notes
- Endpoint : `https://api.pexels.com/v1/search`
- Header : `Authorization: {API_KEY}`
- Rate limit : 200 req/month (gratuit), pagination supportée
- Téléchargement local des images sélectionnées

---

## 4. API OpenAI DALL-E Integration

### Decision
Client HTTP natif pour l'API OpenAI Images (DALL-E 3).

### Rationale
- Compte ChatGPT Team existant avec accès API
- DALL-E 3 offre la meilleure qualité pour les prompts textuels

### Alternatives Considered
| Alternative | Rejetée car |
|------------|-------------|
| DALL-E 2 | Qualité inférieure, même coût |
| Midjourney | Pas d'API publique |
| Stable Diffusion local | Ressources serveur insuffisantes |

### Implementation Notes
- Endpoint : `https://api.openai.com/v1/images/generations`
- Model : `dall-e-3`
- Taille : 1024x1024 (standard)
- Format : PNG téléchargé localement
- Fallback vers Gemini si erreur

---

## 5. API Google Gemini Imagen Integration

### Decision
Client HTTP natif pour l'API Gemini avec génération d'images (Imagen).

### Rationale
- Compte Gemini Pro existant
- Alternative/fallback à DALL-E
- Modèle multimodal

### Alternatives Considered
| Alternative | Rejetée car |
|------------|-------------|
| Google Cloud Vision | Analyse seulement, pas de génération |
| Vertex AI | Configuration plus complexe |

### Implementation Notes
- API Gemini supporte la génération d'images via prompts
- Fallback automatique si DALL-E échoue
- Mêmes formats et dimensions que DALL-E

---

## 6. Génération HTML Statique

### Decision
Générateur PHP utilisant le système de templates Lunar existant, écrivant les fichiers dans `public/blog/`.

### Rationale
- Réutilise l'infrastructure existante (LunarTemplateAdapter)
- Fichiers statiques servis directement par Nginx/Apache
- Configuration serveur simple (try_files)

### Alternatives Considered
| Alternative | Rejetée car |
|------------|-------------|
| Hugo/Jekyll | Outils externes, complexité déploiement |
| Next.js SSG | Stack différente, overhead |
| Cache HTTP | Toujours exécution PHP (moins éco-responsable) |

### Implementation Notes
- Trigger : publication d'article
- Fichiers générés : post, index, catégories, tags, RSS, sitemap
- Invalidation : suppression + régénération
- Configuration Nginx : `try_files $uri $uri/index.html =404;`

---

## 7. Structure Catégories Hiérarchiques

### Decision
Modèle adjacency list (parent_id) avec méthodes de traversée récursive et cache des chemins.

### Rationale
- Simple à implémenter et comprendre
- Flexible pour modifications fréquentes
- Storage JSON compatible

### Alternatives Considered
| Alternative | Rejetée car |
|------------|-------------|
| Nested Set | Complexe pour insertions/suppressions |
| Materialized Path | Mise à jour coûteuse des chemins |
| Closure Table | Overhead pour petit volume |

### Implementation Notes
- Détection cycles : vérification lors de setParent()
- Méthodes : getAncestors(), getDescendants(), getDepth()
- Cache des arborescences complètes

---

## 8. RSS 2.0 Feed

### Decision
Template XML généré par le système de templates Lunar avec métadonnées complètes.

### Rationale
- RSS 2.0 est le standard le plus supporté
- Template simple, pas de logique complexe

### Alternatives Considered
| Alternative | Rejetée car |
|------------|-------------|
| Atom | Moins répandu, avantages marginaux |
| JSON Feed | Support lecteurs RSS limité |

### Implementation Notes
- Éléments : channel, item (title, link, description, pubDate, guid)
- Limite : 20 derniers articles
- Validation : W3C Feed Validator
- URL : `/blog/feed.xml`

---

## 9. Configuration Serveur Éco-Responsable

### Decision
Configuration Nginx recommandée pour servir les fichiers statiques sans toucher PHP.

### Rationale
- Objectif principal : 0% exécution PHP pour visiteurs publics
- Nginx plus performant que Apache pour fichiers statiques

### Implementation Notes

```nginx
# Configuration Nginx recommandée
server {
    listen 80;
    server_name blog.example.com;
    root /var/www/lunar-quanta/public;

    # Blog statique - pas de PHP
    location /blog {
        try_files $uri $uri/index.html =404;
        expires 1h;
        add_header Cache-Control "public, immutable";
    }

    # Admin - PHP requis
    location /admin {
        try_files $uri /index.php$is_args$args;
    }

    # PHP-FPM
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

---

## Summary

Toutes les recherches confirment la faisabilité avec **zero dépendances runtime** conformément à la Constitution. Les implémentations natives PHP pour Markdown et NLP sont réalisables dans les contraintes de performance définies.
