<?php
phpinfo();

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
