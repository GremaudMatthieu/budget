# FluxCapacitor 🚀

**Event Sourcing Framework for PHP**

FluxCapacitor is an event sourcing library designed for building scalable, auditable, and maintainable applications with complete event-driven architecture support.

## 🌟 Features

- **🔄 Event Sourcing**: Complete event-driven architecture with aggregate reconstruction
- **📸 Snapshots**: Automatic performance optimization with configurable snapshot frequency
- **🔒 Data Encryption**: Built-in PII encryption with user-specific key management
- **⚡ Event Versioning**: Backward-compatible event schema evolution with upcasting
- **🎯 CQRS Support**: Separate command and query responsibilities
- **📊 Replay & Rewind**: Debug and analyze system state at any point in time
- **🏗️ Dependency Injection**: Full Symfony container integration
- **📝 Request Tracking**: Complete audit trail with request correlation

## 🏛️ Architecture

```
┌─────────────────────────────────────────────────────────┐
│                 FluxCapacitor Architecture              │
├─────────────────────────────────────────────────────────┤
│  EventStore    │  Anonymizer   │  Aggregates & Events   │
│  ├─ EventStore │  ├─ Encryption│  ├─ AggregateRoot      │
│  ├─ Snapshots  │  ├─ KeyMgmt   │  ├─ DomainEvents       │
│  ├─ Upcasting  │  └─ PII Data  │  └─ Value Objects      │
│  └─ EventBus   │               │                        │
└─────────────────────────────────────────────────────────┘
```

## 🚀 Quick Start

### 1. Installation

```bash
# Add to your Symfony project
composer require symfony/messenger doctrine/orm
```

### 2. Configuration

```yaml
# config/services.yaml
imports:
    - { resource: '../src/Libraries/FluxCapacitor/Config/services.yaml' }

parameters:
    env(KEY_MANAGEMENT_SECRET): 'your-encryption-secret'
```

**Event Mappings Configuration:**

Create the essential event mappings file:

```yaml
# config/fluxCapacitor/event_mappings.yaml
events:
  UserSignedUpDomainEvent_v1: App\UserContext\Domain\Events\UserSignedUpDomainEvent_v1
  UserFirstnameChangedDomainEvent_v1: App\UserContext\Domain\Events\UserFirstnameChangedDomainEvent_v1
  UserDeletedDomainEvent_v1: App\UserContext\Domain\Events\UserDeletedDomainEvent_v1
  # ... add all your domain events

aggregates:
  User: App\UserContext\Domain\Aggregates\User
  UserEmailRegistry: App\UserContext\Domain\Aggregates\UserEmailRegistry
  # ... add all your aggregates

event_to_aggregate:
  UserSignedUpDomainEvent_v1: User
  UserFirstnameChangedDomainEvent_v1: User
  UserDeletedDomainEvent_v1: User
  UserEmailRegisteredDomainEvent_v1: UserEmailRegistry
  UserEmailReleasedDomainEvent_v1: UserEmailRegistry
  # ... map events to their owning aggregates
```

This configuration is **required** for:
- Event deserialization and class mapping
- Aggregate reconstruction from events
- Projection event routing
- Stream name resolution

### 3. Create a Domain Event

```php
<?php

use App\Libraries\FluxCapacitor\EventStore\Ports\VersionedDomainEventInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\VersionedEventTrait;

final class UserRegisteredDomainEvent_v1 implements VersionedDomainEventInterface
{
    use VersionedEventTrait;

    public const int VERSION = 1;
    public const string EVENT_TYPE = 'UserRegistered';
    
    public function __construct(
        public string $aggregateId,
        public string $email,
        public string $name,
        public string $requestId = DomainEventInterface::DEFAULT_REQUEST_ID,
    ) {
        $this->occurredOn = UtcClock::immutableNow();
    }

    public function toArray(): array
    {
        return [
            'aggregateId' => $this->aggregateId,
            'email' => $this->email,
            'name' => $this->name,
            'requestId' => $this->requestId,
            'occurredOn' => $this->occurredOn->format(\DateTimeInterface::ATOM),
        ];
    }

    public static function fromArray(array $data): self
    {
        $event = new self(
            $data['aggregateId'],
            $data['email'],
            $data['name'],
            $data['requestId'],
        );
        $event->occurredOn = new \DateTimeImmutable($data['occurredOn']);
        return $event;
    }
}
```

