#!/bin/bash

echo "🐳 Initializing Docker environment..."

# Parse command line arguments
ENVIRONMENT=""
for arg in "$@"; do
    case $arg in
        --env=*)
            ENVIRONMENT="${arg#*=}"
            shift
            ;;
        dev|prod)
            ENVIRONMENT="$arg"
            shift
            ;;
        *)
            # Unknown argument
            ;;
    esac
done

# If no environment specified, ask user
if [ -z "$ENVIRONMENT" ]; then
    echo "Please select environment:"
    echo "1) Development (dev)"
    echo "2) Production (prod)"
    read -p "Enter choice (1-2): " choice
    case $choice in
        1) ENVIRONMENT="dev" ;;
        2) ENVIRONMENT="prod" ;;
        *) echo "Invalid choice. Using development as default."; ENVIRONMENT="dev" ;;
    esac
fi

# Validate environment
if [ "$ENVIRONMENT" != "dev" ] && [ "$ENVIRONMENT" != "prod" ]; then
    echo "Invalid environment: $ENVIRONMENT. Using development as default."
    ENVIRONMENT="dev"
fi

# Set compose file based on environment
if [ "$ENVIRONMENT" = "dev" ]; then
    COMPOSE_FILE="docker-compose.yml"
    WEB_PORT="8000"
    echo "🔧 Setting up DEVELOPMENT environment..."
else
    COMPOSE_FILE="docker-compose.prod.yml"
    WEB_PORT="80"
    echo "🔧 Setting up PRODUCTION environment..."
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "📄 Copying .env.SAMPLE to .env..."
    cp .env.SAMPLE .env
    echo "⚠️  Remember to configure .env before running!"
fi

# Check if DATABASE_URL is configured
if grep -q "DATABASE_URL=$" .env; then
    echo "🔧 Configuring DATABASE_URL for Docker..."
    sed -i 's/DATABASE_URL=.*/DATABASE_URL="mysql:\/\/user:password@db:3306\/pteroca?serverVersion=8.0"/' .env
fi

# Check if APP_SECRET is empty and generate one if needed
if grep -q "APP_SECRET=$" .env; then
    echo "🔐 Generating APP_SECRET..."
    # Generate a random 32-character hex string
    NEW_SECRET=$(openssl rand -hex 32)
    sed -i "s/APP_SECRET=.*/APP_SECRET=$NEW_SECRET/" .env
    echo "✅ APP_SECRET generated successfully"
fi

echo "🏗️  Building containers..."
docker-compose -f $COMPOSE_FILE build

echo "🚀 Starting environment..."
if [ "$ENVIRONMENT" = "dev" ]; then
    docker-compose -f $COMPOSE_FILE up -d db phpmyadmin
else
    docker-compose -f $COMPOSE_FILE up -d db
fi

echo "⏳ Waiting for database to be ready..."
sleep 10

echo "🌐 Starting web server (with automatic migrations)..."
docker-compose -f $COMPOSE_FILE up -d web

echo "🔧 Setting proper permissions..."
docker-compose -f "$COMPOSE_FILE" exec web chown -R www-data:www-data /app/var /app/public/uploads 2>/dev/null || true
docker-compose -f "$COMPOSE_FILE" exec web chmod -R 775 /app/var /app/public/uploads 2>/dev/null || true

echo "✅ Environment ready!"
echo "🌐 Web application: http://localhost:$WEB_PORT"
echo "🗄️  Database: localhost:3306"
echo "   - Database: pteroca"
echo "   - User: user"
echo "   - Password: password"
echo "🌍 Timezone: inherited from host"
echo ""
echo "📝 Usage:"
echo "   Stop: docker-compose -f $COMPOSE_FILE down"
echo "   Logs: docker-compose -f $COMPOSE_FILE logs -f"
echo "   Restart: docker-compose -f $COMPOSE_FILE restart"
echo ""
if [ "$ENVIRONMENT" = "dev" ]; then
    echo "🧪 PHPMyAdmin: http://localhost:8080"
    echo "   - Server: db"
    echo "   - Username: user"
    echo "   - Password: password"
else
    echo "🔒 PHPMyAdmin disabled for production security"
fi

echo ""
echo "🎯 Next steps to complete installation:"
echo "   Option 1: Web wizard installer at http://localhost:$WEB_PORT/first-configuration"
echo "   Option 2: CLI command: docker-compose -f $COMPOSE_FILE exec web php bin/console app:configure-system"
echo ""
echo "🎉 Installation complete! Visit the web wizard to finalize setup."
