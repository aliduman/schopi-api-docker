<?php
/**
 * List Products Class
 * - middleware
 */

class ListProducts extends Model
{
    public $id;
    public $list_id;

    public function __construct()
    {
        $this->table_name = "list_products";
        $this->table_columns = [
            'id',
            'image',
            'name',
            'unit',
            'price',
            'category_id',
            'product_id',
            'list_id',
            'customProductId',
            'core_list_id',
            'user_id',
            'server_id',
            'counter',
            'isChecked',
            'isTick',
            'isSynced',
            'first_letter',
            'category_image',
            'category_name',
            'checkedCount',
            'created_date',
            'updated_date',
            'is_packet',
        ];
    }

    // Check list bu kullanıcıya ait mi?
    public function check_user_list($list_id, $user_id)
    {
        $this->query("SELECT * FROM lists WHERE id = :list_id AND user_id = :user_id");
        $this->bind(":list_id", $list_id);
        $this->bind(":user_id", $user_id);
        $list = $this->resultSingle();

        // Eğer liste bu kullanıcıya aitse true döndür
        if ($list) {
            return true;
        }

        return false;
    }

    // Liste bu kullnıcı ile paylaşılmış mı? Neye göre user_id = inviter_id and status = accepted and list_id = :list_id
    public function shared_check_user_list($list_id, $user_id)
    {
        $this->query("SELECT * FROM share_list WHERE inviter_id = :user_id AND status = 'accepted' AND list_id = :list_id");
        $this->bind(":user_id", $user_id);
        $this->bind(":list_id", $list_id);
        $sharedList = $this->resultSingle();

        // Eğer liste paylaşılmışsa true döndür
        if ($sharedList) {
            return true;
        }

        return false;
    }

    public function get_list_products($list_id) {
        $this->query("SELECT * FROM list_products WHERE list_products.list_id = :list_id ORDER BY id DESC");
        $this->bind(":list_id", $list_id);
        $list = $this->resultSet();

        // Eğer liste boşsa, boş bir dizi döndür
        if (empty($list)) {
            return [];
        }

        // Listeyi dizi olarak döndür
        return (array)$list;
    }


    // Get All list
    public function get_all_list_products()
    {
        return $this->getAll();
    }

    public function add_list_product($data, $minusOrPlus = null) {
        // Fix the conditional logic in add_list_product method
        if (!empty($data['product_id'])){
            $this->query("SELECT id, counter FROM list_products WHERE list_id = :list_id AND product_id = :product_id ORDER BY id DESC");
            $this->bind(":product_id", $data['product_id']);
            $this->bind(":list_id", $data['list_id']);
        }
        else if (!empty($data['customProductId'])){
            $this->query("SELECT id, counter FROM list_products WHERE list_id = :list_id AND customProductId = :customProductId ORDER BY id DESC");
            $this->bind(":customProductId", $data['customProductId']);
            $this->bind(":list_id", $data['list_id']);
        }

        $existingProduct = $this->resultSingle();
        if ($existingProduct) {
            if (!empty($data['counter']) && is_numeric($data['counter']) && $minusOrPlus === 'minus' && $existingProduct->counter >= 0.5) {
                $existingProduct->counter -= 0.5; // Counter'ı 0.5 azalt
            } else if (!empty($data['counter']) && is_numeric($data['counter']) && $minusOrPlus === 'plus' && $existingProduct->counter >= 0.5) {
                $existingProduct->counter += 0.5; // Counter'ı 0.5 artır
            }
            // Eğer ürün zaten varsa, sadece counter'ı güncelle
            $updateData = [
                'counter' => $existingProduct->counter,
                'isChecked' => $data['isChecked'] ?? 0,
                'isTick' => $data['isTick'] ?? 0,
                'isSynced' => $data['isSynced'] ?? 0,
                'updated_date' => date('Y-m-d H:i:s')
            ];
            $updateWhere = ['id' => $existingProduct->id];
            return $this->update($updateData, $updateWhere);
        }

        // Eğer $data['category_name'] yoksa product ve category tablosunu ilişkilendir ve category_name'i ekle
        if (empty($data['category_name']) && !empty($data['product_id'])) {
            $this->query("SELECT c.id as 'category_id', c.name as 'category_name', c.image as 'category_image' FROM products JOIN categories c on c.id = products.category_id WHERE products.id = :product_id");
            $this->bind(":product_id", $data['product_id']);
            $category = $this->resultSingle();
            if ($category) {
                $data['category_name'] = $category->category_name;
                $data['category_id'] = $category->category_id;
                $data['category_image'] = $category->category_image;
            } else {
                throw new Exception("Category not found for category_id: " . $data['category_id']);
            }
        }

        $create_add_list_product = $this->insert($data);
        $newProductListID = $create_add_list_product->id;

        if ($newProductListID){
            $changeUpdateData = [
                'server_id' => $newProductListID,
                'updated_date' => date('Y-m-d H:i:s')
            ];

            $updateWhere = ['id' => $newProductListID];
            return (array)$this->update($changeUpdateData, $updateWhere);
        }

        return json_encode($create_add_list_product);

    }

