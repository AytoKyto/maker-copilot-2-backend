# 🏛️ Architecture Générale - Maker Copilot

## 📋 Vue d'Ensemble

Maker Copilot est construit sur une **architecture moderne** basée sur Symfony 6.4 avec API Platform, suivant les principes **DDD (Domain Driven Design)** et **Clean Architecture**.

## 🏗️ Architecture Globale

```mermaid
graph TB
    subgraph "🖥️ Couche Présentation"
        Frontend[Frontend SPA]
        API[API REST]
        CLI[Console Commands]
    end
    
    subgraph "⚙️ Couche Application"
        Controllers[🎮 Controllers]
        Services[🔧 Services]
        EventListeners[👂 Event Listeners]
    end
    
    subgraph "💼 Couche Domaine"
        Entities[🏷️ Entities]
        Repositories[📚 Repositories]
        Contracts[📋 Contracts/Interfaces]
    end
    
    subgraph "🗄️ Couche Infrastructure"
        DB[(Database)]
        SMTP[📧 SMTP Server]
        FileSystem[📁 File System]
        JWT[🔐 JWT Service]
    end
    
    Frontend --> API
    CLI --> Services
    API --> Controllers
    Controllers --> Services
    Services --> Repositories
    Services --> Contracts
    Repositories --> Entities
    Entities --> DB
    Services --> SMTP
    Services --> FileSystem
    EventListeners --> JWT
```

## 📁 Structure des Dossiers

```
src/
├── 🎮 Controller/           # Contrôleurs API REST
├── 🏷️ Entity/              # Entités Doctrine
├── 📚 Repository/           # Repositories Doctrine
├── 🔧 Service/              # Services métier
├── 📋 Contracts/            # Interfaces et contrats
├── 🎭 State/                # State processors API Platform
├── 👂 EventListener/        # Event listeners
├── 🗂️ Model/               # Modèles de données
├── 🚀 ApiResource/          # Ressources API Platform
├── ⚡ Command/              # Commandes console
├── 📊 Scheduler/            # Tâches programmées
└── 🔧 Doctrine/             # Extensions Doctrine
```

## 🎯 Patterns Architecturaux Utilisés

### 1. 🎭 **Strategy Pattern**
Utilisé pour le système de génération de rapports :

```mermaid
classDiagram
    class RapportStrategyInterface {
        <<interface>>
        +supports(type: string) bool
        +execute(data: array) array
    }
    
    class RapportManager {
        -strategies: array
        +getStrategy(type: string)
        +addStrategy(strategy)
    }
    
    class SaleAnalysis {
        +supports(type) bool
        +execute(data) array
    }
    
    class ProductPerf {
        +supports(type) bool
        +execute(data) array
    }
    
    RapportStrategyInterface <|-- SaleAnalysis
    RapportStrategyInterface <|-- ProductPerf
    RapportManager --> RapportStrategyInterface
```

### 2. 🏭 **Repository Pattern**
Encapsulation de la logique d'accès aux données :

```mermaid
classDiagram
    class UserRepository {
        +findByEmail(email: string)
        +findActiveUsers()
        +findBySubscriptionType(type: int)
    }
    
    class SaleRepository {
        +findByUser(user: User)
        +findByDateRange(start, end)
        +getStatsByChannel()
    }
    
    class EntityRepository {
        <<abstract>>
        +find(id)
        +findAll()
        +save(entity)
        +remove(entity)
    }
    
    EntityRepository <|-- UserRepository
    EntityRepository <|-- SaleRepository
```

### 3. 🔧 **Dependency Injection**
Services injectés via le container Symfony :

```mermaid
graph TD
    Container[🏗️ DI Container] --> RapportManager
    Container --> EmailService
    Container --> ExcelExportService
    
    RapportManager --> SaleAnalysis[📊 SaleAnalysis]
    RapportManager --> ProductPerf[📈 ProductPerf]
    RapportManager --> ChannelAnalysis[📺 ChannelAnalysis]
    
    EmailService --> Mailer[📧 Symfony Mailer]
    ExcelExportService --> PhpSpreadsheet[📋 PhpSpreadsheet]
```

