# Budget Mobile App (React Native with Expo)

A React Native mobile application for budget management, built using Expo and Expo Router. This app works on both web and mobile platforms and connects to the PHP backend and WebSocket server for real-time updates.

## Features

- Cross-platform (iOS, Android, Web)
- Real-time updates via WebSocket
- Authentication and user management
- Budget envelope management
- Responsive design

## Prerequisites

- Node.js (v16+)
- Docker and Docker Compose
- Expo CLI (optional for local development)

## Getting Started

### Setup Environment Variables

1. Copy the example environment file:
   ```
   cp .env.example .env
   ```

2. Update the variables in `.env` to match your environment:
   ```
   EXPO_PUBLIC_API_URL=http://host.docker.internal:8000/api
   EXPO_PUBLIC_WS_URL=http://host.docker.internal:3001
   ```

### Using Docker (Recommended)

All commands can be run using the provided Makefile:

1. Initialize the app (first time setup):
   ```
   make init
   ```

2. Start the development server:
   ```
   make start
   ```

3. View logs:
   ```
   make logs
   ```

4. Stop the container:
   ```
   make stop
   ```

### Available Make Commands

- `make init` - Initialize the app (first time setup)
- `make install` - Install dependencies
- `make start` - Start the development server
- `make start-detached` - Start the server in detached mode
- `make build` - Build for web
- `make build-android` - Build for Android
- `make build-ios` - Build for iOS
- `make logs` - View logs
- `make stop` - Stop and remove containers
- `make clean` - Clean up node_modules and reinstall

## Accessing the App

- **Web**: Open http://localhost:19000 in your browser
- **iOS/Android**: Scan the QR code using the Expo Go app

## Connecting to Backend Services

This app is configured to connect to:
- Backend API: http://host.docker.internal:8000/api
- WebSocket Server: http://host.docker.internal:3001

Make sure both services are running for full functionality.

## Folder Structure

```
frontendv2/
├── app/                 # Main app screens and routing
├── assets/              # Images, fonts and static resources
├── components/          # Reusable UI components
├── contexts/            # React contexts (auth, socket, etc.)
├── hooks/               # Custom React hooks
├── services/            # API services and utilities
├── docker-compose.yml   # Docker Compose configuration
├── Dockerfile           # Docker image definition
├── Makefile             # Task automation
└── package.json         # Node.js dependencies
```

## Development Workflow

1. **Backend Connection**: Ensure the PHP backend and WebSocket servers are running
2. **Start the App**: Run `make start` to start the development server
3. **Web Development**: Open http://localhost:19000 in your browser
4. **Mobile Development**: Scan the QR code with Expo Go app