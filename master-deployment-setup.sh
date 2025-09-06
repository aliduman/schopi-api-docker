#!/bin/bash

echo "🚀 MASTER DEPLOYMENT SETUP"
echo "=========================="
echo "Docker Container'ları Google Cloud'a CI/CD ile Deploy Etme"
echo ""

# Renkli output için
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

print_step() {
    echo -e "${BLUE}[$1/10]${NC} $2"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

# Gereksinimler kontrolü
check_requirements() {
    print_step 1 "Gereksinimleri kontrol ediliyor..."
    
    commands=("docker" "gcloud" "git" "curl")
    missing=()
    
    for cmd in "${commands[@]}"; do
        if ! command -v $cmd &> /dev/null; then
            missing+=($cmd)
        fi
    done
    
    if [ ${#missing[@]} -ne 0 ]; then
        print_error "Eksik komutlar: ${missing[*]}"
        echo ""
        echo "Kurulum:"
        echo "• Docker: https://docs.docker.com/get-docker/"
        echo "• gcloud: https://cloud.google.com/sdk/docs/install"
        echo "• git: sudo apt-get install git"
        exit 1
    fi
    
    print_success "Tüm gereksinimler mevcut"
}

# Proje bilgilerini al
get_project_info() {
    print_step 2 "Proje bilgileri alınıyor..."
    
    echo "Lütfen aşağıdaki bilgileri girin:"
    echo ""
    
    read -p "🏗️  GCP Project ID: " PROJECT_ID
    read -p "📱 App Name (küçük harf, tire ile): " APP_NAME
    read -p "🌍 Region (örn: europe-west1): " REGION
    read -p "🌐 Domain (opsiyonel, örn: myapp.com): " DOMAIN
    read -p "📧 GitHub Repository (örn: username/repo): " GITHUB_REPO
    
    echo ""
    echo "Deployment stratejisi seçin:"
    echo "1. 🌩️  Cloud Run (Serverless - Önerilen)"
    echo "2. ☸️  Google Kubernetes Engine (GKE)"
    echo "3. 🖥️  Compute Engine (VM)"
    echo "4. 🏭 Full Production (Cloud Run + Cloud SQL + WAF)"
    echo ""
    read -p "Seçiminiz (1-4): " DEPLOYMENT_TYPE
    
    # Validasyon
    if [[ ! "$APP_NAME" =~ ^[a-z0-9-]+$ ]]; then
        print_error "App name sadece küçük harf, rakam ve tire içerebilir"
        exit 1
    fi
    
    export PROJECT_ID APP_NAME REGION DOMAIN GITHUB_REPO DEPLOYMENT_TYPE
    
    print_success "Proje bilgileri alındı"
}

# GCP kurulumu
setup_gcp() {
    print_step 3 "GCP kurulumu yapılıyor..."
    
    # GCP'ye login
    echo "GCP'ye login olunuyor..."
    gcloud auth login
    
    # Project set
    gcloud config set project $PROJECT_ID
    
    # API'leri enable et
    echo "Gerekli API'ler aktifleştiriliyor..."
    gcloud services enable cloudbuild.googleapis.com
    gcloud services enable run.googleapis.com
    gcloud services enable containerregistry.googleapis.com
    gcloud services enable artifactregistry.googleapis.com
    
    if [ "$DEPLOYMENT_TYPE" = "4" ]; then
        gcloud services enable sql-component.googleapis.com
        gcloud services enable secretmanager.googleapis.com
        gcloud services enable cloudarmor.googleapis.com
        gcloud services enable monitoring.googleapis.com
    fi
    
    print_success "GCP kurulumu tamamlandı"
}

# Dosya yapısını oluştur
create_project_structure() {
    print_step 4 "Proje yapısı oluşturuluyor..."
    
    mkdir -p {src,nginx,php,mysql,supervisor,.github/workflows,k8s,terraform}
    
    # Basic nginx config
    cat > nginx/default.conf << 'EOF'
server {
    listen 8080;
    server_name _;
    root /var/www/html;
    index index.php index.html;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
EOF

    # Basic PHP config
    cat > php/php.ini << 'EOF'
[PHP]
memory_limit = 256M
max_execution_time = 30
post_max_size = 10M
upload_max_filesize = 10M
display_errors = Off
log_errors = On
error_log = /var/log/php_errors.log
date.timezone = Europe/Istanbul
EOF

    # Health check endpoint
    cat > src/health.php << 'EOF'
<?php
header('Content-Type: application/json');
http_response_code(200);
echo json_encode([
    'status' => 'healthy',
    'timestamp' => date('c'),
    'version' => '1.0.0'
]);
EOF

    # Basic index.php
    cat > src/index.php << 'EOF'
<!DOCTYPE html>
<html>
<head>
    <title>My App - Deployed to Google Cloud</title>
    <style>
        body { font-family: Arial; margin: 40px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; }
        .success { color: #28a745; }
        .info { background: #e3f2fd; padding: 15px; border-radius: 5px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="success">🚀 Başarılı Deployment!</h1>
        <p>Uygulamanız Google Cloud'da çalışıyor.</p>
        
        <div class="info">
            <h3>Sistem Bilgileri</h3>
            <p><strong>PHP Version:</strong> <?= PHP_VERSION ?></p>
            <p><strong>Server Time:</strong> <?= date('Y-m-d H:i:s') ?></p>
            <p><strong>Environment:</strong> <?= $_ENV['APP_ENV'] ?? 'production' ?></p>
        </div>
        
        <h3>🔗 Faydalı Linkler</h3>
        <ul>
            <li><a href="/health.php">Health Check</a></li>
            <li><a href="https://console.cloud.google.com">Google Cloud Console</a></li>
        </ul>
    </div>
</body>
</html>
EOF
    
    print_success "Proje yapısı oluşturuldu"
}

# Dockerfile oluştur
create_dockerfile() {
    print_step 5 "Dockerfile oluşturuluyor..."
    
    case $DEPLOYMENT_TYPE in
        1|4) # Cloud Run
            cat > Dockerfile << 'EOF'
FROM php:8.2-fpm-alpine

# Install dependencies
RUN apk add --no-cache nginx supervisor curl

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql

# Copy configurations
COPY nginx/default.conf /etc/nginx/http.d/default.conf
COPY php/php.ini /usr/local/etc/php/php.ini

# Supervisor config
RUN echo '[supervisord]' > /etc/supervisor/conf.d/supervisord.conf && \
    echo 'nodaemon=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:nginx]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=nginx -g "daemon off;"' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:php-fpm]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=php-fpm -F' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf

# Copy application
WORKDIR /var/www/html
COPY src/ /var/www/html/

# Set permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 8080

CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
EOF
            ;;
        2) # GKE
            cat > Dockerfile << 'EOF'
FROM php:8.2-apache

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql

# Copy application
COPY src/ /var/www/html/

# Apache config
RUN a2enmod rewrite
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

EXPOSE 80
EOF
            ;;
        3) # Compute Engine
            cat > docker-compose.yml << 'EOF'
version: '3.8'
services:
  app:
    build: .
    ports:
      - "80:8080"
    environment:
      - APP_ENV=production
    restart: unless-stopped
EOF
            ;;
    esac
    
    print_success "Dockerfile oluşturuldu"
}

# CI/CD pipeline oluştur
create_cicd() {
    print_step 6 "CI/CD pipeline oluşturuluyor..."
    
    cat > .github/workflows/deploy.yml << EOF
name: Deploy to Google Cloud

on:
  push:
    branches: [ main ]

env:
  PROJECT_ID: $PROJECT_ID
  SERVICE: $APP_NAME
  REGION: $REGION

jobs:
  deploy:
    runs-on: ubuntu-latest
    
    steps:
    - name: Checkout
      uses: actions/checkout@v3

    - name: Google Auth
      uses: 'google-github-actions/auth@v1'
      with:
        credentials_json: '\${{ secrets.GCP_SA_KEY }}'

    - name: Configure Docker
      run: gcloud auth configure-docker

    - name: Build and Push
      run: |
        docker build -t "gcr.io/\$PROJECT_ID/\$SERVICE:latest" .
        docker push "gcr.io/\$PROJECT_ID/\$SERVICE:latest"

    - name: Deploy to Cloud Run
      if: "$DEPLOYMENT_TYPE" == "1" || "$DEPLOYMENT_TYPE" == "4"
      uses: google-github-actions/deploy-cloudrun@v1
      with:
        service: \${{ env.SERVICE }}
        region: \${{ env.REGION }}
        image: gcr.io/\${{ env.PROJECT_ID }}/\${{ env.SERVICE }}:latest
        flags: '--port=8080 --memory=512Mi --cpu=1'

    - name: Deploy to GKE
      if: "$DEPLOYMENT_TYPE" == "2"
      run: |
        gcloud container clusters get-credentials \$SERVICE-cluster --region=\$REGION
        kubectl set image deployment/\$SERVICE \$SERVICE=gcr.io/\$PROJECT_ID/\$SERVICE:latest
EOF

    print_success "CI/CD pipeline oluşturuldu"
}

# Service Account oluştur
create_service_account() {
    print_step 7 "Service Account oluşturuluyor..."
    
    SA_NAME="${APP_NAME}-ci-cd"
    SA_EMAIL="${SA_NAME}@${PROJECT_ID}.iam.gserviceaccount.com"
    
    # Service account oluştur
    gcloud iam service-accounts create $SA_NAME \
        --display-name="CI/CD Service Account for $APP_NAME"
    
    # Gerekli roller ver
    gcloud projects add-iam-policy-binding $PROJECT_ID \
        --member="serviceAccount:$SA_EMAIL" \
        --role="roles/run.admin"
    
    gcloud projects add-iam-policy-binding $PROJECT_ID \
        --member="serviceAccount:$SA_EMAIL" \
        --role="roles/storage.admin"
    
    gcloud projects add-iam-policy-binding $PROJECT_ID \
        --member="serviceAccount:$SA_EMAIL" \
        --role="roles/iam.serviceAccountUser"
    
    # Key oluştur
    gcloud iam service-accounts keys create key.json \
        --iam-account=$SA_EMAIL
    
    print_success "Service Account oluşturuldu: $SA_EMAIL"
    print_warning "key.json dosyasını GitHub Secrets'a GCP_SA_KEY olarak ekleyin"
}

# Build ve deploy
build_and_deploy() {
    print_step 8 "Build ve deployment..."
    
    # Image build
    echo "Docker image build ediliyor..."
    docker build -t "gcr.io/$PROJECT_ID/$APP_NAME:latest" .
    
    # Image push
    echo "Image push ediliyor..."
    docker push "gcr.io/$PROJECT_ID/$APP_NAME:latest"
    
    # Deploy
    case $DEPLOYMENT_TYPE in
        1|4) # Cloud Run
            echo "Cloud Run'a deploy ediliyor..."
            gcloud run deploy $APP_NAME \
                --image "gcr.io/$PROJECT_ID/$APP_NAME:latest" \
                --platform managed \
                --region $REGION \
                --allow-unauthenticated \
                --port 8080
            ;;
        2) # GKE
            echo "GKE cluster oluşturuluyor..."
            gcloud container clusters create $APP_NAME-cluster \
                --region $REGION \
                --num-nodes 2
            
            echo "GKE'ye deploy ediliyor..."
            kubectl create deployment $APP_NAME --image="gcr.io/$PROJECT_ID/$APP_NAME:latest"
            kubectl expose deployment $APP_NAME --type=LoadBalancer --port=80 --target-port=80
            ;;
        3) # Compute Engine
            echo "Compute Engine instance oluşturuluyor..."
            gcloud compute instances create $APP_NAME-vm \
                --zone="${REGION}-a" \
                --machine-type=e2-micro \
                --image-family=docker-optimized \
                --image-project=cos-cloud \
                --metadata=startup-script="docker run -d -p 80:8080 gcr.io/$PROJECT_ID/$APP_NAME:latest"
            ;;
    esac
    
    print_success "Deployment tamamlandı"
}