## 🌐 Architecture API

### 📡 **RESTful API avec API Platform**

```mermaid
sequenceDiagram
    participant Client
    participant Controller
    participant Service
    participant Repository
    participant Database
    
    Client->>Controller: POST /api/sales
    Controller->>Service: createSale(data)
    Service->>Repository: save(sale)
    Repository->>Database: INSERT
    Database-->>Repository: ID
    Repository-->>Service: Sale Entity
    Service-->>Controller: Sale DTO
    Controller-->>Client: 201 Created
```

### 🔐 **Authentification JWT**

```mermaid
graph LR
    Login[🔑 Login] --> JWTToken[📜 JWT Token]
    JWTToken --> APIRequest[🌐 API Request]
    APIRequest --> Validation[✅ Token Validation]
    Validation --> UserContext[👤 User Context]
    UserContext --> BusinessLogic[⚙️ Business Logic]
```

## 🗄️ Architecture de Données

### 📊 **Modèle de Données Principal**

```mermaid
erDiagram
    USER ||--o{ SALE : creates
    USER ||--o{ PRODUCT : owns
    USER ||--o{ SALES_CHANNEL : manages
    USER ||--o{ CLIENT : has
    USER ||--o{ SPENT : records
    
    SALE ||--o{ SALES_PRODUCT : contains
    SALE }o--|| SALES_CHANNEL : through
    
    PRODUCT ||--o{ SALES_PRODUCT : sold_in
    PRODUCT ||--o{ PRICE : has
    PRODUCT }o--o{ CATEGORY : belongs_to
    
    CLIENT ||--o{ SALES_PRODUCT : buys
    
    USER {
        int id PK
        string email UK
        string password
        json roles
        float urssaf_pourcent
        int type_subscription
        datetime created_at
    }
    
    SALE {
        int id PK
        float price
        float benefit
        float commission
        float expense
        datetime created_at
        int user_id FK
        int canal_id FK
    }
```

### 📈 **Vues SQL pour Analytics**

Le système utilise 21 vues SQL optimisées pour les rapports :

```mermaid
graph TB
    subgraph "📊 Vues de Bénéfices"
        BenefitMonth[💰 view_benefit_month]
        BenefitYear[💰 view_benefit_year]
        BenefitByChannel[📺 view_benefit_month_canal]
        BenefitByProduct[📦 view_benefit_month_product]
    end
    
    subgraph "🏆 Vues de Performance"
        BestProductMonth[🥇 view_best_product_sales_month]
        BestProductYear[🥇 view_best_product_sales_year]
        ChannelPerf[📺 view_canal_month]
    end
    
    subgraph "📊 Tables Source"
        Sales[(Sales)]
        Products[(Products)]
        Channels[(Sales Channels)]
    end
    
    Sales --> BenefitMonth
    Sales --> BestProductMonth
    Products --> BestProductYear
    Channels --> ChannelPerf
```

## 🔧 Services & Composants

### 📈 **Système de Rapports**

```mermaid
graph TD
    RapportController[🎮 RapportController] --> RapportManager[📊 RapportManager]
    
    RapportManager --> SaleAnalysis[📊 Analyse des Ventes]
    RapportManager --> ProductPerf[📦 Performance Produits]
    RapportManager --> ChannelAnalysis[📺 Analyse Canaux]
    RapportManager --> ProfitabilityStrategy[💰 Rentabilité]
    RapportManager --> CustomInsights[🔍 Insights Personnalisés]
    RapportManager --> EmailRapport[📧 Rapports Email]
    
    SaleAnalysis --> ExcelExport[📋 Export Excel]
    ProductPerf --> ExcelExport
    ChannelAnalysis --> ExcelExport
```

### 📧 **Système d'Email**

