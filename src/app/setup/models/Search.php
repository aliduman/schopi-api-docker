<?php

/**
 * Products class
 * - middleware
 */
class Search extends Model
{

    public $id = null;

    /** Check middleware information */
    public function __construct()
    {
        $this->table_name = "search_history";
        $this->table_columns = [
            'id',
            'user_id',
            'product_id',
            'created_date',
            'updated_date'
        ];
    }

    // Save search product
    public function save_search_history($data)
    {
        $search = $this->insert($data);
        return (array)$search;
    }

    // Remove all search history by user
    public function remove_all_search_history_by_user($data)
    {
        $search = $this->delete($data);
        return (array)$search;
    }

    // Remove search history by user
    public function remove_search_history_by_user($data)
    {
        $search = $this->delete($data);
        return (array)$search;
    }


}

?>