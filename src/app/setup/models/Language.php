<?php

/**
 * Language class
 * - middleware
 */
class Language extends Model
{
    /** Check middleware information */
    public function __construct()
    {
        $this->table_name = "languages";
        $this->table_columns = [
            'id',
            'name',
            'code',
            'native_name',
            'is_active',
            'created_date',
            'updated_date'
        ];
    }

    // Get product by id
    public function get_language($data): array
    {
        return (array)$this->get($data);
    }

    // Get all product
    public function get_all_language(): array
    {
        $languages = $this->getAll();
        return (array)$languages;
    }

    // Create product
    public function created_language(array $data): array
    {
        return (array)$this->insert($data);
    }

    // Update product
    public function update_language($id, $data): array
    {
        return (array)$this->update($data, ['id' => $id]);
    }

    // Delete product
    public function delete_language($id): array
    {
        return (array)$this->delete($id);
    }
}

?>