```mermaid
graph LR
    EmailService[📧 EmailService] --> Mailer[📮 Symfony Mailer]
    EmailService --> Templates[📄 Twig Templates]
    
    subgraph "📬 Types d'Emails"
        Welcome[🎉 Bienvenue]
        ForgotPassword[🔑 Mot de passe oublié]
        Reports[📊 Rapports]
        Notifications[🔔 Notifications]
    end
    
    EmailService --> Welcome
    EmailService --> ForgotPassword
    EmailService --> Reports
    EmailService --> Notifications
```

## 🔒 Sécurité

### 🛡️ **Couches de Sécurité**

```mermaid
graph TD
    Request[🌐 HTTP Request] --> CORS[🔒 CORS Filter]
    CORS --> JWT[🔐 JWT Validation]
    JWT --> Authorization[👮 Authorization]
    Authorization --> DataFilter[🔍 Data Filtering]
    DataFilter --> BusinessLogic[⚙️ Business Logic]
    
    subgraph "🔐 Contrôles d'Accès"
        UserScope[👤 User Scope]
        RoleCheck[🎭 Role Check]
        ResourceOwner[👑 Resource Owner]
    end
    
    Authorization --> UserScope
    Authorization --> RoleCheck
    Authorization --> ResourceOwner
```

### 🔍 **Filtrage Automatique des Données**

```php
// Extension Doctrine pour filtrer automatiquement par utilisateur
class CurrentUserExtension implements QueryCollectionExtensionInterface
{
    public function applyToCollection(/* ... */)
    {
        $this->addWhere($queryBuilder, 'o.user = :current_user');
    }
}
```

## ⚡ Performance & Optimisation

### 📊 **Stratégies d'Optimisation**

1. **🚀 Eager Loading** : Configuration API Platform avec max 9000 joins
2. **📱 Pagination** : Pagination configurée (max 1000 items)
3. **🗄️ Vues SQL** : Pré-calculs pour les rapports complexes
4. **💾 Cache** : Cache Symfony pour les données statiques
5. **📊 Indexation** : Index sur les colonnes fréquemment utilisées

### 📈 **Monitoring & Observabilité**

```mermaid
graph LR
    App[🚀 Application] --> Logs[📝 Logs]
    App --> Metrics[📊 Métriques]
    App --> Sentry[🔍 Sentry]
    
    Logs --> Monolog[📋 Monolog]
    Metrics --> Messenger[📮 Messenger]
    Sentry --> ErrorTracking[🐛 Error Tracking]
```

## 🚀 Évolutivité

### 📈 **Axes d'Évolution**

1. **🔄 Microservices** : Possibilité de découper en services
2. **📊 Event Sourcing** : Pour l'historique des modifications
3. **🚀 CQRS** : Séparation lecture/écriture pour les rapports
4. **☁️ Cloud Native** : Déploiement containerisé

### 🏗️ **Architecture Future**

```mermaid
graph TB
    subgraph "🌐 API Gateway"
        Gateway[🚪 Gateway]
    end
    
    subgraph "🔧 Services"
        UserService[👤 User Service]
        SalesService[💰 Sales Service]
        ReportsService[📊 Reports Service]
        NotificationService[📧 Notification Service]
    end
    
    subgraph "📊 Data Layer"
        UserDB[(👤 User DB)]
        SalesDB[(💰 Sales DB)]
        ReportsDB[(📊 Reports DB)]
        EventStore[(📝 Event Store)]
    end
    
    Gateway --> UserService
    Gateway --> SalesService
    Gateway --> ReportsService
    Gateway --> NotificationService
    
    UserService --> UserDB
    SalesService --> SalesDB
    ReportsService --> ReportsDB
    
    UserService --> EventStore
    SalesService --> EventStore
```

---

> 💡 **Note** : Cette architecture est conçue pour être **évolutive** et **maintenable**, permettant une croissance progressive du projet tout en conservant la qualité du code.