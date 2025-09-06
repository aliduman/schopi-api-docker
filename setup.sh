#!/bin/bash

echo "🚀 Development Environment Kurulum Başlıyor..."

# Gerekli dizinleri oluştur
mkdir -p src
mkdir -p php
mkdir -p nginx
mkdir -p mysql

# PHP konfigürasyon dosyası oluştur
cat > php/php.ini << 'EOF'
[PHP]
post_max_size = 100M
upload_max_filesize = 100M
max_execution_time = 300
memory_limit = 512M
display_errors = On
log_errors = On
error_log = /var/log/php_errors.log

[Date]
date.timezone = Europe/Istanbul

[Session]
session.save_handler = redis
session.save_path = "tcp://redis:6379"
EOF

# PHP-FPM konfigürasyon dosyası oluştur
cat > php/www.conf << 'EOF'
[www]
user = www-data
group = www-data
listen = 9000
listen.owner = www-data
listen.group = www-data
pm = dynamic
pm.max_children = 20
pm.start_servers = 3
pm.min_spare_servers = 2
pm.max_spare_servers = 4
pm.max_requests = 500
EOF

# Örnek PHP dosyası oluştur
cat > src/index.php << 'EOF'
<?php
phpinfo();

// Redis bağlantı testi
try {
    $redis = new Redis();
    $redis->connect('redis', 6379);
    echo "<h2>Redis Bağlantısı: ✅ Başarılı</h2>";
    $redis->close();
} catch (Exception $e) {
    echo "<h2>Redis Bağlantısı: ❌ Başarısız - " . $e->getMessage() . "</h2>";
}

// MySQL bağlantı testi
try {
    $pdo = new PDO('mysql:host=mysql;dbname=development', 'devuser', 'devpassword');
    echo "<h2>MySQL Bağlantısı: ✅ Başarılı</h2>";
} catch (PDOException $e) {
    echo "<h2>MySQL Bağlantısı: ❌ Başarısız - " . $e->getMessage() . "</h2>";
}

// Socket testi
if (extension_loaded('sockets')) {
    echo "<h2>Socket Extension: ✅ Yüklü</h2>";
} else {
    echo "<h2>Socket Extension: ❌ Yüklü Değil</h2>";
}
EOF

# MySQL başlangıç scripti oluştur
cat > mysql/init.sql << 'EOF'
-- Development veritabanı için örnek tablolar
CREATE DATABASE IF NOT EXISTS development;
USE development;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO users (name, email) VALUES 
('Test User', 'test@example.com'),
('Admin User', 'admin@example.com');
EOF

echo "📁 Proje yapısı oluşturuldu!"
echo ""
echo "🔧 Kurulum için şu komutları çalıştırın:"
echo "1. chmod +x setup.sh && ./setup.sh"
echo "2. docker-compose up -d --build"
echo ""
echo "🌐 Erişim adresleri:"
echo "• Web: http://localhost:8080"
echo "• PhpMyAdmin: http://localhost:8081"
echo "• MySQL: localhost:3306"
echo "• Memcache: localhost:11211"
echo ""
echo "✅ Kurulum tamamlandı!"