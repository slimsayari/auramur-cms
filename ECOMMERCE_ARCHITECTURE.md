# Architecture E-Commerce Léger - Auramur CMS

## Principes Architecturaux

Le module e-commerce du CMS Auramur suit une architecture **headless stricte** où le CMS ne gère **que les données** et **pas les paiements**.

### Responsabilités

| Composant | Responsabilités |
|-----------|----------------|
| **Frontend (Lovable)** | Panier, UI, Appels Stripe/PayPal, Expérience utilisateur |
| **CMS (Auramur)** | Comptes clients, Commandes, Webhooks, Emails, Admin |
| **Stripe/PayPal** | Paiements, Sécurité, Compliance PCI |

### Flow de Paiement

```
[Frontend] → Panier → Stripe/PayPal → Paiement
                                         ↓
                                      Webhook
                                         ↓
[CMS] ← Création commande ← Vérification signature
```

---

## Entités

### 1. Customer (Client)

Représente un compte client du site e-commerce.

**Champs** :
- `id` (UUID) - Identifiant unique
- `email` (string, unique) - Email du client
- `password` (string, hashé) - Mot de passe
- `firstName` (string) - Prénom
- `lastName` (string) - Nom
- `createdAt` (datetime) - Date de création
- `lastLoginAt` (datetime, nullable) - Dernière connexion
- `isActive` (boolean) - Compte actif ou désactivé
- `deletedAt` (datetime, nullable) - Soft delete

**Relations** :
- `orders` (OneToMany) - Commandes du client

**Sécurité** :
- Auth séparée de l'admin (JWT)
- Un client ne peut voir que SES commandes
- Voters pour contrôler l'accès

---

### 2. Order (Commande)

Représente une commande validée après paiement.

**Champs** :
- `id` (UUID) - Identifiant unique
- `reference` (string, unique) - Référence commande (ex: ORD-2024-001)
- `status` (enum) - Statut : PAID, PROCESSING, COMPLETED, CANCELLED
- `totalAmount` (decimal) - Montant total
- `currency` (string) - Devise (EUR, USD)
- `paymentProvider` (enum) - STRIPE ou PAYPAL
- `paymentReference` (string) - payment_intent_id ou transaction_id
- `createdAt` (datetime) - Date de création
- `updatedAt` (datetime) - Date de mise à jour
- `customer` (ManyToOne) - Client propriétaire

**Relations** :
- `items` (OneToMany) - Articles de la commande
- `customer` (ManyToOne) - Client

**Règles** :
- Une commande est créée **uniquement** après validation du paiement via webhook
- Le statut initial est toujours PAID
- La référence est générée automatiquement

---

### 3. OrderItem (Article de commande)

Représente un article dans une commande (snapshot).

**Champs** :
- `id` (UUID) - Identifiant unique
- `order` (ManyToOne) - Commande parente
- `productId` (UUID) - Référence au produit CMS
- `productName` (string) - Nom du produit (snapshot)
- `variantLabel` (string, nullable) - Label de la variante (ex: "1024x768")
- `quantity` (integer) - Quantité
- `unitPrice` (decimal) - Prix unitaire
- `totalPrice` (decimal) - Prix total (quantity * unitPrice)

**Règles** :
- Les données sont des **snapshots** : le produit peut évoluer après la commande
- Le `productId` est conservé pour référence mais n'est pas une relation Doctrine

---

## API Endpoints

