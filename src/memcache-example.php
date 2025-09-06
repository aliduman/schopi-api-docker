<?php
// Memcache kullanım örnekleri

class MemcacheManager 
{
    private $memcached;
    
    public function __construct()
    {
        $this->memcached = new Memcached();
        $this->memcached->addServer('memcache', 11211);
        
        // Memcached ayarları
        $this->memcached->setOption(Memcached::OPT_COMPRESSION, true);
        $this->memcached->setOption(Memcached::OPT_SERIALIZER, Memcached::SERIALIZER_JSON);
        $this->memcached->setOption(Memcached::OPT_PREFIX_KEY, 'app_');
    }
    
    public function set($key, $value, $expiration = 3600)
    {
        return $this->memcached->set($key, $value, $expiration);
    }
    
    public function get($key)
    {
        return $this->memcached->get($key);
    }
    
    public function delete($key)
    {
        return $this->memcached->delete($key);
    }
    
    public function flush()
    {
        return $this->memcached->flush();
    }
    
    public function getStats()
    {
        return $this->memcached->getStats();
    }
    
    public function increment($key, $offset = 1)
    {
        return $this->memcached->increment($key, $offset);
    }
    
    public function decrement($key, $offset = 1)
    {
        return $this->memcached->decrement($key, $offset);
    }
    
    // Session yönetimi örneği
    public function setSession($sessionId, $data, $expiration = 1800)
    {
        return $this->set("session_$sessionId", $data, $expiration);
    }
    
    public function getSession($sessionId)
    {
        return $this->get("session_$sessionId");
    }
    
    // Cache invalidation örneği
    public function invalidateTag($tag)
    {
        $keys = $this->get("tag_$tag") ?: [];
        foreach ($keys as $key) {
            $this->delete($key);
        }
        $this->delete("tag_$tag");
    }
    
    public function setWithTag($key, $value, $tag, $expiration = 3600)
    {
        // Veriyi kaydet
        $this->set($key, $value, $expiration);
        
        // Tag listesine ekle
        $tagKeys = $this->get("tag_$tag") ?: [];
        $tagKeys[] = $key;
        $this->set("tag_$tag", array_unique($tagKeys), $expiration);
    }
    
    public function __destruct()
    {
        if ($this->memcached) {
            $this->memcached->quit();
        }
    }
}

// Kullanım örnekleri
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $cache = new MemcacheManager();
    
    echo "<h2>Memcache Test Sayfası</h2>";
    
    // Basit cache işlemleri
    echo "<h3>Basit Cache İşlemleri</h3>";
    $cache->set('test_key', 'Merhaba Memcache!', 600);
    $value = $cache->get('test_key');
    echo "Cached Value: " . htmlspecialchars($value) . "<br>";
    
    // Sayaç işlemleri
    echo "<h3>Sayaç İşlemleri</h3>";
    $cache->set('counter', 0);
    $cache->increment('counter', 5);
    $counter = $cache->get('counter');
    echo "Counter Value: $counter<br>";
    
    // Array cache
    echo "<h3>Array Cache</h3>";
    $users = [
        ['id' => 1, 'name' => 'Ali', 'email' => 'ali@example.com'],
        ['id' => 2, 'name' => 'Ayşe', 'email' => 'ayse@example.com']
    ];
    $cache->set('users_list', $users, 300);
    $cachedUsers = $cache->get('users_list');
    echo "Cached Users: <pre>" . print_r($cachedUsers, true) . "</pre>";
    
    // Tag-based cache
    echo "<h3>Tag-based Cache</h3>";
    $cache->setWithTag('user_1', ['name' => 'Ali'], 'users', 600);
    $cache->setWithTag('user_2', ['name' => 'Ayşe'], 'users', 600);
    echo "User 1: <pre>" . print_r($cache->get('user_1'), true) . "</pre>";
    
    // Stats
    echo "<h3>Memcache İstatistikleri</h3>";
    $stats = $cache->getStats();
    foreach ($stats as $server => $data) {
        echo "<strong>Server: $server</strong><br>";
        echo "Version: " . ($data['version'] ?? 'N/A') . "<br>";
        echo "Current Items: " . ($data['curr_items'] ?? 'N/A') . "<br>";
        echo "Total Items: " . ($data['total_items'] ?? 'N/A') . "<br>";
        echo "Memory Used: " . number_format(($data['bytes'] ?? 0) / 1024 / 1024, 2) . " MB<br>";
        echo "Hit Rate: " . round((($data['get_hits'] ?? 0) / (($data['cmd_get'] ?? 1))) * 100, 2) . "%<br><br>";
    }
    
    echo '<br><a href="?action=clear">Cache Temizle</a>';
}

if (isset($_GET['action']) && $_GET['action'] === 'clear') {
    $cache = new MemcacheManager();
    $cache->flush();
    echo "Cache temizlendi! <a href='memcache-example.php'>Geri dön</a>";
}