<?php

/**
 * Products class
 * - middleware
 */
class Product extends Model
{
    /** Check middleware information */
    public function __construct()
    {
        $this->table_name = "products";
        $this->table_columns = [
            'id',
            'category_id',
            'name',
            'image',
            'price',
            'unit',
            'info',
            'created_date',
            'updated_date',
            'owner_id',
        ];
    }

    // Get product by id
    public function get_product($data): array
    {
        return (array)$this->get($data);
    }

    // Get all product
    public function get_all_product(): array
    {
        $products = $this->getAll();
        return (array)$products;
    }

    // Create product
    public function created_product(array $data): array
    {
        return (array)$this->insert($data);
    }

    // Update product
    public function update_product($id, $data): array
    {
        return (array)$this->update($data, ['id' => $id]);
    }

    // Delete product
    public function delete_product($id): array
    {
        return (array)$this->delete($id);
    }

    // Get product by category
    public function get_product_with_category(): array
    {
        $sql = "SELECT products.*, categories.name as category_name,categories.description as category_description FROM products LEFT JOIN categories ON products.category_id = categories.id";
        $this->query($sql);
        $products = $this->resultset();
        return (array)$products;
    }

    // Get product all translation
    /*public function get_all_product_translation($lang_code): array
    {
        $sql = "SELECT products.*, product_translations.name as translation FROM products LEFT JOIN product_translations ON products.id = product_translations.product_id WHERE product_translations.lang_code = :lang_code";
        $this->query($sql);
        $this->bind(':lang_code', $lang_code);
        $products = $this->resultset();
        return (array)$products;
    }*/

    // Get products with language
    public function get_product_with_language($lang_code): array
    {
        $sql = "SELECT product_translations.name as translation_name,product_translations.description as translation_description,products.* FROM products LEFT JOIN product_translations ON products.id = product_translations.product_id WHERE product_translations.lang_code = :lang_code";
        $this->query($sql);
        $this->bind(':lang_code', $lang_code);
        $products = $this->resultset();
        return (array)$products;
    }

}

?>