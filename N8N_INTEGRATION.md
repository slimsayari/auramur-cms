# Intégration n8n avec Auramur CMS

Ce guide explique comment configurer n8n pour générer du contenu et l'envoyer au CMS Auramur via le webhook.

## 1. Architecture d'Intégration

```
┌──────────────────┐
│  Source de Données│
│  (ex: produits)  │
└────────┬─────────┘
         │
         ▼
┌──────────────────────────────────┐
│  n8n Workflow                    │
│  ┌────────────────────────────┐  │
│  │ 1. Récupérer les données   │  │
│  │ 2. Appeler LLM (OpenAI)    │  │
│  │ 3. Formater le résultat    │  │
│  │ 4. Envoyer au webhook      │  │
│  └────────────────────────────┘  │
└────────────┬─────────────────────┘
             │
             ▼
┌──────────────────────────────────┐
│ Auramur CMS Webhook              │
│ POST /api/webhooks/ai-generations│
└────────────┬─────────────────────┘
             │
             ▼
┌──────────────────────────────────┐
│ AiGenerationService              │
│ - Valide les données             │
│ - Crée AiGeneration (DRAFT)      │
│ - Associe au produit/article     │
└────────────┬─────────────────────┘
             │
             ▼
┌──────────────────────────────────┐
│ Admin Dashboard                  │
│ - Valide ou rejette le contenu   │
│ - Publie le produit/article      │
└──────────────────────────────────┘
```

## 2. Prérequis

- **n8n** : Installé et accessible (cloud ou self-hosted).
- **Auramur CMS** : Déployé avec un endpoint webhook accessible.
- **Token Webhook** : Configuré dans `.env.local` du CMS.
- **Clé API OpenAI** : Pour la génération de contenu (ou autre LLM).

## 3. Configuration du Webhook dans le CMS

### Étape 1 : Définir le token secret

Dans `.env.local` du CMS :
```env
WEBHOOK_SECRET=your-very-secure-webhook-token-12345
```

### Étape 2 : Redémarrer l'application

```bash
symfony server:stop
symfony server:start
```

## 4. Créer un Workflow n8n

### Exemple 1 : Générer une Description de Produit

#### Étape 1 : Créer un nouveau workflow

1. Ouvrez n8n.
2. Cliquez sur "New Workflow".
3. Nommez-le "Generate Product Description".

#### Étape 2 : Ajouter un trigger

Utilisez un trigger manuel ou une planification :
- **Manual Trigger** : Pour tester.
- **Cron** : Pour exécuter régulièrement.
- **Webhook** : Pour être appelé depuis une autre source.

#### Étape 3 : Ajouter un nœud "HTTP Request" pour récupérer les produits

1. Ajouter un nœud "HTTP Request".
2. Configurer :
   - **Method** : GET
   - **URL** : `https://api.auramur.local/api/admin/products?status=draft`
   - **Authentication** : Basic Auth (admin/password)
   - **Headers** : `Content-Type: application/json`

#### Étape 4 : Ajouter un nœud "Loop" pour traiter chaque produit

1. Ajouter un nœud "Loop".
2. Configurer pour itérer sur `$json.hydra:member`.

#### Étape 5 : Ajouter un nœud "OpenAI" pour générer la description

1. Ajouter un nœud "OpenAI".
2. Configurer :
   - **Model** : `gpt-4`
   - **Prompt** :
     ```
     Génère une description marketing courte (150-200 mots) pour ce produit de papier peint :
     Nom : {{$json.name}}
     Catégories : {{$json.categories.join(', ')}}
     Tags : {{$json.tags.join(', ')}}
     
     La description doit être engageante, mettre en avant les qualités du produit et encourager l'achat.
     ```
   - **API Key** : Votre clé OpenAI.

#### Étape 6 : Ajouter un nœud "HTTP Request" pour envoyer au webhook

