.PHONY: init install install-deps start mobile-start start-detached build build-android build-ios logs clean help

# Initialize app (first time setup)
init:
	docker compose run --rm mobile-app npm install
	docker compose run --rm mobile-app npm install expo expo-router react-native-web react-native-screens react-native-safe-area-context expo-linking expo-constants expo-status-bar axios socket.io-client @react-native-async-storage/async-storage expo-secure-store @expo/vector-icons

# Install additional dependencies
install-deps:
	docker compose run --rm mobile-app npm install $(deps)

# Install dependencies
install:
	docker compose run --rm mobile-app npm install

# Standard start (may not work properly with mobile devices)
start:
	docker compose up

# Start for mobile development (uses host IP for QR code)
mobile-start:
	./scripts/start-with-host-ip.sh

# Start development server in detached mode
start-detached:
	docker compose up -d

# Build for web
build:
	docker compose run --rm mobile-app npx expo export:web

# Build for Android
build-android:
	docker compose run --rm mobile-app eas build -p android

# Build for iOS
build-ios:
	docker compose run --rm mobile-app eas build -p ios

# View logs
logs:
	docker compose logs -f

# Stop and remove containers
stop:
	docker compose down

# Clean up node_modules and reinstall
clean:
	docker compose down
	docker compose run --rm mobile-app rm -rf node_modules
	docker compose run --rm mobile-app npm install

# Help
help:
	@echo "Usage: make [command]"
	@echo "Commands:"
	@echo "  init           Initialize the app and install all dependencies"
	@echo "  install        Install dependencies from package.json"
	@echo "  install-deps   Install additional dependencies (use deps=package-name)"
	@echo "  start          Start the development server"
	@echo "  mobile-start   Start the server with proper host IP for mobile development"
	@echo "  start-detached Start the development server in detached mode"
	@echo "  build          Build for web"
	@echo "  build-android  Build for Android"
	@echo "  build-ios      Build for iOS"
	@echo "  logs           View logs"
	@echo "  stop           Stop and remove containers"
	@echo "  clean          Clean up node_modules and reinstall"
	@echo "  help           Show this help message"