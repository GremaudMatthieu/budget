# Docker commands
up:
	docker compose up --build -d

up-no-build:
	docker compose up

down:
	docker compose down

logs:
	docker compose logs -f

# Node.js commands
install:
	docker compose run --rm websocket npm install

start:
	docker compose run --rm websocket npm start

test:
	docker compose run --rm websocket npm test

# Utility commands
shell:
	docker compose run --rm websocket /bin/sh