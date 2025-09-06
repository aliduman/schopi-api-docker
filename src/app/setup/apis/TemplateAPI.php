<?php
class TemplateAPI extends Api {

    public Templates $Templates;
    public $id;
    public $request;
    public $lang_code;
    public $userId;
    public Authentication $Authentication;

    function __construct() {
        /* Login gerektiren sınıflarda bu alanın eklenmesi gerekiyor */
        $this->Authentication = $this->model("Authentication");
        if (!$this->Authentication->authenticateJWTToken()) {
            exit;
        }
        // JWT içinden aldığımız kullanıcı id
        $this->userId = $this->Authentication->userData['id'];
        $this->Templates = $this->model('Templates');
    }

    // Get all categories
    public function get_all_templates() {
        $this->json($this->Templates->get_all_templates());
    }

    // Save template with image upload
    public function save_template() {
        try{
            $data = [
                'name' => $this->request->name ?? '',
                'image' => $this->request->image ?? ''
            ];

            $this->Templates->save_template($data);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
        }
    }

    // Update template
    public function update_template() {
        try {
            $data = [
                'id' => $this->id ?? 0,
                'name' => $this->request->name ?? '',
                'image' => $this->request->image ?? ''
            ];

            if ($data['id'] <= 0) {
                throw new Exception("Geçersiz template ID");
            }

            $this->Templates->update_template($data);

        } catch (Exception $e) {
            $this->json(['success' => false, 'message' => 'Bir hata oluştu: ' . $e->getMessage()]);
        }
    }

    // Delete template
    public function delete_template() {
        try {
            $id = $this->id ?? 0;

            if ($id <= 0) {
                throw new Exception("Geçersiz template ID");
            }

            $this->Templates->delete_template(['id' => $id]);

        } catch (Exception $e) {
            return [false];
        }
    }
}
?>