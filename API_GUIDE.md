# Guide d'Utilisation de l'API Auramur CMS

Ce guide détaille comment consommer l'API Auramur CMS depuis une application frontend.

## 1. Configuration de Base

### URL de Base
```
https://api.auramur.local/api
```

### Headers Requis
```
Content-Type: application/json
Accept: application/json
```

## 2. Authentification

### Pour les endpoints publics
Aucune authentification requise. Les endpoints publics retournent uniquement les contenus avec le statut `PUBLISHED`.

### Pour les endpoints d'administration
Utilisez l'authentification HTTP Basic :
```
Authorization: Basic base64(username:password)
```

Exemple avec cURL :
```bash
curl -u admin:password https://api.auramur.local/api/admin/products
```

## 3. Exemples de Requêtes

### Récupérer les produits publiés

**Requête :**
```bash
GET /api/products?page=1&itemsPerPage=20
```

**Réponse (200 OK) :**
```json
{
  "@context": "/api/contexts/Product",
  "@id": "/api/products",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/api/products/550e8400-e29b-41d4-a716-446655440000",
      "@type": "Product",
      "id": "550e8400-e29b-41d4-a716-446655440000",
      "slug": "papier-peint-geometrique",
      "name": "Papier Peint Géométrique",
      "description": "Un magnifique papier peint avec des motifs géométriques modernes.",
      "sku": "PPG-001",
      "price": "29.99",
      "status": "published",
      "createdAt": "2025-12-23T10:00:00+00:00",
      "updatedAt": "2025-12-23T10:00:00+00:00",
      "publishedAt": "2025-12-23T10:00:00+00:00",
      "images": [
        {
          "id": "550e8400-e29b-41d4-a716-446655440001",
          "url": "https://cdn.auramur.local/images/ppg-001-1.jpg",
          "format": "jpg",
          "dpi": 300,
          "width": 1920,
          "height": 1080,
          "position": 0,
          "isThumbnail": true,
          "altText": "Vue du papier peint géométrique"
        }
      ],
      "categories": ["geometrique", "moderne"],
      "tags": ["motifs", "design", "interieur"]
    }
  ],
  "hydra:totalItems": 42,
  "hydra:view": {
    "@id": "/api/products?page=1&itemsPerPage=20",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/api/products?page=1&itemsPerPage=20",
    "hydra:next": "/api/products?page=2&itemsPerPage=20",
    "hydra:last": "/api/products?page=3&itemsPerPage=20"
  }
}
```

### Filtrer les produits par catégorie

**Requête :**
```bash
GET /api/products?categories[]=geometrique&status=published
```

### Récupérer un produit spécifique

**Requête :**
```bash
GET /api/products/550e8400-e29b-41d4-a716-446655440000
```

**Réponse (200 OK) :**
```json
{
  "@id": "/api/products/550e8400-e29b-41d4-a716-446655440000",
  "@type": "Product",
  "id": "550e8400-e29b-41d4-a716-446655440000",
  "slug": "papier-peint-geometrique",
  "name": "Papier Peint Géométrique",
  "description": "Un magnifique papier peint avec des motifs géométriques modernes.",
  "sku": "PPG-001",
  "price": "29.99",
  "status": "published",
  "createdAt": "2025-12-23T10:00:00+00:00",
  "updatedAt": "2025-12-23T10:00:00+00:00",
  "publishedAt": "2025-12-23T10:00:00+00:00",
  "images": [
    {
      "id": "550e8400-e29b-41d4-a716-446655440001",
      "url": "https://cdn.auramur.local/images/ppg-001-1.jpg",
      "format": "jpg",
      "dpi": 300,
      "width": 1920,
      "height": 1080,
      "position": 0,
      "isThumbnail": true,
      "altText": "Vue du papier peint géométrique"
    }
  ],
  "categories": ["geometrique", "moderne"],
  "tags": ["motifs", "design", "interieur"]
}
```

### Créer un nouveau produit (Admin)

