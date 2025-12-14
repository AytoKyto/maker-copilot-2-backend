# 🎮 Contrôleurs - Maker Copilot

## 📋 Vue d'Ensemble

Les contrôleurs de Maker Copilot gèrent la **logique de présentation** et orchestrent les interactions entre l'API et les services métier. Ils sont organisés par **domaine fonctionnel**.

## 🏗️ Architecture des Contrôleurs

```mermaid
graph TD
    Request[🌐 HTTP Request] --> Router[🚀 Symfony Router]
    Router --> Controller[🎮 Controller]
    Controller --> Service[🔧 Service Layer]
    Controller --> Validation[✅ Validation]
    Service --> Repository[📚 Repository]
    Repository --> Database[(🗄️ Database)]
    
    Controller --> Response[📤 HTTP Response]
    
    subgraph "🎮 Controller Types"
        API[🌐 API Controllers]
        Auth[🔐 Auth Controllers]
        Admin[👑 Admin Controllers]
        Utils[🛠️ Utility Controllers]
    end
```

## 👤 Contrôleurs d'Authentification

### 🔐 `RegistrationController` - Inscription

**Fichier :** `src/Controller/RegistrationController.php`

```php
#[Route('/register', name: 'register', methods: 'POST')]
public function register(Request $request, MailerInterface $mailer): JsonResponse
```

#### ✨ **Fonctionnalités**

```mermaid
graph LR
    Registration[📝 Inscription] --> Validation[✅ Validation]
    Validation --> PasswordHash[🔐 Hash Password]
    PasswordHash --> CreateUser[👤 Create User]
    CreateUser --> GenerateJWT[🔑 Generate JWT]
    GenerateJWT --> SendEmail[📧 Send Welcome Email]
    SendEmail --> Response[📤 Response]
```

#### 🛡️ **Validations Implémentées**

| Validation | Description | Règle |
|------------|-------------|-------|
| 📧 **Email** | Format valide et unique | `Assert\Email`, vérification unicité |
| 🔐 **Password** | Force du mot de passe | 8 chars min, lettres + chiffres |
| 🔍 **Data** | Présence des champs | `email` et `password` requis |

#### 📧 **Gestion Email**

```php
try {
    $htmlContent = $this->renderView('email/welcome.html.twig', [
        'email' => $user->getEmail()
    ]);
    $mailer->send($email);
    $this->logger->info('Email de bienvenue envoyé');
} catch (\Exception $e) {
    // Log erreur mais ne bloque pas la création
    $this->logger->error('Erreur email: ' . $e->getMessage());
}
```

### 🔑 `ForgotPasswordController` - Mot de Passe Oublié

**Workflow :**

```mermaid
sequenceDiagram
    participant User
    participant Controller
    participant EmailService
    participant Database
    
    User->>Controller: POST /api/forgot-password
    Controller->>Database: Find user by email
    Database-->>Controller: User found
    Controller->>Database: Generate reset token
    Controller->>EmailService: Send reset email
    EmailService-->>User: Email with reset link
    Controller-->>User: 200 Success
```

### 🔄 `ResetPasswordController` - Réinitialisation

**Features :**
- 🔐 Validation du token de reset
- ⏰ Expiration des tokens (24h)
- 🔒 Hash sécurisé du nouveau mot de passe
- 🗑️ Suppression automatique du token

## 📊 Contrôleurs de Rapports

### 📈 `RapportController` - Génération de Rapports

**Fichier :** `src/Controller/RapportController.php`

```php
#[Route('/api/rapports/{type}', name: 'generate_rapport')]
public function generateRapport(string $type, Request $request): JsonResponse
```

#### 🎯 **Types de Rapports Supportés**

```mermaid
graph TD
    RapportController[📊 RapportController] --> RapportManager[🔧 RapportManager]
    
    RapportManager --> SalesAnalysis[📈 Sales Analysis]
    RapportManager --> ProductPerf[📦 Product Performance]
    RapportManager --> ChannelAnalysis[📺 Channel Analysis]
    RapportManager --> Profitability[💰 Profitability]
    RapportManager --> CustomInsights[🔍 Custom Insights]
    RapportManager --> EmailReport[📧 Email Report]
    
    subgraph "📋 Formats Export"
        Excel[📊 Excel]
        PDF[📄 PDF]
        CSV[📝 CSV]
        JSON[🔗 JSON]
    end
    
    SalesAnalysis --> Excel
    ProductPerf --> PDF
    ChannelAnalysis --> CSV
```