# Production setup (sadece tip 4 için)
setup_production() {
    if [ "$DEPLOYMENT_TYPE" = "4" ]; then
        print_step 9 "Production altyapısı kuruluyor..."
        
        # Cloud SQL
        echo "Cloud SQL instance oluşturuluyor..."
        gcloud sql instances create $APP_NAME-mysql \
            --database-version=MYSQL_8_0 \
            --tier=db-f1-micro \
            --region=$REGION
        
        # Database
        gcloud sql databases create $APP_NAME --instance=$APP_NAME-mysql
        
        # User
        DB_PASSWORD=$(openssl rand -base64 16)
        gcloud sql users create appuser \
            --instance=$APP_NAME-mysql \
            --password=$DB_PASSWORD
        
        # Secret
        echo $DB_PASSWORD | gcloud secrets create $APP_NAME-db-password --data-file=-
        
        print_success "Production altyapısı kuruldu"
    else
        print_step 9 "Production setup atlandı (sadece tip 4 için gerekli)"
    fi
}

# URL'leri göster
show_urls() {
    print_step 10 "Deployment bilgileri gösteriliyor..."
    
    echo ""
    echo "🎉 DEPLOYMENT BAŞARILI!"
    echo "======================"
    
    case $DEPLOYMENT_TYPE in
        1|4) # Cloud Run
            URL=$(gcloud run services describe $APP_NAME --region=$REGION --format='value(status.url)')
            echo "🌐 App URL: $URL"
            echo "❤️  Health Check: $URL/health.php"
            ;;
        2) # GKE
            IP=$(kubectl get service $APP_NAME -o jsonpath='{.status.loadBalancer.ingress[0].ip}')
            if [ -n "$IP" ]; then
                echo "🌐 App URL: http://$IP"
                echo "❤️  Health Check: http://$IP/health.php"
            else
                echo "⏳ External IP henüz hazırlanıyor. Kontrol edin: kubectl get service $APP_NAME"
            fi
            ;;
        3) # Compute Engine
            IP=$(gcloud compute instances describe $APP_NAME-vm --zone="${REGION}-a" --format='value(networkInterfaces[0].accessConfigs[0].natIP)')
            echo "🌐 App URL: http://$IP"
            echo "❤️  Health Check: http://$IP/health.php"
            ;;
    esac
    
    if [ -n "$DOMAIN" ]; then
        echo "🌍 Custom Domain: https://$DOMAIN (DNS ayarları gerekli)"
    fi
    
    echo ""
    echo "📋 GitHub Repository Setup:"
    echo "1. Bu klasörü GitHub'a push edin:"
    echo "   git init"
    echo "   git add ."
    echo "   git commit -m 'Initial deployment setup'"
    echo "   git branch -M main"
    echo "   git remote add origin https://github.com/$GITHUB_REPO.git"
    echo "   git push -u origin main"
    echo ""
    echo "2. GitHub Secrets ekleyin:"
    echo "   - GCP_SA_KEY: $(pwd)/key.json dosyasının içeriği"
    echo ""
    echo "📊 Monitoring:"
    echo "   • Google Cloud Console: https://console.cloud.google.com/run/detail/$REGION/$APP_NAME"
    echo "   • Logs: gcloud logs tail run.googleapis.com/requests --follow"
    echo ""
    print_success "Tüm kurulum tamamlandı!"
}

# Ana script
main() {
    echo "Bu script Docker containerlarınızı Google Cloud'a deploy eder ve CI/CD kurar."
    echo ""
    read -p "Devam etmek istiyor musunuz? (y/N): " confirm
    
    if [[ ! "$confirm" =~ ^[Yy]$ ]]; then
        echo "İptal edildi."
        exit 0
    fi
    
    check_requirements
    get_project_info
    setup_gcp
    create_project_structure
    create_dockerfile
    create_cicd
    create_service_account
    build_and_deploy
    setup_production
    show_urls
}

# Script'i çalıştır
main "$@"