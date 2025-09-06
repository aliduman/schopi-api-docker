<?php

/**
 * Language class
 * - middleware
 */
class ProductTranslation extends Model
{
    /** Check middleware information */
    public function __construct()
    {
        $this->table_name = "product_translations";
        $this->table_columns = [
            'id',
            'product_id',
            'lang_code',
            'name',
            'description',
            'created_date',
            'updated_date'
        ];
    }

    // Create product translation
    public function created_product_translation(array $data): array
    {
        return (array)$this->insert($data);
    }

    // Get product translation
    public function get_product_translation(array $data): array
    {
        // Eğer data boş ise array boş dönsün.
        $translations = $this->get($data);
        if(!$translations){
            return [];
        }
        return (array)$this->get($data);
    }

    // Get product translations
    public function get_product_translations(): array
    {
        $translations = $this->getAll();
        return (array)$translations;
    }

    // Update product translation
    public function update_product_translation($id, $data): array
    {
        return (array)$this->update($data, ['id' => $id]);
    }
}

?>