#### 🔧 **Implémentation Strategy Pattern**

```php
public function generateRapport(string $type, Request $request): JsonResponse
{
    try {
        $strategy = $this->rapportManager->getStrategy($type);
        $data = $request->query->all();
        
        $result = $strategy->execute($data);
        
        return new JsonResponse($result, Response::HTTP_OK);
    } catch (\DomainException $e) {
        return new JsonResponse([
            'error' => 'Type de rapport non supporté'
        ], Response::HTTP_BAD_REQUEST);
    }
}
```

### 📊 `RapportDataController` - Données pour Dashboard

**API Resource personnalisée :**

```php
#[ApiResource(
    operations: [
        new Get(controller: RapportDataController::class)
    ]
)]
class RapportData
```

## 📧 Contrôleurs de Communication

### ✉️ `EmailTestController` - Tests d'Email

**Fonctionnalités :**
- 🧪 Test de configuration SMTP
- 📧 Envoi d'emails de test
- 📊 Diagnostic de configuration
- 🔍 Validation des templates

```php
#[Route('/api/test-email', name: 'test_email', methods: ['POST'])]
public function testEmail(Request $request, MailerInterface $mailer): JsonResponse

#[Route('/api/email-config', name: 'email_config', methods: ['GET'])]
public function getEmailConfig(): JsonResponse
```

### 📞 `ContactHomeController` - Contact Général

**Gestion des demandes de contact :**

```mermaid
graph LR
    Contact[📞 Contact Form] --> Validation[✅ Validation]
    Validation --> EmailAdmin[📧 Email Admin]
    Validation --> EmailUser[📧 Email User Confirmation]
    EmailAdmin --> Response[📤 Response]
```

### 🧪 `ContactTesteurController` - Contact Testeurs

**Spécifique aux demandes de programme testeur :**
- 🎯 Formulaire spécialisé
- 📊 Métriques dédiées
- 🔄 Workflow d'approbation

## 🖼️ Contrôleurs de Gestion de Fichiers

### 📸 `ProductImageController` - Images Produits

**Fichier :** `src/Controller/ProductImageController.php`

```php
#[Route('/api/products/{id}/image', methods: ['POST'])]
public function uploadImage(Product $product, Request $request): JsonResponse
```

#### 🔄 **Workflow Upload**

```mermaid
sequenceDiagram
    participant Client
    participant Controller
    participant VichUploader
    participant FileSystem
    participant Database
    
    Client->>Controller: POST /api/products/123/image
    Controller->>Controller: Validate file
    Controller->>VichUploader: Process upload
    VichUploader->>FileSystem: Store file
    VichUploader->>Database: Update product.imagePath
    Database-->>Controller: Success
    Controller-->>Client: 200 + image URL
```

#### 🛡️ **Validations d'Upload**

| Validation | Règle | Erreur |
|------------|-------|--------|
| 📏 **Taille** | Max 5MB | `413 Payload Too Large` |
| 🖼️ **Format** | JPG, PNG, WEBP | `422 Unprocessable Entity` |
| 📐 **Dimensions** | Max 2048x2048 | `422 Unprocessable Entity` |
| 🔒 **Sécurité** | Scan antivirus | `422 Unprocessable Entity` |

## 📮 Contrôleurs de Monitoring

### 📊 `MessengerMonitorController` - Monitoring des Queues

**Surveillance des tâches asynchrones :**

```php
#[Route('/admin/messenger/monitor')]
public function monitor(): Response
```

**Métriques suivies :**
- 📊 Nombre de messages en attente
- ⚡ Temps de traitement moyen
- ❌ Taux d'erreur
- 🔄 Tentatives de retry

### 💬 `FeedBackController` - Retours Utilisateurs

**Collecte des feedbacks :**

