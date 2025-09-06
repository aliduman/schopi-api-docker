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
            <p><strong>Database Host:</strong> <?= $_ENV['DB_HOST'] ?? 'localhost' ?></p>
            <p><strong>Database Name:</strong> <?= $_ENV['DB_NAME'] ?? 'schopi-api' ?></p>
        </div>
        
        <h3>🔗 Faydalı Linkler</h3>
        <ul>
            <li><a href="/health.php">Health Check</a></li>
            <li><a href="/db-test.php">Database Test</a></li>
            <li><a href="https://console.cloud.google.com">Google Cloud Console</a></li>
        </ul>
    </div>
</body>
</html>