### 4. Create an Aggregate Root

```php
<?php

use App\Libraries\FluxCapacitor\EventStore\Ports\AggregateRootInterface;
use App\Libraries\FluxCapacitor\EventStore\Ports\SnapshotableAggregateInterface;
use App\Libraries\FluxCapacitor\EventStore\Traits\DomainEventsCapabilityTrait;

final class User implements AggregateRootInterface, SnapshotableAggregateInterface
{
    use DomainEventsCapabilityTrait;

    private string $userId;
    private string $email;
    private string $name;
    private int $aggregateVersion = 0;

    private function __construct() {}

    public static function register(string $userId, string $email, string $name): self
    {
        $user = new self();
        $user->raiseDomainEvent(
            new UserRegisteredDomainEvent_v1($userId, $email, $name)
        );
        return $user;
    }

    public static function empty(): self
    {
        return new self();
    }

    public function getAggregateId(): string
    {
        return $this->userId;
    }

    public function setAggregateVersion(int $version): self
    {
        $this->aggregateVersion = $version;
        return $this;
    }

    // Event application methods - AUTOMATICALLY CALLED via reflection
    private function applyUserRegisteredDomainEvent_v1(UserRegisteredDomainEvent_v1 $event): void
    {
        $this->userId = $event->aggregateId;
        $this->email = $event->email;
        $this->name = $event->name;
    }

    // Snapshot support
    public function createSnapshot(): array
    {
        return [
            'userId' => $this->userId,
            'email' => $this->email,
            'name' => $this->name,
        ];
    }

    public static function fromSnapshot(array $data, int $version): self
    {
        $user = new self();
        $user->userId = $data['userId'];
        $user->email = $data['email'];
        $user->name = $data['name'];
        $user->aggregateVersion = $version;
        return $user;
    }
}
```

### 5. Use the Event Store

```php
<?php

class UserService
{
    public function __construct(
        private EventStoreInterface $eventStore,
    ) {}

    public function registerUser(string $userId, string $email, string $name): void
    {
        $user = User::register($userId, $email, $name);
        $this->eventStore->save($user);
    }

    public function getUser(string $userId): User
    {
        return $this->eventStore->load($userId);
    }

    public function getUserAtDate(string $userId, \DateTimeImmutable $date): User
    {
        return $this->eventStore->load($userId, $date);
    }
}
```

## 🔧 Core Components

### Event Store

The heart of FluxCapacitor, managing event persistence and aggregate reconstruction.

```php
interface EventStoreInterface
{
    public function load(string $uuid, ?\DateTimeImmutable $desiredDateTime = null): AggregateRootInterface;
    public function save(AggregateRootInterface $aggregate): void;
    public function saveMultiAggregate(array $aggregates): void;
}
```

**Features:**
- Automatic event persistence
- Aggregate reconstruction from events
- Point-in-time querying
- Multi-aggregate transactions
- Concurrent modification detection

### Snapshots

Automatic performance optimization for large aggregates.

```php
// Configure snapshot frequency
App\Libraries\FluxCapacitor\EventStore\Services\SnapshotService:
    arguments:
        $snapshotFrequency: 50  # Snapshot every 50 events
```

**Benefits:**
- Reduces event replay time
- Configurable snapshot frequency
- Automatic cleanup of old snapshots
- Transparent to application code

### Event Versioning & Upcasting

Handle schema evolution gracefully with versioned events.

```php
// Event mapping configuration
events:
  UserRegisteredDomainEvent_v1: App\Domain\Events\UserRegisteredDomainEvent_v1
  UserRegisteredDomainEvent_v2: App\Domain\Events\UserRegisteredDomainEvent_v2

// Upcasting service
final class EventUpcastingService implements EventUpcastingServiceInterface
{
    public function upcastEvent(array $eventData): array
    {
        return match([$eventData['event_name'], $eventData['event_version']]) {
            ['UserRegisteredDomainEvent_v1', 1] => $this->upcastUserRegisteredV1ToV2($eventData),
            default => $eventData,
        };
    }
}
```

