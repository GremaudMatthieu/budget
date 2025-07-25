# GoGoBudgeto 💰

A modern, full-stack budget management application built with React Native/Expo and Symfony, featuring event sourcing architecture.

🌐 **Live Demo**: [https://gogobudgeto.com](https://gogobudgeto.com)

## 📱 Overview

GoGoBudgeto is a comprehensive personal finance management solution that helps users track their spending through envelope budgeting and create detailed budget plans. The application supports multiple platforms (iOS, Android, Web) with a robust backend API featuring advanced architectural patterns.

### ✨ Key Features

- **🎯 Envelope Budgeting**: Create and manage budget envelopes with targeted amounts
- **📊 Budget Planning**: Comprehensive budget plans with income, needs, wants, and savings categorization
- **💰 Real-time Transactions**: Credit/debit operations with instant balance updates
- **🌍 Multi-currency Support**: Handle different currencies across budget items
- **🔐 OAuth Authentication**: Secure Google OAuth integration
- **📱 Cross-platform**: Native mobile apps (iOS/Android) and responsive web interface
- **🌐 Internationalization**: Multi-language support (English/French)

## 🏗️ Architecture

### System Architecture

```
┌─────────────────┐    HTTPS     ┌─────────────────┐
│  Mobile Apps    │◄────────────►│   Backend API   │
│  (iOS/Android)  │              │   (Symfony)     │
└─────────────────┘              └─────────────────┘
          ▲                               ▲
          │                               │
          ▼                               ▼
┌─────────────────┐              ┌─────────────────┐
│   Web App       │              │   PostgreSQL    │
│  (React/Expo)   │              │   Database      │
└─────────────────┘              └─────────────────┘
```

### Backend Architecture Patterns

The backend implements several advanced architectural patterns:

#### 🎯 **Domain-Driven Design (DDD)**
- **Bounded Contexts**: Separate contexts for Budget Envelopes, Budget Plans, and Users
- **Aggregates**: Rich domain models with business logic encapsulation
- **Value Objects**: Immutable objects for concepts like Money, Currency, Names
- **Domain Events**: Business events that drive the system

#### 🔄 **Event Sourcing with CQRS**
- **Event Store**: All state changes stored as immutable events
- **Projections**: Read models built from event streams
- **Command/Query Separation**: Separate write and read operations
- **Event Replay**: Ability to reconstruct state from events

#### 🏛️ **Hexagonal Architecture (Ports & Adapters)**
- **Domain Layer**: Pure business logic without dependencies
- **Application Layer**: Use cases and application services
- **Infrastructure Layer**: Persistence, external services
- **Presentation Layer (Gateway)**: HTTP, DTOs, View models, Projections
- **Clear Boundaries**: Well-defined interfaces between layers

#### 📚 **Custom Event Sourcing Framework (FluxCapacitor)**
- **Aggregate Tracking**: Automatic aggregate state management
- **Event Encryption**: Personal data encryption for GDPR compliance
- **Snapshots**: Performance optimization for large event streams
- **Event Versioning**: Backward-compatible event evolution

## 🛠️ Tech Stack

### Frontend (`frontendv2/`)
- **Framework**: React Native with Expo Router
- **Styling**: NativeWind (Tailwind CSS for React Native)
- **Navigation**: Expo Router with tab-based navigation
- **State Management**: React Context API
- **Authentication**: Expo Auth Session with Google OAuth
- **Storage**: Expo SecureStore (mobile) / AsyncStorage (web)
- **HTTP Client**: Axios with automatic token refresh
- **Internationalization**: Custom i18n implementation
- **Icons**: Ionicons, Lucide React Native
- **Charts**: Custom pie charts for budget visualization

### Backend (`backend/`)
- **Framework**: Symfony 7.3 (PHP 8.4)
- **Architecture**: DDD + Event Sourcing + CQRS + Hexagonal
- **Database**: PostgreSQL 17.4
- **Authentication**: JWT with refresh tokens (LexikJWTAuthenticationBundle)
- **OAuth**: Google OAuth2 integration
- **Validation**: Symfony Validator with custom constraints
- **Testing**: PHPUnit with domain testing
- **Code Quality**: PHPStan (level max), PHP-CS-Fixer, Rector
- **Containerization**: Docker with Docker Compose
- **Deployment**: Deployer for automated deployments

### Infrastructure
- **Containerization**: Docker with multi-service setup
- **Web Server**: Nginx with SSL termination
- **SSL**: Let's Encrypt automatic certificate management

## 🚀 Getting Started

### Prerequisites
- **Docker & Docker Compose** (for backend)
- **Node.js 18+** and **npm/yarn** (for frontend)
- **Expo CLI** (`npm install -g @expo/cli`)

### Backend Setup

1. **Clone and navigate to backend**:
```bash
git clone <repository-url>
cd budget/backend
```

2. **Environment setup**:
```bash
cp .env.example .env
# Edit .env with your database credentials and API keys
```

3. **Start services**:
```bash
make up                    # Start Docker containers
make composer-install      # Install dependencies
make database-create       # Create database
make migration-apply       # Run migrations
make jwt-generate-key      # Generate JWT keys
```

4. **Verify installation**:
```bash
# API should be available at http://localhost
curl http://localhost/
```

### Frontend Setup

1. **Navigate to frontend**:
```bash
cd ../frontendv2
```

2. **Install dependencies**:
```bash
npm install
# or
yarn install
```

3. **Environment setup**:
```bash
# Create .env file with API URL
echo "EXPO_PUBLIC_API_URL=http://localhost/api" > .env
```

4. **Start development server**:
```bash
npm start
# or
expo start
```

5. **Run on platforms**:
```bash
npm run web      # Web browser
npm run ios      # iOS simulator
npm run android  # Android emulator
```

## 📱 Development

### Available Scripts

#### Backend Commands (via Makefile)
```bash
make up                 # Start all services
make down              # Stop all services
make shell             # Access container shell
make cache-clear       # Clear Symfony cache
make phpunit           # Run tests
make phpstan           # Static analysis
make cs-fixer          # Code formatting
```

#### Frontend Commands
```bash
npm start              # Start Expo dev server
npm run web           # Start web development
npm run ios           # Start iOS development
npm run android       # Start Android development
npm test              # Run tests
npm run lint          # Lint code
```

### Project Structure

```
budget/
├── backend/                    # Symfony API
│   ├── src/
│   │   ├── BudgetEnvelopeContext/     # Envelope management
│   │   ├── BudgetPlanContext/         # Budget planning
│   │   ├── UserContext/               # User management
│   │   ├── Gateway/                   # HTTP/CLI interfaces
│   │   └── Libraries/FluxCapacitor/   # Event sourcing framework
│   ├── config/                # Symfony configuration
│   ├── docker-compose.yml     # Docker services
│   └── Makefile              # Development commands
│
├── frontendv2/               # React Native/Expo app
│   ├── app/                  # App router pages
│   │   ├── (auth)/          # Authentication screens
│   │   └── (tabs)/          # Main tab navigation
│   ├── components/          # Reusable UI components
│   ├── contexts/           # React Context providers
│   ├── services/           # API clients
│   ├── hooks/              # Custom React hooks
│   └── utils/              # Utility functions
│
└── README.md               # This file
```

## 🔐 Authentication Flow

1. **OAuth Setup**: Configure Google OAuth credentials
2. **Token Exchange**: Frontend exchanges OAuth code for JWT tokens
3. **Token Storage**: Secure storage of access/refresh tokens
4. **Auto-refresh**: Automatic token refresh on expiration
5. **Logout**: Secure token cleanup and redirect

## 🎯 Key Features Deep Dive

### Envelope Budgeting
- Create budget envelopes with target amounts
- Credit/debit operations with real-time balance updates
- Transaction history with descriptions
- Multi-currency support
- Progress tracking and visual indicators

### Budget Planning
- Categorized planning (Income, Needs, Wants, Savings)
- Monthly/yearly budget templates
- Financial ratio calculations and insights
- Calendar view of budget plans
- Duplicate and modify existing plans

### Event Sourcing Benefits
- **Complete Audit Trail**: Every change is recorded
- **Time Travel**: View state at any point in time
- **Replay Capability**: Rebuild projections from events
- **Debugging**: Full visibility into what happened when
- **Compliance**: GDPR-compliant with personal data encryption

## 🧪 Testing

### Backend Testing
```bash
make phpunit              # Run all tests
make phpunit-coverage     # Generate coverage report
make phpstan             # Static analysis
```

### Frontend Testing
```bash
npm test                 # Run Jest tests
npm run lint            # ESLint checking
```

### Test Coverage
- **Domain Logic**: Comprehensive unit tests for aggregates and value objects
- **API Endpoints**: Integration tests for all HTTP endpoints
- **Event Sourcing**: Event replay and projection tests

## 📊 Monitoring & Observability

- **Application Logs**: Structured logging with Monolog
- **Error Tracking**: Comprehensive error handling and reporting
- **Performance Monitoring**: Request/response time tracking
- **Health Checks**: Endpoint health monitoring
- **Database Monitoring**: Query performance and connection health

## 📄 License

This project is proprietary software. All rights reserved.

## 🎯 Roadmap

- [ ] **Real-time Notifications**: Push notifications for budget alerts
- [ ] **Analytics Dashboard**: Advanced spending analytics and insights
- [ ] **Recurring Transactions**: Automated recurring income/expenses
- [ ] **Goal Tracking**: Savings goals with progress tracking
- [ ] **Category Management**: Custom expense categories
- [ ] **Export Features**: PDF/CSV export of financial data
- [ ] **Mobile Offline Support**: Offline-first mobile experience
- [ ] **Advanced Reporting**: Comprehensive financial reports

---

**Built with ❤️**
