<?php

/**
 * Category class
 * - middleware
 */

 class Category extends Model{
    
    public function __construct() {
        
        $this->table_name = "categories";
        $this->table_columns = [
            'id',
            'name',
            'info',
            'status',
            'image',
            'created_date',
            'updated_date'
        ];
    }

    // Get all category
    public function get_all_category() {
        $categories = $this->getAll();
        return (array)$categories;
    }

    // Get category by id
    public function get_category($data) {
        $category = $this->get($data);
        return (array)$category;
    } 

    // Create category
    public function created_category(array $data) {
        $createdCategory = $this->insert($data);
        return (array)$createdCategory;
    }

    // Update category
    public function update_category($data, $id) {
        $updatedCategory = $this->update($data, ['id' => $id]);
        return (array)$updatedCategory;
    }

    // Delete Category
    public function delete_category($id) {
        $deleteCategory = $this->delete($id);

        return (array)$deleteCategory;
    }

    public function get_category_with_language($lang_code)
    {
        $sql = "SELECT category_translations.name as name,category_translations.description as description, categories.image,categories.id FROM categories LEFT JOIN category_translations ON categories.id = category_translations.category_id WHERE category_translations.lang_code = :lang_code ORDER BY categories.id";
        $this->query($sql);
        $this->bind(':lang_code', $lang_code);
        $categorys = $this->resultset();
        return (array)$categorys;

    }
 }
?>