# Auramur Headless CMS

Mini CMS headless conçu avec **Symfony 6.4** et **API Platform** pour la gestion de produits et articles d'un site e-commerce de papier peint.

**Lead Developer:** Manus AI  
**Stack:** Symfony 6.4 + API Platform + Doctrine ORM + PostgreSQL  
**Architecture:** DDD light / Clean Architecture

---

## 🎯 Objectif

Ce CMS est volontairement léger et sert uniquement à :
- Gérer les produits et articles
- Valider des contenus générés par IA
- Exposer une API propre au frontend
- Orchestrer des workflows IA via n8n

⚠️ **Ce CMS ne fait pas** : scraping, génération IA lourde, traitement batch.

---

## ✨ Fonctionnalités Principales

### 🛍️ Gestion de Contenu
- **CRUD Produits & Articles** avec statuts (draft / validated / published)
- **Variantes Produits** : dimensions, prix, SKU, stock
- **Gestion SEO Complète** : meta, slugs, canonical, noindex/nofollow
- **Images Produits** : métadonnées qualité (DPI, dimensions)
- **Catégories & Tags** : organisation du contenu

### 🔄 Workflow & Publication
- **Workflow de Publication** : draft → ready_for_review → validated → published → archived
- **Versioning de Contenu** : historique des modifications avec rollback
- **Soft Delete & Archivage** : aucune perte de données
- **Mode Preview** : tokens temporaires pour visualiser les brouillons

### 🔗 Intégrations
- **Import WooCommerce** : migration one-shot depuis WooCommerce
- **Export Typesense** : synchronisation read-only pour la recherche
- **Webhooks Sortants** : notifications vers systèmes externes (n8n, cache)
- **Traçabilité IA** : source, prompt, validateur

### 🌐 SEO & Redirections
- **Redirections Automatiques** : 301/302 lors des changements de slug
- **Gestion des Slugs** : registre global pour éviter les collisions
- **Support Multi-Langue** : architecture prête (entité Translation)

### 🎨 Interface Admin
- **EasyAdmin** : interface d'administration simple et fonctionnelle
- **Gestion des Variantes** : édition inline depuis la page produit
- **Gestion SEO** : édition directe des métadonnées

---

## 📦 Architecture

### Entités Principales (6)
- `Product` - Produits avec variantes et SEO
- `ProductImage` - Images avec métadonnées qualité
- `Article` - Articles de blog
- `Category` - Catégories
- `Tag` - Tags
- `AiGeneration` - Historique des générations IA

### Entités Structurelles (9)
- `ProductVariant` - Variantes de produits
- `ProductSeo` / `ArticleSeo` / `CategorySeo` - Métadonnées SEO
- `ContentVersion` - Versioning
- `Redirect` - Redirections SEO
- `SlugRegistry` - Registre des slugs
- `PreviewToken` - Tokens de preview
- `Translation` - Traductions multi-langue
- `WebhookEvent` - Événements sortants
- `WooImportLog` - Logs d'import WooCommerce

### Services Métier (15)
- `ProductService` / `ArticleService` - Logique métier CRUD
- `PublicationWorkflowService` - Gestion des transitions d'état
- `VersioningService` - Versioning et rollback
- `SlugService` - Gestion des slugs et redirections
- `PreviewService` - Génération de tokens de preview
- `TranslationService` - Gestion des traductions
- `WebhookDispatcher` - Dispatch des webhooks
- `WooProductImporter` - Import depuis WooCommerce
- `TypesenseExporter` - Export vers Typesense
- `SeoService` / `VariantService` / `ValidationService` / `AiGenerationService`

---

## 🚀 Installation

### Prérequis
- PHP 8.1+
- Composer
- PostgreSQL 14+
- Symfony CLI (optionnel)

### Étapes

1. **Cloner le repository**
   ```bash
   git clone https://github.com/slimsayari/auramur-cms.git
   cd auramur-cms
   ```

2. **Installer les dépendances**
   ```bash
   composer install
   ```

3. **Configurer la base de données**
   ```bash
   cp .env .env.local
   # Éditer .env.local et configurer DATABASE_URL
   ```

4. **Créer la base de données et appliquer les migrations**
   ```bash
   php bin/console doctrine:database:create
   php bin/console doctrine:migrations:migrate
   ```

5. **Lancer le serveur de développement**
   ```bash
   symfony server:start
   # ou
   php -S localhost:8000 -t public/
   ```

6. **Accéder à l'interface admin**
   ```
   http://localhost:8000/admin
   ```

---

## 📚 Documentation

- **[ARCHITECTURE.md](ARCHITECTURE.md)** - Architecture globale du projet
- **[README_V2.md](README_V2.md)** - Fonctionnalités métier (variantes, SEO, import/export)
- **[README_V3.md](README_V3.md)** - Fonctionnalités structurelles (workflow, versioning, webhooks)
- **[API_GUIDE.md](API_GUIDE.md)** - Guide d'utilisation de l'API avec exemples cURL
- **[N8N_INTEGRATION.md](N8N_INTEGRATION.md)** - Guide d'intégration n8n
- **[EXTENSION_DESIGN.md](EXTENSION_DESIGN.md)** - Design des extensions v2
- **[STRUCTURAL_FEATURES_DESIGN.md](STRUCTURAL_FEATURES_DESIGN.md)** - Design des fonctionnalités structurelles v3

---

## 🔗 Endpoints Clés

### API Publique (lecture seule, contenu PUBLISHED)
- `GET /api/products` - Liste des produits
- `GET /api/products/{id}` - Détail d'un produit
- `GET /api/articles` - Liste des articles
- `GET /api/categories` - Catégories
- `GET /api/tags` - Tags

### API Admin (protégée, ROLE_ADMIN)
- `POST /api/admin/products` - Créer un produit
- `PATCH /api/admin/products/{id}` - Modifier un produit
- `POST /api/admin/products/{id}/workflow/publish` - Publier un produit
- `GET /api/admin/products/{id}/versions` - Historique des versions
- `POST /api/admin/products/{id}/versions/{versionNumber}/rollback` - Rollback

### Webhooks & Intégrations
- `POST /api/webhooks/ai-generations` - Webhook n8n pour générations IA
- `POST /api/admin/import/woocommerce` - Import depuis WooCommerce
- `POST /api/admin/export/typesense` - Export vers Typesense

### Preview & Redirections
- `GET /api/preview/{token}` - Prévisualiser un contenu
- `GET /api/redirects/check?path=/ancienne-url` - Vérifier une redirection

---

## 🛠️ Technologies

- **Symfony 6.4** - Framework PHP
- **API Platform** - API REST hypermedia
- **Doctrine ORM** - Mapping objet-relationnel
- **PostgreSQL** - Base de données
- **EasyAdmin** - Interface d'administration
- **UUID v7** - Identifiants uniques

---

## 📝 Licence

Ce projet est sous licence MIT.

---

## 👨‍💻 Auteur

**Manus AI** - Lead Developer Symfony + API Platform avec 10+ ans d'expérience.

---

## 🤝 Contribution

Les contributions sont les bienvenues ! Merci de créer une Pull Request avec une description claire des modifications.
