<?php

/**
 * Language class
 * - middleware
 */
class CategoryTranslation extends Model
{
    /** Check middleware information */
    public function __construct()
    {
        $this->table_name = "category_translations";
        $this->table_columns = [
            'id',
            'category_id',
            'lang_code',
            'name',
            'description',
            'created_date',
            'updated_date'
        ];
    }

    // Create category translation
    public function created_category_translation(array $data): array
    {
        return (array)$this->insert($data);
    }

    // Get category translation
    public function get_category_translation(array $data): array
    {
        // Eğer data boş ise array boş dönsün.
        $translations = $this->get($data);
        if(!$translations){
            return [];
        }
        return (array)$this->get($data);
    }

    // Get category translations
    public function get_category_translations(): array
    {
        $translations = $this->getAll();
        return (array)$translations;
    }

    // Update category translation
    public function update_category_translation($id, $data): array
    {
        return (array)$this->update($data, ['id' => $id]);
    }
}

?>