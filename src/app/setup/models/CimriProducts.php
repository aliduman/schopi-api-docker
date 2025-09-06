<?php

/**
 * Cimri product crawler
 * - middleware
 */

 class CimriProducts extends Model{
    
    public function __construct() {
        
        $this->table_name = "cimri_products";
        $this->table_columns = [
            'id',
            'name',
            'price',
            'unit_price',
            'unit',
            'image',
            'created_date',
            'updated_date'
        ];
    }

    // Create category
    public function created_product(array $data) {
        $createProduct = $this->insert($data);
        return (array)$createProduct;
    }
 }
?>