### Data Encryption & Anonymization

Built-in PII protection with user-specific encryption using the `#[PersonalData]` attribute.

```php
<?php

use App\Libraries\FluxCapacitor\Anonymizer\Attributes\PersonalData;
use App\Libraries\FluxCapacitor\Anonymizer\Ports\UserDomainEventInterface;

// Example 1: User sign-up event with multiple PII fields
final class UserSignedUpDomainEvent_v1 implements UserDomainEventInterface
{
    public string $aggregateId;
    
    #[PersonalData]
    public string $email;
    
    #[PersonalData]
    public string $firstname;
    
    #[PersonalData]
    public string $lastname;
    
    #[PersonalData]
    public string $languagePreference;
    
    public bool $isConsentGiven;
    public array $roles;
    public string $userId;
    // ... other non-sensitive fields
}

// Example 2: Simple field change event
final class UserFirstnameChangedDomainEvent_v1 implements UserDomainEventInterface
{
    public string $aggregateId;
    
    #[PersonalData]
    public string $firstname;
    
    public string $userId;
    public string $requestId;
    // ... other fields
}
```

**Automatic Encryption/Decryption:**
```php
// EventEncryptor automatically handles fields marked with #[PersonalData]
final class EventEncryptor implements EventEncryptorInterface
{
    public function encrypt(AbstractUserDomainEventInterface $event, string $userId): AbstractUserDomainEventInterface
    {
        $reflection = new ReflectionClass($event);
        
        foreach ($reflection->getProperties() as $property) {
            if ($this->hasPersonalDataAttribute($property)) {
                $property->setAccessible(true);
                $value = $property->getValue($event);
                
                if (!is_null($value)) {
                    $encrypted = $this->encryptionService->encrypt((string) $value, $userId);
                    $property->setValue($event, json_encode($encrypted));
                }
            }
        }
        
        return $event;
    }
    
    private function hasPersonalDataAttribute(ReflectionProperty $property): bool
    {
        return count($property->getAttributes(PersonalData::class)) > 0;
    }
}
```

**Features:**
- **Attribute-based PII marking**: Simply add `#[PersonalData]` to sensitive fields
- **Automatic encryption/decryption**: Transparent handling in event store
- **User-specific encryption keys**: Each user has their own encryption key
- **GDPR-compliant**: Built-in data anonymization capabilities
- **Reflection-based detection**: No manual configuration required
- **Invisible to users**: Encryption/decryption happens behind the scenes

## 🎯 Advanced Usage

### SaveAggregateMiddleware - Automatic Persistence

FluxCapacitor uses Symfony Messenger middleware to automatically save aggregates after command execution.

```php
#[AutoconfigureTag('messenger.middleware', ['bus' => 'command_bus', 'priority' => 100])]
final readonly class SaveAggregateMiddleware implements MiddlewareInterface
{
    public function __construct(private EventStore $eventStore) {}

    public function handle(Envelope $envelope, StackInterface $stack): Envelope
    {
        $this->eventStore->clearTrackedAggregates();
        
        // Execute the command
        $envelope = $stack->next()->handle($envelope, $stack);
        
        // Automatically save tracked aggregates
        $trackedAggregates = $this->eventStore->getTrackedAggregates();
        
        if (count($trackedAggregates) === 1) {
            $this->eventStore->save($trackedAggregates[0]);
        } elseif (count($trackedAggregates) > 1) {
            $this->eventStore->saveMultiAggregate($trackedAggregates);
        }
        
        return $envelope;
    }
}
```

**Key Benefits:**
- **Automatic Persistence**: No manual save() calls needed in command handlers
- **Multi-Aggregate Support**: Handles complex transactions automatically
- **Unit of Work Pattern**: Tracks all aggregates touched during command execution
- **Transactional Integrity**: Ensures all aggregates are saved atomically

