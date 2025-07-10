#!/bin/bash

echo "🐳 Initializing Docker environment..."

# Check if Docker is installed
if ! command -v docker &> /dev/null; then
    echo "❌ Error: Docker is not installed!"
    echo ""
    echo "Please install Docker first:"
    echo "  - Ubuntu/Debian: https://docs.docker.com/engine/install/ubuntu/"
    echo "  - CentOS/RHEL: https://docs.docker.com/engine/install/centos/"
    echo "  - Windows: https://docs.docker.com/desktop/install/windows-install/"
    echo "  - macOS: https://docs.docker.com/desktop/install/mac-install/"
    echo ""
    exit 1
fi

# Check if Docker Compose is installed
if ! command -v docker-compose &> /dev/null; then
    echo "❌ Error: Docker Compose is not installed!"
    echo ""
    echo "Please install Docker Compose first:"
    echo "  - Linux: https://docs.docker.com/compose/install/"
    echo "  - Windows/macOS: Usually included with Docker Desktop"
    echo ""
    exit 1
fi

# Check if Docker daemon is running
if ! docker info &> /dev/null; then
    echo "❌ Error: Docker daemon is not running!"
    echo ""
    echo "Please start Docker daemon first:"
    echo "  - Linux: sudo systemctl start docker"
    echo "  - Windows/macOS: Start Docker Desktop"
    echo ""
    exit 1
fi

echo "✅ Docker and Docker Compose are installed and running"

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
    DB_PASSWORD="password"
    DB_ROOT_PASSWORD="root"
    echo "🔧 Setting up DEVELOPMENT environment..."
else
    COMPOSE_FILE="docker-compose.prod.yml"
    WEB_PORT="80"
    # Generate secure passwords for production
    DB_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    DB_ROOT_PASSWORD=$(openssl rand -base64 32 | tr -d "=+/" | cut -c1-25)
    echo "🔧 Setting up PRODUCTION environment..."
    echo "🔐 Generated secure database passwords for production"
fi

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "📄 Copying .env.SAMPLE to .env..."
    cp .env.SAMPLE .env
    echo "⚠️  Remember to configure .env before running!"
fi

# Check if DATABASE_URL is configured and update with generated password
if grep -q "DATABASE_URL=$" .env || grep -q "DATABASE_URL=.*password.*" .env; then
    echo "🔧 Configuring DATABASE_URL for Docker with generated password..."
    sed -i "s/DATABASE_URL=.*/DATABASE_URL=\"mysql:\/\/user:$DB_PASSWORD@db:3306\/pteroca?serverVersion=8.0\"/" .env
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
    # Set environment variables for production
    export MYSQL_PASSWORD=$DB_PASSWORD
    export MYSQL_ROOT_PASSWORD=$DB_ROOT_PASSWORD
    export DATABASE_URL="mysql://user:$DB_PASSWORD@db:3306/pteroca?serverVersion=8.0"
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
echo "   - Password: $DB_PASSWORD"
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
    echo "   - Password: $DB_PASSWORD"
else
    echo "🔒 PHPMyAdmin disabled for production security"
    echo ""
    echo "⚠️  IMPORTANT: Save these database passwords securely!"
    echo "🔐 Database User Password: $DB_PASSWORD"
    echo "🔐 Database Root Password: $DB_ROOT_PASSWORD"
fi

echo ""
echo "⏰ Cron job status:"
echo "   ✅ PteroCA cron job automatically configured"
echo "   📋 Schedule: Every minute (billing, suspensions, etc.)"
echo "   🔄 Command: php /app/bin/console app:cron-job-schedule"
echo ""
echo "🎯 Next steps to complete installation:"
echo "   Option 1: Web wizard installer at http://localhost:$WEB_PORT/first-configuration"
echo "   Option 2: CLI command: docker-compose -f $COMPOSE_FILE exec web php bin/console app:configure-system"
echo ""
echo "🎉 Installation complete! Visit the web wizard to finalize setup."