### Clients

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/api/customers/register` | Inscription client | Public |
| POST | `/api/customers/login` | Connexion (retourne JWT) | Public |
| GET | `/api/customers/me` | Profil client | JWT |
| PATCH | `/api/customers/me` | Modification profil | JWT |
| DELETE | `/api/customers/me` | Suppression compte (soft) | JWT |

### Commandes

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| GET | `/api/orders` | Liste commandes client | JWT |
| GET | `/api/orders/{id}` | Détail commande | JWT + Voter |
| GET | `/api/admin/orders` | Liste toutes commandes | Admin |
| GET | `/api/admin/orders/{id}` | Détail commande admin | Admin |
| PATCH | `/api/admin/orders/{id}/status` | Changer statut | Admin |

### Webhooks

| Méthode | Endpoint | Description | Auth |
|---------|----------|-------------|------|
| POST | `/api/webhooks/stripe` | Webhook Stripe | Signature |
| POST | `/api/webhooks/paypal` | Webhook PayPal | Signature |

---

## Sécurité

### Authentification Client (JWT)

- **Bundle** : `lexik/jwt-authentication-bundle`
- **Durée** : 1 heure
- **Refresh** : Token refresh disponible
- **Stockage** : Frontend (localStorage ou cookie httpOnly)

### Voters

- **OrderVoter** : Vérifie que le client est propriétaire de la commande
- **CustomerVoter** : Vérifie que le client modifie son propre profil

### Webhooks

- **Stripe** : Vérification de la signature avec `stripe-signature` header
- **PayPal** : Vérification de la signature avec clé API PayPal
- **Logs** : Tous les webhooks sont loggés (succès et échecs)

---

## Services Métier

### OrderService

Responsabilités :
- Créer une commande depuis un webhook
- Calculer le montant total
- Générer la référence unique
- Envoyer les emails transactionnels

### CustomerAuthService

Responsabilités :
- Inscription client
- Connexion client
- Validation email unique
- Hash du mot de passe

### WebhookPaymentHandler

Responsabilités :
- Vérifier la signature du webhook
- Parser les données Stripe/PayPal
- Créer la commande via OrderService
- Logger les événements

---

## Emails Transactionnels

### Confirmation de commande (client)

**Sujet** : Confirmation de votre commande #{reference}

**Contenu** :
- Référence commande
- Montant total
- Liste des articles
- Lien vers le suivi

### Notification admin

**Sujet** : Nouvelle commande #{reference}

**Contenu** :
- Référence commande
- Client
- Montant total
- Lien vers l'admin

---

## Admin EasyAdmin

### Orders

- Liste des commandes (tri par date)
- Détail commande (articles, client, montant)
- Changement de statut (PROCESSING, COMPLETED, CANCELLED)
- Recherche par référence, client, montant

### Customers

- Liste des clients
- Désactivation compte
- Consultation des commandes du client
- Recherche par email, nom

**Règle** : Aucune création manuelle de commande depuis l'admin.

---

## Tests

### Tests Unitaires

- `OrderServiceTest` : Création commande, calcul montant, génération référence
- `CustomerAuthServiceTest` : Inscription, connexion, validation
- `WebhookPaymentHandlerTest` : Vérification signature, parsing données

### Tests Fonctionnels

- `CustomerRegistrationTest` : Inscription client
- `CustomerLoginTest` : Connexion client et récupération JWT
- `OrderCreationTest` : Webhook paiement valide → création commande
- `OrderAccessTest` : Client accède à SES commandes uniquement
- `OrderAccessDeniedTest` : Accès interdit aux commandes des autres clients

---

## Intégration Frontend (Lovable)

### Flow d'achat

1. **Frontend** : L'utilisateur ajoute des produits au panier (côté client)
2. **Frontend** : L'utilisateur clique sur "Payer"
3. **Frontend** : Appel à Stripe/PayPal avec les données du panier
4. **Stripe/PayPal** : Traitement du paiement
5. **Stripe/PayPal** : Envoi d'un webhook au CMS
6. **CMS** : Vérification de la signature
7. **CMS** : Création de la commande
8. **CMS** : Envoi des emails
9. **Frontend** : Redirection vers la page de confirmation

### Données à envoyer à Stripe/PayPal

```json
{
  "amount": 12990,
  "currency": "eur",
  "metadata": {
    "customer_email": "client@example.com",
    "customer_id": "uuid-du-client",
    "items": [
      {
        "product_id": "uuid-produit",
        "product_name": "Papier peint floral",
        "variant_label": "1024x768",
        "quantity": 2,
        "unit_price": 4995
      }
    ]
  }
}
```

### Récupération du JWT

```javascript
// Inscription
const response = await fetch('/api/customers/register', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'client@example.com',
    password: 'password123',
    firstName: 'John',
    lastName: 'Doe'
  })
});

// Connexion
const loginResponse = await fetch('/api/customers/login', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    email: 'client@example.com',
    password: 'password123'
  })
});

const { token } = await loginResponse.json();

// Utilisation du token
const ordersResponse = await fetch('/api/orders', {
  headers: {
    'Authorization': `Bearer ${token}`
  }
});
```

---

## Évolutions Futures

- Ajout d'adresses de livraison (optionnel)
- Historique des statuts de commande
- Notifications par email lors du changement de statut
- Export des commandes en CSV
- Statistiques e-commerce dans le dashboard admin