### Automatic Event Application System

FluxCapacitor automatically applies events to aggregates using **reflection-based method invocation**.

```php
// In DomainEventsCapabilityTrait
protected function raiseDomainEvent(DomainEventInterface $event): void
{
    // Automatically calls apply method via reflection
    $this->{sprintf('apply%s', new \ReflectionClass($event)->getShortName())}($event);
    $this->events[] = $event;
}
```

**How it works:**
1. When you call `$this->raiseDomainEvent(new UserSignedUpDomainEvent_v1(...))` 
2. FluxCapacitor automatically calls `$this->applyUserSignedUpDomainEvent_v1($event)`
3. The apply method updates the aggregate's internal state
4. The event is stored for later persistence

**Naming Convention:**
- Event class: `UserSignedUpDomainEvent_v1`
- Auto-called method: `applyUserSignedUpDomainEvent_v1()`
- Pattern: `apply{EventClassName}()`

**Benefits:**
- **Zero Configuration**: No manual method registration needed
- **Type Safety**: Method signatures enforce correct event types
- **Automatic Discovery**: Events are automatically applied during aggregate reconstruction
- **Consistent Naming**: Clear naming convention for apply methods

### Event Mappings & Configuration System

FluxCapacitor uses the `event_mappings.yaml` configuration file for critical system operations:

```php
// EventClassMap loads and uses the configuration
final readonly class EventClassMap implements EventClassMapInterface
{
    public function __construct(KernelInterface $kernel)
    {
        $config = Yaml::parseFile($kernel->getProjectDir().'/config/fluxCapacitor/event_mappings.yaml');
        $this->eventMappings = $config['events'] ?? [];
        $this->aggregateMappings = $config['aggregates'] ?? [];
        $this->eventToAggregateMap = $config['event_to_aggregate'] ?? [];
    }
    
    public function getStreamNameByEventPath(string $eventPath): string
    {
        $className = basename(str_replace('\\', '/', $eventPath));
        
        // Uses event_to_aggregate mapping
        if (isset($this->eventToAggregateMap[$className])) {
            return $this->eventToAggregateMap[$className];
        }
        
        // Fallback to aggregate detection
        return $this->detectAggregateFromPath($eventPath);
    }
}
```

**Used by:**
- **EventStore**: For event serialization/deserialization and stream organization
- **Projections**: For routing events to the correct projection handlers
- **Aggregate Reconstruction**: For mapping events back to their originating aggregates
- **Event Dispatching**: For determining which aggregate root to load and apply events to

### Required Traits for FluxCapacitor

FluxCapacitor requires specific traits to function correctly:

#### For User Aggregates (with encryption)
```php
use App\Libraries\FluxCapacitor\Anonymizer\Traits\UserDomainEventsCapabilityTrait;
use App\Libraries\FluxCapacitor\Anonymizer\Traits\EncryptedKeyCacheTrait;

final class User implements AggregateRootInterface, UserAggregateInterface, SnapshotableAggregateInterface
{
    use UserDomainEventsCapabilityTrait;  // Handles user events with encryption
    use EncryptedKeyCacheTrait;           // Caches encryption keys for performance
    
    // ...
}
```

#### For Regular Aggregates (without encryption)
```php
use App\Libraries\FluxCapacitor\EventStore\Traits\DomainEventsCapabilityTrait;

final class BudgetEnvelope implements AggregateRootInterface, SnapshotableAggregateInterface
{
    use DomainEventsCapabilityTrait;  // Standard domain events handling
    
    // ...
}
```

#### For Event Store (tracking aggregates)
```php
use App\Libraries\FluxCapacitor\EventStore\Traits\AggregateTrackerTrait;

final class EventStore implements EventStoreInterface
{
    use AggregateTrackerTrait;  // Tracks aggregates for middleware
    
    // ...
}
```

### Event Bus Integration

FluxCapacitor integrates seamlessly with Symfony Messenger for event distribution.

