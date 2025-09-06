<?php
	class CimriProductCrawlerAPI extends Api{

        public CimriProducts $CimriProducts;
        public $id; // QueryParam id
        public $lang_code; // QueryParam lang_code
        public $user_id;
        public Authentication $Authentication;
		function __construct(){
            /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
            //$this->Authentication = $this->model("Authentication");
            //if (!$this->Authentication->authenticateJWTToken()) {
            //    exit;
            //}
            // JWT içinden aldığımız kullanıcı id
            //$this->user_id = $this->Authentication->userData['id'];

            $this->CimriProducts = $this->model('CimriProducts');
		}
        /**
         * Create product
         */
        public function cimri_create_product(){

            $url = "https://www.cimri.com/market/gida?sort=specUnit-asc&page=9";

            $ch = curl_init();

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Tarayıcı taklidi (User-Agent)
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36');

            // Gerekirse tarayıcıdan gelen diğer header'ları da taklit edebilirsin:
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Connection: keep-alive',
                'Referer: https://www.cimri.com'
            ]);

            // Tarayıcı gibi davranmak için bazı güvenlik ayarlarını kaldır
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_ENCODING, ""); // gzip varsa decode et

            $response = curl_exec($ch);

            // Hata kontrolü
            if (curl_errno($ch)) {
                echo "cURL Hatası: " . curl_error($ch);
                exit;
            }

            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode != 200) {
                echo "Sayfa çekilemedi. HTTP Kodu: $httpCode";
                exit;
            }

            // Başarılıysa DOM ile işleme geçebilirsin
            libxml_use_internal_errors(true);
            $dom = new DOMDocument();
            $dom->loadHTML($response);
            libxml_clear_errors();

            $xpath = new DOMXPath($dom);

            // Örnek: Başlıkları çek
            // Ürün adlarını ve eski fiyatları çek
            $productNames = $xpath->query("//*[contains(@class, 'ProductCard_productName')]");
            // Fiyat ve birim bilgilerini çek
            $productFooter = $xpath->query("//*[contains(@class, 'ProductCard_footer__')]");
            $prices = [];
            $units = [];
            $unit_prices = [];
            foreach ($productFooter as $footer) {
                $priceNode = $footer->getElementsByTagName('span')->item(0); // İlk span tag'ini al
                $unitNode = $footer->getElementsByTagName('span')->item(1); // İkinci span tag'ini al
               if ($priceNode && $unitNode) {
                    $prices[] = trim($priceNode->textContent);
                    $unitData = explode('/', trim($unitNode->textContent));
                    $units[] = trim($unitData[0]); // Birim bilgisini al
                    $unit_prices[] = isset($unitData[1]) ? trim($unitData[1]) : ''; // Birim fiyatını al
                }
            }

            // Eşleşenleri yazdır
            foreach ($productNames as $index => $nameNode) {
                $name = trim($nameNode->textContent);

                $this->CimriProducts->created_product([
                    'name' => $name,
                    'image' => '',
                    'price' => $prices[$index],
                    'unit_price' => $unit_prices[$index],
                    'unit'  => $units[$index]
                ]);
            }
            return $this->json([]);
        }
        
	}

?>