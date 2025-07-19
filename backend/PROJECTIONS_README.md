# Event Sourcing Projections Management

Simple CLI commands to manage your event sourcing projections.

## Quick Start

```bash
# Check status of all projections
docker exec my_symfony_app php bin/console projections:status

# Reset and rebuild all projections
docker exec my_symfony_app php bin/console projections:replay --all --reset-first

# Initialize projections on new environment
docker exec my_symfony_app php bin/console projections:replay --all
```

## Commands

### `projections:status`
Shows the current state of all projections.

```bash
docker exec my_symfony_app php bin/console projections:status
```

### `projections:reset`
Clears projection data (truncates tables).

```bash
# Reset specific projection
docker exec my_symfony_app php bin/console projections:reset 'App\UserContext\ReadModels\Projections\UserProjection'

# Reset all projections
docker exec my_symfony_app php bin/console projections:reset --all --force
```

**Options:**
- `--all, -a`: Reset all projections
- `--force, -f`: Skip confirmation prompt

### `projections:replay`
Rebuilds projections from event store.

```bash
# Replay specific projection
docker exec my_symfony_app php bin/console projections:replay 'App\UserContext\ReadModels\Projections\UserProjection'

# Replay all projections
docker exec my_symfony_app php bin/console projections:replay --all

# Reset before replay
docker exec my_symfony_app php bin/console projections:replay --all --reset-first

# Replay from specific date
docker exec my_symfony_app php bin/console projections:replay --all --from-date="2024-01-01"
```

**Options:**
- `--all, -a`: Replay all projections
- `--reset-first, -r`: Reset tables before replay
- `--from-date`: Replay events from specific date (Y-m-d H:i:s)

## Common Scenarios

### Fix a broken projection
```bash
# 1. Check what's wrong
docker exec my_symfony_app php bin/console projections:status

# 2. Fix the specific projection
docker exec my_symfony_app php bin/console projections:reset 'App\UserContext\ReadModels\Projections\UserProjection' --force
docker exec my_symfony_app php bin/console projections:replay 'App\UserContext\ReadModels\Projections\UserProjection'

# 3. Verify it's fixed
docker exec my_symfony_app php bin/console projections:status
```

### Setup new environment
```bash
docker exec my_symfony_app php bin/console projections:replay --all
```

### Development reset
```bash
docker exec my_symfony_app php bin/console projections:replay --all --reset-first
```

## Troubleshooting

**"Projection service not found"**
```bash
docker exec my_symfony_app php bin/console cache:clear
```

**"Event class not found"**
- Check if event classes exist in Domain/Events folders
- Verify event naming includes version suffix (e.g., `UserSignedUpDomainEvent_v1`)

**"Table does not exist"**
- Run database migrations first