```php
// Automatic event dispatching after save
class EventBus implements EventBusInterface
{
    public function __construct(private MessageBusInterface $messageBus) {}

    public function execute(array $events): void
    {
        foreach ($events as $event) {
            $this->messageBus->dispatch($event);
        }
    }
}
```

### Request Correlation

Track requests across your entire system with automatic correlation IDs.

```php
// Automatic request ID injection
class RequestIdProvider
{
    public function getRequestId(): string
    {
        return $this->requestStack->getCurrentRequest()?->headers->get('X-Request-ID') 
            ?? DomainEventInterface::DEFAULT_REQUEST_ID;
    }
}
```

### Multi-Aggregate Transactions

Handle complex business operations spanning multiple aggregates.

```php
public function transferMoney(string $fromAccount, string $toAccount, Money $amount): void
{
    $from = $this->eventStore->load($fromAccount);
    $to = $this->eventStore->load($toAccount);
    
    $from->debit($amount);
    $to->credit($amount);
    
    // Atomic save of both aggregates
    $this->eventStore->saveMultiAggregate([$from, $to]);
}
```

### Registry Pattern for Uniqueness Validation

FluxCapacitor implements a sophisticated registry pattern for enforcing uniqueness constraints across aggregates.

```php
// Registry aggregate for managing unique constraints
final class BudgetEnvelopeNameRegistry implements AggregateRootInterface
{
    use DomainEventsCapabilityTrait;
    
    private array $registeredNames = [];
    
    public function registerName(
        BudgetEnvelopeName $name, 
        UserId $userId, 
        BudgetEnvelopeId $envelopeId
    ): void {
        $nameKey = $this->generateNameKey((string) $name, (string) $userId);
        
        if (isset($this->registeredNames[$nameKey]) && 
            $this->registeredNames[$nameKey] !== (string) $envelopeId) {
            throw new BudgetEnvelopeNameAlreadyExistsForUserException();
        }
        
        $this->raiseDomainEvent(new BudgetEnvelopeNameRegisteredDomainEvent_v1(
            $this->aggregateId,
            (string) $userId,
            (string) $name,
            (string) $envelopeId,
        ));
    }
    
    public function releaseName(
        BudgetEnvelopeName $name, 
        UserId $userId, 
        BudgetEnvelopeId $envelopeId
    ): void {
        $this->raiseDomainEvent(new BudgetEnvelopeNameReleasedDomainEvent_v1(
            $this->aggregateId,
            (string) $userId,
            (string) $name,
            (string) $envelopeId,
        ));
    }
    
    private function generateNameKey(string $name, string $userId): string
    {
        return $userId . ':' . mb_strtolower($name);
    }
}
```

**Registry Builder Pattern:**
```php
// Fluent builder for complex registry operations
final class BudgetEnvelopeNameRegistryBuilder
{
    private ?BudgetEnvelopeNameRegistry $currentRegistry = null;
    private ?BudgetEnvelopeNameRegistry $oldRegistry = null;
    
    public function loadOrCreateRegistry(BudgetEnvelopeNameRegistryId $registryId): self
    {
        try {
            $registry = $this->eventSourcedRepository->get((string) $registryId);
            $this->currentRegistry = $registry;
        } catch (EventsNotFoundForAggregateException) {
            $this->currentRegistry = BudgetEnvelopeNameRegistry::create($registryId);
        }
        
        return $this;
    }
    
    public function ensureNameIsAvailable(
        BudgetEnvelopeName $name, 
        UserId $userId, 
        ?BudgetEnvelopeId $currentEnvelopeId = null
    ): self {
        // Check uniqueness constraint by analyzing domain events
        $isInUse = false;
        $currentOwner = null;
        
        foreach ($this->currentRegistry->raisedDomainEvents() as $event) {
            if ($event instanceof BudgetEnvelopeNameRegisteredDomainEvent_v1 &&
                $event->name === (string) $name &&
                $event->userId === (string) $userId) {
                $isInUse = true;
                $currentOwner = $event->budgetEnvelopeId;
            }
            
            if ($event instanceof BudgetEnvelopeNameReleasedDomainEvent_v1 &&
                $event->name === (string) $name &&
                $event->userId === (string) $userId) {
                $isInUse = false;
                $currentOwner = null;
            }
        }
        
        if ($isInUse && ($currentEnvelopeId === null || $currentOwner !== (string) $currentEnvelopeId)) {
            throw new BudgetEnvelopeNameAlreadyExistsForUserException();
        }
        
        return $this;
    }
    
    public function registerName(
        BudgetEnvelopeName $name, 
        UserId $userId, 
        BudgetEnvelopeId $envelopeId
    ): self {
        $this->currentRegistry->registerName($name, $userId, $envelopeId);
        return $this;
    }
    
    public function getRegistryAggregates(): array
    {
        $registries = [];
        
        if ($this->currentRegistry !== null && count($this->currentRegistry->raisedDomainEvents()) > 0) {
            $registries[] = $this->currentRegistry;
        }
        
        if ($this->oldRegistry !== null && count($this->oldRegistry->raisedDomainEvents()) > 0) {
            $registries[] = $this->oldRegistry;
        }
        
        return $registries;
    }
}
```

