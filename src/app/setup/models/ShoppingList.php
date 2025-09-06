<?php

/**
 * List class
 * - middleware
 */
class ShoppingList extends Model{

    public $id = null;

    public function __construct()
    {
        $this->table_name = "list";
        $this->table_columns = ['id', 'name','created_date'];
    }

    // Get all list
    public function get_all(){
        $sql = "SELECT * FROM `$this->table_name`";
        $this->query($sql);
        $row = $this->resultSingle();
        return $row;
    }

    // Get list by id
    public function get($id){
        $sql = "SELECT * FROM `$this->table_name` WHERE `id` = :id";
        $this->query($sql);
        $this->bind(":id", $id);
        $row = $this->resultSingle();
        return $row;
    }

    // Create list
    public function create($data){
        $sql = "INSERT INTO `$this->table_name` (`name`) VALUES (:name)";
        $this->query($sql);
        $this->bind(":name", $data->name);
        $this->execute();
        $newID = $this->lastInsertId();
        return $this->get($newID);
    }

    // Update list
    public function update($id, $data){
        $sql = "UPDATE `$this->table_name` SET `name` = :name WHERE `id` = :id";
        $this->query($sql);
        $this->bind(":id", $id);
        $this->bind(":name", $data->name);
        $this->execute();
        return $this->get($id);
    }

    // Delete list
    public function delete($id){
        $sql = "DELETE FROM `$this->table_name` WHERE `id` = :id";
        $this->query($sql);
        $this->bind(":id", $id);
        $this->execute();
        return true;
    }

}

?>