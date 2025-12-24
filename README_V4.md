## Auramur CMS v4 - Import WordPress & IA Découplée

Cette version étend le CMS Auramur avec deux fonctionnalités majeures :

1.  **Import d'articles WordPress** (one-shot, CLI)
2.  **Système de génération d'images IA découplé** (interchangeable)

---

### 1. Import d'Articles WordPress

Une nouvelle commande CLI permet d'importer des articles depuis WordPress via JSON ou l'API REST.

**Fonctionnalités** :
- Import des articles, catégories, tags, images à la une, et SEO (Yoast)
- Création automatique des catégories et tags manquants
- Téléchargement des images à la une
- Statut des articles importés = DRAFT
- Logs d'import détaillés (`WordpressImportLog`)

**Commande CLI** :
```bash
# Import depuis un fichier JSON
php bin/console app:import:wordpress-articles docs/wordpress-import-example.json

# Import depuis l'API REST WordPress
php bin/console app:import:wordpress-articles https://example.com --type=rest_api --limit=50
```

**Fichiers Clés** :
- `src/Command/ImportWordpressArticlesCommand.php`
- `src/Service/WordpressArticleImporter.php`
- `src/DTO/WordpressArticleImportDTO.php`
- `src/Entity/WordpressImportLog.php`
- `docs/wordpress-import-example.json`

---

### 2. Système de Génération d'Images IA Découplé

L'architecture a été refactorisée pour permettre de changer de provider de génération d'images (NanoBanana, Midjourney, etc.) sans modifier le code du CMS.

**Architecture** :
- **Interface** : `AiImageGeneratorInterface` définit le contrat pour tous les providers.
- **Adapter** : `NanoBananaImageGenerator` est la première implémentation de cette interface.
- **Factory** : `AiImageGeneratorFactory` crée dynamiquement le bon adapter en fonction de la configuration active.
- **Configuration** : `AiProviderConfig` stocke les clés API (chiffrées) et les paramètres des providers.

**Fonctionnalités** :
- **Interchangeabilité** : Ajoutez un nouveau provider en créant un nouvel adapter.
- **Sécurité** : Les clés API sont chiffrées en base de données avec une clé AES-256-CBC.
- **Gestion Admin** : Une page "Configurations IA" dans EasyAdmin permet de gérer les providers, leurs clés API, et d'activer celui à utiliser.

**Fichiers Clés** :
- `src/Service/Ai/AiImageGeneratorInterface.php`
- `src/Service/Ai/NanoBananaImageGenerator.php`
- `src/Service/Ai/AiImageGeneratorFactory.php`
- `src/Entity/AiProviderConfig.php`
- `src/Controller/Admin/AiProviderConfigCrudController.php`

---

### 3. Configuration

**Variables d'environnement** :

Ajoutez la clé suivante à votre fichier `.env` pour le chiffrement des clés API :

```
APP_ENCRYPTION_KEY=change-this-to-a-random-32-char-key-in-production
```

**Configuration des services** :

Le fichier `config/services.yaml` a été mis à jour pour injecter la clé de chiffrement et le répertoire d'upload.

---

### 4. Tests

Les tests pour ces nouvelles fonctionnalités sont documentés dans `TESTS_TODO.md` et seront implémentés dans une prochaine itération.