**Usage in Command Handlers:**
```php
public function handle(AddABudgetEnvelopeCommand $command): void
{
    $aggregatesToSave = BudgetEnvelopeNameRegistryBuilder::build(
        $this->eventSourcedRepository,
        $this->uuidGenerator,
    )
    ->loadOrCreateRegistry(
        BudgetEnvelopeNameRegistryId::fromUserIdAndBudgetEnvelopeName(
            $command->getBudgetEnvelopeUserId(),
            $command->getBudgetEnvelopeName(),
            $this->uuidGenerator,
        )
    )
    ->ensureNameIsAvailable(
        $command->getBudgetEnvelopeName(), 
        $command->getBudgetEnvelopeUserId()
    )
    ->registerName(
        $command->getBudgetEnvelopeName(),
        $command->getBudgetEnvelopeUserId(),
        $command->getBudgetEnvelopeId(),
    )
    ->getRegistryAggregates();
    
    $aggregatesToSave[] = BudgetEnvelope::create(
        $command->getBudgetEnvelopeId(),
        $command->getBudgetEnvelopeUserId(),
        $command->getBudgetEnvelopeTargetedAmount(),
        $command->getBudgetEnvelopeName(),
        $command->getBudgetEnvelopeCurrency(),
        $command->getContext(),
    );
    
    // Save all aggregates atomically
    $this->eventSourcedRepository->trackAggregates($aggregatesToSave);
}
```

**Registry Pattern Benefits:**
- **Eventual Consistency**: Maintains uniqueness across distributed systems
- **Event-Driven Validation**: Validates constraints through domain events
- **Atomic Operations**: Registers names and creates aggregates atomically
- **Conflict Resolution**: Handles concurrent access to same resources
- **Audit Trail**: Complete history of name registrations and releases
- **Scalability**: Partitioned by user for horizontal scaling

### Time Travel Debugging

Replay system state at any point in time for debugging.

```php
// Get aggregate state at specific date
$userYesterday = $this->eventStore->load($userId, new \DateTimeImmutable('yesterday'));

// Replay events from a specific point
$events = $this->eventStore->loadByDomainEvents(
    $userId, 
    [UserRegisteredDomainEvent_v1::class], 
    new \DateTimeImmutable('last week')
);
```

## 🔒 Security Features

### Encryption at Rest

Sensitive data is automatically encrypted using AES-256-GCM.

```php
// Mark sensitive fields
#[PersonalData]
public string $socialSecurityNumber;

// Automatic encryption/decryption
public function encrypt(string $data, string $userId): array
{
    $key = $this->keyManagement->getKey($userId);
    $iv = random_bytes(16);
    $encrypted = openssl_encrypt($data, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    
    return [
        'ciphertext' => base64_encode($encrypted),
        'iv' => base64_encode($iv),
        'tag' => base64_encode($tag),
    ];
}
```

### Key Management

Secure, user-specific key management for encryption.

```php
interface KeyManagementRepositoryInterface
{
    public function generateKey(string $userId): string;
    public function getKey(string $userId): ?string;
    public function deleteKey(string $userId): void;
}
```

