<?php
/**
 * List Class
 * - middleware
 */

class Templates extends Model
{
    public $userId;
    public function __construct()
    {
        $this->table_name = "list_templates";
        $this->table_columns = [
            'id',
            'server_id',
            'name',
            'image',
            'is_selected',
            'is_tick',
            'created_date',
            'updated_date',
        ];
    }

    // Get All list
    public function get_all_templates(){
        return $this->getAll();
    }

    public function save_template($data) {
        return $this->insert($data);
    }

    // Update template
    public function update_template($data) {
        if (empty($data['id']) || $data['id'] <= 0) {
            throw new Exception("Geçersiz template ID");
        }

        // Güncelleme işlemi
        $updateData = [
            'name' => $data['name'],
            'image' => $data['image'],
            'updated_date' => date('Y-m-d H:i:s')
        ];

        return $this->update($updateData, ['id' => $data['id']]);
    }

    // Get template by ID
    public function get_template_by_id($id) {
        if (empty($id) || $id <= 0) {
            throw new Exception("Geçersiz template ID");
        }

        $this->query("SELECT * FROM " . $this->table_name . " WHERE id = :id LIMIT 1");
        $this->bind(':id', $id);
        $result = $this->resultSingle();

        if ($result) {
            return (array)$result;
        } else {
            throw new Exception("Template bulunamadı");
        }
    }

    //Delete template
    public function delete_template($data) {
        if (empty($data['id']) || $data['id'] <= 0) {
            throw new Exception("Geçersiz template ID");
        }

        return (array)$this->delete($data['id']);
    }
}
?>