```mermaid
graph TD
    Feedback[💬 User Feedback] --> Validation[✅ Validation]
    Validation --> Storage[💾 Storage]
    Validation --> EmailNotif[📧 Admin Notification]
    Storage --> Analytics[📊 Analytics]
    Analytics --> Improvements[🚀 Product Improvements]
```

## 🔒 Middleware et Sécurité

### 🛡️ **JWT Authentication**

```php
// Gestion automatique par LexikJWTAuthenticationBundle
// Configuration dans security.yaml
```

### 👤 **User Context Injection**

```php
class CurrentUserExtension implements QueryCollectionExtensionInterface
{
    public function applyToCollection(QueryBuilder $qb, ...)
    {
        // Filtre automatique par utilisateur connecté
        $qb->andWhere('o.user = :current_user')
           ->setParameter('current_user', $this->security->getUser());
    }
}
```

### 🔍 **Validation des Données**

```mermaid
graph LR
    Request[📥 Request] --> Deserializer[🔄 Deserializer]
    Deserializer --> Validator[✅ Validator]
    Validator --> Entity[🏷️ Entity]
    
    subgraph "✅ Validation Layers"
        Format[📝 Format Validation]
        Business[💼 Business Rules]
        Security[🔒 Security Checks]
    end
    
    Validator --> Format
    Validator --> Business
    Validator --> Security
```

## ⚡ Performance et Optimisation

### 📊 **Cache des Réponses**

```php
#[Cache(expires: '+1 hour', public: true)]
public function getPublicData(): JsonResponse
```

### 🔄 **Pagination Optimisée**

```php
// Configuration API Platform
paginationClientItemsPerPage: true
paginationMaximumItemsPerPage: 1000
```

### 📱 **Compression des Réponses**

```php
// Compression automatique via Symfony
// Configuration dans framework.yaml
```

## 🧪 Tests et Validation

### 🔍 **Validation des Contrôleurs**

```php
// Tests fonctionnels avec WebTestCase
class RegistrationControllerTest extends WebTestCase
{
    public function testSuccessfulRegistration()
    {
        $client = static::createClient();
        $client->request('POST', '/register', [
            'email' => 'test@example.com',
            'password' => 'motdepasse123'
        ]);
        
        $this->assertResponseIsSuccessful();
        $this->assertJsonContains(['message' => 'Compte créé avec succès']);
    }
}
```

### 📊 **Métriques des Contrôleurs**

| Contrôleur | Endpoints | Complexité | Tests | Maintenance |
|------------|-----------|------------|-------|-------------|
| 🔐 **Registration** | 1 | Élevée | ✅ Complets | 🟢 Facile |
| 📊 **Rapport** | 6 | Élevée | ⚠️ Partiels | 🟡 Moyenne |
| 📧 **EmailTest** | 2 | Faible | ✅ Complets | 🟢 Facile |
| 🖼️ **ProductImage** | 1 | Moyenne | ⚠️ Partiels | 🟡 Moyenne |
| 📞 **Contact** | 2 | Faible | ❌ Manquants | 🔴 Difficile |

## 🚀 Évolutions Prévues

### 📈 **Améliorations en Cours**

1. **🔄 Rate Limiting** : Limitation des requêtes par IP
2. **📊 Metrics** : Collecte de métriques détaillées
3. **🔒 Enhanced Security** : Validation renforcée
4. **⚡ Caching** : Cache intelligent des réponses

### 🎯 **Roadmap**

```mermaid
graph LR
    Current[📍 Actuel] --> Q1[Q1 2024]
    Q1 --> Q2[Q2 2024]
    Q2 --> Q3[Q3 2024]
    
    Q1 --> RateLimit[🔄 Rate Limiting]
    Q1 --> Metrics[📊 Detailed Metrics]
    
    Q2 --> GraphQL[🎯 GraphQL Support]
    Q2 --> WebSocket[🔌 Real-time APIs]
    
    Q3 --> Microservices[🏗️ Microservices Split]
    Q3 --> AI[🤖 AI Integration]
```

---

> 💡 **Best Practices** : Les contrôleurs suivent les principes **SOLID** et maintiennent une **séparation claire** entre logique de présentation et logique métier.