## 📊 Performance Optimization

### Snapshot Configuration

```yaml
# Optimize for your use case
App\Libraries\FluxCapacitor\EventStore\Services\SnapshotService:
    arguments:
        $snapshotFrequency: 50    # Heavy write workloads
        # $snapshotFrequency: 10  # Heavy read workloads
```

### Database Indexing

```sql
-- Recommended indexes
CREATE INDEX idx_events_aggregate_id ON events (aggregate_id);
CREATE INDEX idx_events_aggregate_version ON events (aggregate_id, version);
CREATE INDEX idx_events_occurred_on ON events (occurred_on);
CREATE INDEX idx_snapshots_aggregate_version ON aggregate_snapshots (aggregate_id, version DESC);
```

## 🧪 Testing

### Unit Testing Aggregates

```php
public function testUserRegistration(): void
{
    $user = User::register('123', 'john@example.com', 'John Doe');
    
    $events = $user->raisedDomainEvents();
    $this->assertCount(1, $events);
    $this->assertInstanceOf(UserRegisteredDomainEvent_v1::class, $events[0]);
}
```

### Integration Testing

```php
public function testEventStoreIntegration(): void
{
    $user = User::register('123', 'john@example.com', 'John Doe');
    $this->eventStore->save($user);
    
    $loadedUser = $this->eventStore->load('123');
    $this->assertEquals('john@example.com', $loadedUser->getEmail());
}
```

## 🐛 Debugging & Monitoring

### Event Replay

```php
// Replay specific events for debugging
$events = $this->eventStore->loadByDomainEvents(
    $aggregateId,
    [UserRegisteredDomainEvent_v1::class, UserUpdatedDomainEvent_v1::class]
);

foreach ($events as $event) {
    echo "Event: " . get_class($event) . " at " . $event->occurredOn->format('Y-m-d H:i:s') . "\n";
}
```

### Performance Monitoring

```php
// Monitor snapshot efficiency
$snapshotService = $this->container->get(SnapshotService::class);
$shouldSnapshot = $snapshotService->shouldCreateSnapshot($aggregate);

if ($shouldSnapshot) {
    $snapshotService->saveSnapshot($aggregate);
}
```

## 🔄 Migration Guide

### From v1 to v2 Events

```php
private function upcastUserRegisteredV1ToV2(array $eventData): array
{
    $payload = json_decode($eventData['payload'], true);
    
    // Add new field with default value
    $payload['phoneNumber'] = $payload['phoneNumber'] ?? '';
    
    $eventData['payload'] = json_encode($payload);
    $eventData['event_version'] = 2;
    $eventData['event_name'] = 'UserRegisteredDomainEvent_v2';
    
    return $eventData;
}
```

## 🛡️ Best Practices

### 1. Event Design

- **Immutable**: Events should never change once created
- **Versioned**: Always version your events (`EventName_v1`)
- **Specific**: Events should represent business facts, not technical operations
- **Complete**: Include all necessary data for event processing

### 2. Aggregate Design

- **Small**: Keep aggregates focused on single business concepts
- **Consistent**: Maintain invariants within aggregate boundaries
- **Idempotent**: Operations should be safe to retry
- **Encapsulated**: Hide internal state, expose behavior

### 3. Error Handling

```php
use App\Libraries\FluxCapacitor\EventStore\Exceptions\EventsNotFoundForAggregateException;
use App\Libraries\FluxCapacitor\EventStore\Exceptions\PublishDomainEventsException;

try {
    $this->eventStore->save($aggregate);
} catch (EventsNotFoundForAggregateException $e) {
    // Handle when aggregate doesn't exist
    $this->logger->info('Aggregate not found', ['aggregateId' => $aggregateId]);
} catch (PublishDomainEventsException $e) {
    // Handle event publishing errors
    $this->logger->error('Failed to publish domain events', ['exception' => $e]);
    throw $e;
} catch (\Exception $e) {
    // Handle other errors
    $this->logger->error('Event store error', ['exception' => $e]);
    throw $e;
}
```