    public function add_list_product_mobile($data)
    {
        // Eğer list_id verilmemişse veya boşsa, veritabanından list_id'yi al
        if (empty($data['list_id'])) {
            $this->query("SELECT id, server_id FROM lists WHERE user_id = :user_id AND core_list_id = :core_list_id ORDER BY id DESC");
            $this->bind(":user_id", $data['user_id']);
            $this->bind(":core_list_id", $data['core_list_id']);
            $list = $this->resultSingle();

            if (!$list) {
                throw new Exception("List not found for user_id: " . $data['user_id'] . " and core_list_id: " . $data['core_list_id']);
            }

            $data['list_id'] = $list->id;
        }

        // name, unit, price alanlarını data'dan çıkar çünkü bunlar list_products tablosunda yok
        // Bu bilgiler products tablosundan JOIN ile alınacak
        //unset($data['name'], $data['unit'], $data['price']);

        // Mevcut ürün kontrolü
        $existingProduct = false;
        if (!empty($data['product_id'])){
            $this->query("SELECT id, counter FROM list_products WHERE list_id = :list_id AND product_id = :product_id ORDER BY id DESC");
            $this->bind(":product_id", $data['product_id']);
            $this->bind(":list_id", $data['list_id']);
            $existingProduct = $this->resultSingle();
        }
        else if (!empty($data['customProductId'])){
            $this->query("SELECT id, counter FROM list_products WHERE list_id = :list_id AND customProductId = :customProductId ORDER BY id DESC");
            $this->bind(":customProductId", $data['customProductId']);
            $this->bind(":list_id", $data['list_id']);
            $existingProduct = $this->resultSingle();
        }

        if ($existingProduct) {
            // Eğer ürün zaten varsa, sadece counter'ı güncelle
            $updateData = [
                'counter' => $data['counter'],
                'isChecked' => $data['isChecked'] ?? 0,
                'isTick' => $data['isTick'] ?? 0,
                'isSynced' => $data['isSynced'] ?? 0,
                'updated_date' => date('Y-m-d H:i:s')
            ];
            $updateWhere = ['id' => $existingProduct->id];
            return $this->update($updateData, $updateWhere);
        }

        // Eğer $data['category_name'] yoksa product ve category tablosunu ilişkilendir
        if (empty($data['category_name']) && !empty($data['product_id'])) {
            $this->query("SELECT c.id as 'category_id', c.name as 'category_name', c.image as 'category_image' FROM products JOIN categories c on c.id = products.category_id WHERE products.id = :product_id");
            $this->bind(":product_id", $data['product_id']);
            $category = $this->resultSingle();
            if ($category) {
                $data['category_name'] = $category->category_name;
                $data['category_id'] = $category->category_id;
                $data['category_image'] = $category->category_image;
            }
        }

        $create_add_list_product = $this->insert($data);
        $newProductListID = $create_add_list_product->id;

        if ($newProductListID){
            $changeUpdateData = [
                'server_id' => $newProductListID,
                'updated_date' => date('Y-m-d H:i:s')
            ];

            $updateWhere = ['id' => $newProductListID];
            return (array)$this->update($changeUpdateData, $updateWhere);
        }

        return json_encode($create_add_list_product);
    }

    public function remove_list_product($id)
    {
        $deleted_list_products = $this->delete($id);
        return (array)$deleted_list_products;
    }

    public function update_list_product($server_id, $updates)
    {
        $updated_list_products = $this->update([
            'counter' => $updates['counter'] ?? null,
            'name' => $updates['name'] ?? null,
            'unit' => $updates['unit'] ?? null,
            'price' => $updates['price'] ?? null,
            'isSynced' => $updates['isSynced'] ?? null,
            'updated_date' => date('Y-m-d H:i:s')
        ], ['id' => $server_id]);

        return (array)$updated_list_products;
    }

    // Update list product counter
    public function update_list_product_counter($server_id, $counter){
        return $this->update([
            'counter' => $counter,
            'updated_date' => date('Y-m-d H:i:s')
        ], ['id' => $server_id]);
    }

    // Remove all products by list_id
    public function remove_all_products_by_list_id($list_id)
    {
        $sql = "DELETE FROM `$this->table_name` WHERE `list_id` = :list_id";
        $this->query($sql);
        $this->bind(":list_id", $list_id);
        $this->execute();
        return array($list_id);
    }
}

?>