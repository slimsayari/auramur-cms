# 🚀 Guide de Déploiement - Auramur CMS

## 📋 Prérequis

- PHP 8.1+
- Composer 2.x
- PostgreSQL 14+ (ou SQLite pour dev)
- Node.js 18+ (optionnel, pour assets)

## 🔧 Installation

### 1. Cloner le repository

```bash
git clone https://github.com/slimsayari/auramur-cms.git
cd auramur-cms
```

### 2. Installer les dépendances

```bash
composer install
```

### 3. Configurer l'environnement

Copier `.env` vers `.env.local` et configurer :

```bash
cp .env .env.local
```

Éditer `.env.local` :

```env
# Database (PostgreSQL en production)
DATABASE_URL="postgresql://user:password@localhost:5432/auramur_cms?serverVersion=14&charset=utf8"

# Typesense
TYPESENSE_HOST=http://typesense:8108
TYPESENSE_API_KEY=your-api-key-here

# n8n Webhook (optionnel)
N8N_WEBHOOK_URL=https://your-n8n-instance.com/webhook/auramur
```

### 4. Créer la base de données

```bash
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate --no-interaction
```

### 5. Créer un utilisateur admin

```bash
php bin/console app:create-admin admin@auramur.com SecurePassword123 "Admin Auramur"
```

### 6. Démarrer le serveur (dev)

```bash
symfony server:start
# ou
php -S localhost:8000 -t public/
```

## 🎯 Accès à l'interface admin

- **URL** : `http://localhost:8000/admin`
- **Credentials** : `admin@auramur.com` / `SecurePassword123`

## 📦 Commandes Disponibles

### Gestion des utilisateurs

```bash
# Créer un admin
php bin/console app:create-admin email@example.com password "Nom"
```

### Import WooCommerce

```bash
# Import JSON
php bin/console app:import:woocommerce docs/woo-import-example.json

# Import CSV
php bin/console app:import:woocommerce docs/woo-import-example.csv --format=csv

# Mode dry-run (simulation)
php bin/console app:import:woocommerce file.json --dry-run
```

### Export Typesense

```bash
# Export réel
php bin/console app:export:typesense

# Mode dry-run (affiche le JSON sans envoyer)
php bin/console app:export:typesense --dry-run

# Force export même si Typesense n'est pas configuré
php bin/console app:export:typesense --force
```

## 🔄 Workflow de Publication

### Transitions d'état

```
DRAFT → READY_FOR_REVIEW → VALIDATED → PUBLISHED
                ↓                ↓          ↓
            ARCHIVED ←──────────────────────┘
```

### Règles de publication

Un produit **ne peut pas être publié** si :
- ❌ Aucune variante active (stock > 0)
- ❌ Aucune image
- ❌ Aucune configuration SEO

### API Workflow

```bash
# Soumettre pour révision
POST /api/admin/products/{id}/workflow/submit

# Approuver
POST /api/admin/products/{id}/workflow/approve

# Publier
POST /api/admin/products/{id}/workflow/publish

# Archiver
POST /api/admin/products/{id}/workflow/archive
```

## 🔍 SEO Automatique

### Redirections automatiques

Quand un slug change, une redirection 301 est créée automatiquement :

```
Ancien slug: /papier-peint-tropical
Nouveau slug: /papier-peint-tropical-paradise
→ Redirection 301 créée automatiquement
```

### Registre global des slugs

Tous les slugs sont enregistrés dans `slug_registry` pour garantir l'unicité globale.

## 🎨 Gestion des Variantes

### Créer des variantes

Via l'API :

```bash
POST /api/admin/products/{id}/variants
Content-Type: application/json

{
  "sku": "PP-TROP-001-M",
  "name": "Medium (100x300cm)",
  "dimensions": "100x300",
  "pricePerM2": 5.49,
  "stock": 15
}
```

Via l'admin : `/admin/product-variant`

## 📊 Export Typesense

### Payload exporté

```json
{
  "id": "uuid",
  "name": "Papier Peint Tropical Paradise",
  "description": "...",
  "price": 89.99,
  "slug": "papier-peint-tropical-paradise",
  "status": "published",
  "publishedAt": "2024-12-23T10:00:00+00:00",
  "variants": [
    {
      "id": "uuid",
      "sku": "PP-TROP-001-M",
      "name": "Medium (100x300cm)",
      "dimensions": "100x300",
      "pricePerM2": 5.49,
      "stock": 15
    }
  ],
  "categories": [...],
  "tags": [...],
  "images": [...],
  "seoTitle": "...",
  "metaDescription": "..."
}
```

### Export automatique

L'export vers Typesense est **automatique** quand un produit passe en statut `PUBLISHED`.

## 🧪 Tests

### Tester l'interface admin

1. Accéder à `/admin`
2. Se connecter avec les credentials admin
3. Créer un produit
4. Ajouter des variantes
5. Configurer le SEO
6. Publier

### Tester l'import WooCommerce

```bash
php bin/console app:import:woocommerce docs/woo-import-example.json
```

Vérifier dans `/admin/product` que les produits sont importés.

### Tester l'export Typesense

```bash
php bin/console app:export:typesense --dry-run
```

Vérifier le payload JSON affiché.

## 🐛 Dépannage

### Erreur : "Class ValidatorInterface not found"

```bash
composer require symfony/validator
```

### Erreur : "Table not found"

```bash
php bin/console doctrine:schema:update --force
```

### Cache corrompu

```bash
php bin/console cache:clear
```

## 📚 Documentation Complémentaire

- [README.md](README.md) - Vue d'ensemble
- [API_GUIDE.md](API_GUIDE.md) - Documentation API
- [N8N_INTEGRATION.md](N8N_INTEGRATION.md) - Intégration n8n
- [AUDIT_REPORT.md](AUDIT_REPORT.md) - Rapport d'audit

## 🔐 Sécurité

### En production

- ✅ Changer `APP_SECRET` dans `.env.local`
- ✅ Utiliser PostgreSQL (pas SQLite)
- ✅ Activer HTTPS
- ✅ Configurer un firewall
- ✅ Limiter l'accès à `/admin` par IP si possible

### Authentification

- HTTP Basic Auth disponible en dev
- Form Login pour l'interface admin
- JWT recommandé pour l'API (à configurer)

## 📞 Support

Pour toute question : https://github.com/slimsayari/auramur-cms/issues
