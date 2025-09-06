<?php

class ListProductsAPI extends API
{
    public ListProducts $ListProducts;
    public $id;
    public $user_id;
    public $product_id;
    public $list_id;
    public $server_id;

    public Authentication $Authentication;

    function __construct()
    {
        /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
        $this->Authentication = $this->model("Authentication");
        if (!$this->Authentication->authenticateJWTToken()) {
            exit;
        }
        // JWT içinden aldığımız kullanıcı id
        $this->user_id = $this->Authentication->userData['id'];
        $this->ListProducts = $this->model("ListProducts");
    }

    public function get_list_products() {
        // Liste kullanıcıya ait ise ürünleri getir. Değilse ve paylaşılmamışsa erişim izni var mı kontrol et.
        $userInList = $this->ListProducts->check_user_list($this->list_id, $this->user_id);
        $sharedInUserList = $this->ListProducts->shared_check_user_list($this->user_id, $this->list_id);

        if ($userInList || $sharedInUserList) {
            $return = $this->ListProducts->get_list_products($this->list_id);
            $this->json($return);
        }else{
            $this->json(false);
            return;
        }
    }

    public function get_all_list_products() {
        $return = $this->ListProducts->get_all_list_products();
        $this->json($return);
    }

    public function add_list_product()
    {
        $return = $this->ListProducts->add_list_product([
            'category_id' => $this->request->category_id ?? null,
            'product_id' => $this->request->product_id ?? null,
            'customProductId' => $this->request->customProductId ?? "",
            'core_list_id' => $this->request->core_list_id ?? "",
            'user_id' => $this->request->user_id ?? $this->user_id,
            'server_id' => $this->request->server_id ?? 0,
            'counter' => $this->request->counter ?? 0,
            'isChecked' => $this->request->isChecked ?? 0,
            'isTick' => $this->request->isTick ?? 0,
            'isSynced' => $this->request->isSynced ?? 0,
            'first_letter' => $this->request->first_letter ?? "",
            'category_image' => $this->request->category_image ?? "",
            'category_name' => $this->request->category_name ?? "",
            'checkedCount' => $this->request->checkedCount ?? 0,
            'created_date' => $this->request->created_date ?? date('Y-m-d H:i:s'),
            'updated_date' => $this->request->updated_date ?? date('Y-m-d H:i:s'),
            'is_packet' => $this->request->is_packet ?? 0,
            'name' => $this->request->name ?? null,
            'unit' => $this->request->unit ?? null,
            'price' => $this->request->price ?? 1.0,
            'list_id' => $this->request->list_id
        ],$this->request->minusOrPlus);

        $this->json((array)$return);
    }

    public function add_list_product_mobile()
    {
        $return = $this->ListProducts->add_list_product_mobile([
            'category_id' => $this->request->category_id ?? null,
            'product_id' => $this->request->product_id ?? null,
            'customProductId' => $this->request->customProductId ?? "",
            'core_list_id' => $this->request->core_list_id ?? "",
            'user_id' => $this->request->user_id ?? $this->user_id,
            'server_id' => $this->request->server_id ?? 0,
            'counter' => $this->request->counter ?? 0,
            'isChecked' => $this->request->isChecked ?? 0,
            'isTick' => $this->request->isTick ?? 0,
            'isSynced' => $this->request->isSynced ?? 0,
            'first_letter' => $this->request->first_letter ?? "",
            'category_image' => $this->request->category_image ?? "",
            'category_name' => $this->request->category_name ?? "",
            'checkedCount' => $this->request->checkedCount ?? 0,
            'created_date' => $this->request->created_date ?? date('Y-m-d H:i:s'),
            'updated_date' => $this->request->updated_date ?? date('Y-m-d H:i:s'),
            'is_packet' => $this->request->is_packet ?? 0,
            'image' => $this->request->image ?? "",
            'name' => $this->request->name ?? null,
            'unit' => $this->request->unit ?? null,       
            'price' => $this->request->price ?? null, 
            'list_id' => $this->request->list_id ?? null
        ]);

        $this->json((array)$return);
    }

    public function update_list_product()
    {
        $updates = [
            'counter' => $this->request->counter ?? null,
            'name' => $this->request->name ?? null,
            'unit' => $this->request->unit ?? null,
            'price' => $this->request->price ?? null,
            'isSynced' => $this->request->isSynced ?? null
        ];


        $return = $this->ListProducts->update_list_product(
            $this->request->server_id,
            $updates
        );

        $this->json($return);
    }

    /*Update list product counter*/
    public function update_list_product_counter()
    {
        $return = $this->ListProducts->update_list_product_counter(
            $this->request->server_id,
            $this->request->counter
        );

        $this->json((array)$return);
    }

    public function remove_list_product()
    {
        $return = $this->ListProducts->remove_list_product($this->id);
        $this->json($return);
    }

    public function get_product_server_id()
    {
        $serverId = $this->ListProducts->get_product_server_id($this->user_id);

        $this->json([
            'user_id' => $this->user_id,
            'server_id' => $serverId
        ]);
    }

    public function remove_all_list_products()
    {
        $return = $this->ListProducts->remove_all_products_by_list_id($this->list_id);
        $this->json($return);
    }
}

?>