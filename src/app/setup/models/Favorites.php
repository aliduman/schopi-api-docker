<?php

/**
 * Favorites class
 * - middleware
 */

 class Favorites extends Model{
    
    public function __construct() {
        
        $this->table_name = "favorites";
        $this->table_columns = [
            'id',
            'user_id',
            'product_id',
            'created_date',
            'updated_date',
        ];
    }

    // Get all favorite
    public function get_all_favorites() {
        $favorites = $this->getAll();
        return (array)$favorites;
    }

    // Get Favorites by id
    public function get_favorites($data) {
        $favorite = $this->get($data);
        return (array)$favorite;
    } 

    // Create Favorites
    public function created_favorites(array $data) {
        $createdFavorites = $this->insert($data);
        return (array)$createdFavorites;
    }

    // Update Favorites
    public function update_favorites($data, $id) {
        $updatedFavorites = $this->update($data, ['id' => $id]);
        return (array)$updatedFavorites;
    }

    // Delete Favorites
    public function delete_favorites($id) {
        $deleteFavorites = $this->delete($id);

        return (array)$deleteFavorites;
    }

    // Kullanıcıya ait favorileri ürün bilgisi ile getirir.
    public function get_favorites_by_user_id($user_id) {
        $query = "select * from products as p inner join favorites as f on p.id = f.product_id where f.user_id = :user_id";
        $this->query($query);
        $this->bind(":user_id", $user_id);
        $favorite_products = $this->resultset();
        return (array)$favorite_products;
    }
 }
?>