**Requête :**
```bash
POST /api/admin/products
Authorization: Basic YWRtaW46cGFzc3dvcmQ=
Content-Type: application/json

{
  "slug": "nouveau-papier-peint",
  "name": "Nouveau Papier Peint",
  "description": "Description du nouveau papier peint",
  "sku": "NPP-001",
  "price": "39.99",
  "categoryIds": ["550e8400-e29b-41d4-a716-446655440010"],
  "tagIds": ["550e8400-e29b-41d4-a716-446655440020"],
  "images": [
    {
      "url": "https://cdn.auramur.local/images/npp-001-1.jpg",
      "format": "jpg",
      "dpi": 300,
      "width": 1920,
      "height": 1080,
      "altText": "Vue du nouveau papier peint"
    }
  ]
}
```

**Réponse (201 Created) :**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440100",
  "name": "Nouveau Papier Peint",
  "status": "draft"
}
```

### Mettre à jour un produit (Admin)

**Requête :**
```bash
PATCH /api/admin/products/550e8400-e29b-41d4-a716-446655440100
Authorization: Basic YWRtaW46cGFzc3dvcmQ=
Content-Type: application/json

{
  "name": "Nouveau Papier Peint - Édition Révisée",
  "description": "Description mise à jour"
}
```

### Publier un produit (Admin)

**Requête :**
```bash
PATCH /api/admin/products/550e8400-e29b-41d4-a716-446655440100/publish
Authorization: Basic YWRtaW46cGFzc3dvcmQ=
```

**Réponse (200 OK) :**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440100",
  "status": "published",
  "publishedAt": "2025-12-23T14:30:00+00:00"
}
```

## 4. Gestion des Erreurs

### Erreur de validation (422)

```json
{
  "error": "Validation failed",
  "details": "The property slug is required."
}
```

### Erreur d'authentification (401)

```json
{
  "error": "Unauthorized"
}
```

### Erreur d'accès (403)

```json
{
  "error": "Access Denied"
}
```

### Ressource non trouvée (404)

```json
{
  "@context": "/api/contexts/Error",
  "@type": "hydra:Error",
  "hydra:title": "An error occurred",
  "hydra:description": "Not Found"
}
```

## 5. Pagination

L'API utilise la pagination par défaut avec 20 éléments par page.

**Paramètres :**
- `page` : Numéro de la page (par défaut : 1)
- `itemsPerPage` : Nombre d'éléments par page (par défaut : 20, max : 100)

**Exemple :**
```bash
GET /api/products?page=2&itemsPerPage=50
```

## 6. Filtrage et Tri

Les endpoints de collection supportent le filtrage et le tri via les paramètres de requête.

**Filtrage par statut :**
```bash
GET /api/products?status=published
```

**Tri par date de création (décroissant) :**
```bash
GET /api/products?order[createdAt]=desc
```

## 7. Webhook n8n

Pour envoyer du contenu généré par IA via n8n, utilisez le webhook :

**Requête :**
```bash
POST /api/webhooks/ai-generations
X-Webhook-Token: your-secret-token
Content-Type: application/json

{
  "productId": "550e8400-e29b-41d4-a716-446655440000",
  "type": "description",
  "content": "Une description générée par IA...",
  "metadata": {
    "model": "gpt-4",
    "confidence": 0.95
  }
}
```

**Réponse (202 Accepted) :**
```json
{
  "id": "550e8400-e29b-41d4-a716-446655440200",
  "status": "draft",
  "type": "description",
  "message": "AI generation received and queued for validation"
}
```

## 8. Statuts de Contenu

- `draft` : Brouillon, non visible au public.
- `validated` : Validé par un administrateur.
- `published` : Publié et visible au public.
- `archived` : Archivé (généralement pour les générations IA rejetées).

## 9. Types de Génération IA

- `description` : Description du produit ou résumé de l'article.
- `title` : Titre du produit ou de l'article.
- `tags` : Tags générés automatiquement.
- `seo_meta` : Métadonnées SEO (JSON).
- `article_content` : Contenu complet d'un article.

## 10. Ressources Supplémentaires

- **Documentation OpenAPI** : Disponible à `/api/docs`
- **Documentation ReDoc** : Disponible à `/api/docs.html`
- **Schéma JSON-LD** : Disponible à `/api/contexts/Product`, etc.