### 4. Concurrency Control

FluxCapacitor implements **optimistic concurrency control** using database constraints:

```php
// Database constraint ensures version uniqueness
#[ORM\UniqueConstraint(name: 'unique_stream_version', columns: ['stream_id', 'stream_version'])]
```

**How it works:**
- Each event has a `stream_version` that increments sequentially
- Database constraint prevents duplicate versions for the same stream
- Concurrent modifications will cause a database constraint violation
- Application must handle `\Doctrine\DBAL\Exception\UniqueConstraintViolationException`

```php
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

try {
    $aggregate = $this->eventStore->load($aggregateId);
    $aggregate->performOperation();
    $this->eventStore->save($aggregate);
} catch (UniqueConstraintViolationException $e) {
    // Handle concurrent modification
    $this->logger->warning('Concurrent modification detected', [
        'aggregateId' => $aggregateId,
        'exception' => $e->getMessage()
    ]);
    
    // Retry with exponential backoff
    $retryCount = 0;
    $maxRetries = 3;
    
    while ($retryCount < $maxRetries) {
        try {
            usleep(100000 * pow(2, $retryCount)); // Exponential backoff
            $aggregate = $this->eventStore->load($aggregateId);
            $aggregate->performOperation();
            $this->eventStore->save($aggregate);
            break;
        } catch (UniqueConstraintViolationException $e) {
            $retryCount++;
            if ($retryCount >= $maxRetries) {
                throw new \RuntimeException('Failed to save after multiple retries due to concurrent modifications');
            }
        }
    }
}
```

### 5. Monitoring

- Monitor event store performance
- Track snapshot creation frequency
- Alert on encryption/decryption failures
- Monitor aggregate reconstruction time
- Watch for version conflicts in high-concurrency scenarios

## 🔗 Integration Examples

### Symfony Messenger

```yaml
# config/packages/messenger.yaml
framework:
    messenger:
        transports:
            events: '%env(MESSENGER_TRANSPORT_DSN)%'
        
        routing:
            'App\*\Domain\Events\*': events
```

### Doctrine DBAL

```yaml
# config/packages/doctrine.yaml
doctrine:
    dbal:
        connections:
            default:
                url: '%env(DATABASE_URL)%'
                
            event_store:
                url: '%env(EVENT_STORE_DATABASE_URL)%'
```

## 📋 Database Schema

```sql
-- Events table
CREATE TABLE events (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    aggregate_id VARCHAR(255) NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    event_name VARCHAR(255) NOT NULL,
    event_version INT NOT NULL DEFAULT 1,
    payload JSON NOT NULL,
    occurred_on TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    request_id VARCHAR(255) NOT NULL,
    version INT NOT NULL,
    INDEX idx_aggregate_id (aggregate_id),
    INDEX idx_aggregate_version (aggregate_id, version),
    INDEX idx_occurred_on (occurred_on),
    UNIQUE KEY unique_aggregate_version (aggregate_id, version)
);

-- Snapshots table
CREATE TABLE aggregate_snapshots (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    aggregate_id VARCHAR(255) NOT NULL,
    aggregate_type VARCHAR(255) NOT NULL,
    version INT NOT NULL,
    data JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_aggregate_id (aggregate_id),
    INDEX idx_aggregate_version (aggregate_id, version DESC),
    UNIQUE KEY unique_aggregate_version (aggregate_id, version)
);

-- Encryption keys table
CREATE TABLE encryption_keys (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id VARCHAR(255) NOT NULL,
    encryption_key TEXT NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_id (user_id)
);
```

## 📚 Additional Resources

- [Event Sourcing Patterns](https://martinfowler.com/eaaDev/EventSourcing.html)
- [CQRS Documentation](https://docs.microsoft.com/en-us/azure/architecture/patterns/cqrs)
- [Domain-Driven Design](https://domainlanguage.com/ddd/)

## 🤝 Contributing

FluxCapacitor is an internal library designed for applications. For questions, please contact me.

## 📄 License

Proprietary - Internal use only.

---

**FluxCapacitor** - *Sweeter, bolder, better* ⚡