1. Ajouter un nœud "HTTP Request".
2. Configurer :
   - **Method** : POST
   - **URL** : `https://api.auramur.local/api/webhooks/ai-generations`
   - **Headers** :
     ```
     Content-Type: application/json
     X-Webhook-Token: your-very-secure-webhook-token-12345
     ```
   - **Body** (JSON) :
     ```json
     {
       "productId": "{{ $json.id }}",
       "type": "description",
       "content": "{{ $node['OpenAI'].json.choices[0].message.content }}",
       "metadata": {
         "model": "gpt-4",
         "workflow": "Generate Product Description",
         "generatedAt": "{{ now() }}"
       }
     }
     ```

#### Étape 7 : Tester et Sauvegarder

1. Cliquez sur "Test Workflow".
2. Vérifiez que le webhook reçoit les données.
3. Cliquez sur "Save".

### Exemple 2 : Générer des Tags

La structure est similaire, mais le prompt et le type changent :

**Prompt OpenAI** :
```
Génère 5-7 tags pertinents pour ce produit de papier peint :
Nom : {{$json.name}}
Description : {{$json.description}}

Retourne les tags sous forme de JSON array : ["tag1", "tag2", ...]
```

**Payload du webhook** :
```json
{
  "productId": "{{ $json.id }}",
  "type": "tags",
  "content": "{{ $node['OpenAI'].json.choices[0].message.content }}",
  "metadata": {
    "model": "gpt-4",
    "workflow": "Generate Product Tags"
  }
}
```

## 5. Tester l'Intégration

### Étape 1 : Créer un produit de test

```bash
curl -X POST https://api.auramur.local/api/admin/products \
  -u admin:password \
  -H "Content-Type: application/json" \
  -d '{
    "slug": "test-wallpaper",
    "name": "Test Wallpaper",
    "description": "A test wallpaper",
    "sku": "TEST-001",
    "price": "29.99",
    "categoryIds": [],
    "tagIds": [],
    "images": [
      {
        "url": "https://example.com/image.jpg",
        "format": "jpg",
        "dpi": 300,
        "width": 1920,
        "height": 1080
      }
    ]
  }'
```

### Étape 2 : Exécuter le workflow n8n

1. Allez dans le workflow n8n.
2. Cliquez sur "Execute Workflow".
3. Attendez la completion.

### Étape 3 : Vérifier la génération IA

```bash
curl https://api.auramur.local/api/admin/ai-generations \
  -u admin:password
```

Vous devriez voir une nouvelle génération avec le statut `draft`.

### Étape 4 : Valider la génération

```bash
curl -X PATCH https://api.auramur.local/api/admin/ai-validations/{id}/validate \
  -u admin:password
```

La description devrait maintenant être appliquée au produit.

## 6. Gestion des Erreurs

### Erreur : Token invalide

**Symptôme** : Réponse 401 du webhook.

**Solution** : Vérifiez que le token dans le header `X-Webhook-Token` correspond à `WEBHOOK_SECRET` dans `.env.local`.

### Erreur : Produit non trouvé

**Symptôme** : Réponse 400 "Product not found".

**Solution** : Vérifiez que le `productId` dans le payload correspond à un UUID valide.

### Erreur : Validation échouée

**Symptôme** : Réponse 422 avec détails de validation.

**Solution** : Vérifiez que le payload JSON respecte le schéma attendu (voir API_GUIDE.md).

## 7. Bonnes Pratiques

1. **Limiter la fréquence** : Évitez de générer du contenu trop souvent pour les mêmes produits.
2. **Monitorer les erreurs** : Configurez des alertes n8n en cas d'échec du webhook.
3. **Valider avant publication** : Ne publiez jamais sans vérifier manuellement le contenu généré.
4. **Versionner les prompts** : Gardez l'historique des prompts utilisés pour chaque type de génération.
5. **Tester en staging** : Testez les workflows sur un environnement de staging avant la production.

## 8. Évolution Future

- **Scoring de qualité** : Ajouter un score de confiance à chaque génération.
- **Feedback loop** : Permettre aux administrateurs de noter les générations pour améliorer le modèle.
- **Batch processing** : Générer du contenu pour plusieurs produits en parallèle.
- **Intégration avec d'autres LLMs** : Supporter Claude, Mistral, etc.
