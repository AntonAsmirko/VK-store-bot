<?php

namespace App\repository;

require_once "vendor/autoload.php";

class CatalogRepository{
    
    private $PDO;

    public function __construct($pdo){
        $this->PDO = $pdo;
    }

    public function getCategories() {
        $result_array = array();
        $query_res = $this->PDO->query("SELECT * FROM category");
        $rows = $query_res->fetchAll();
        foreach($rows as $row) {
            array_push($result_array, array("$row[1] (id: $row[0])\n$row[2]", $row[3]));
        }
        return $result_array;
    }

    public function getItemsByCategory($categoryId) {
        $result_array = array();
        $query_res = $this->PDO->query("SELECT * from 
            item JOIN category_to_item ON item.ID = category_to_item.ITEM_ID 
            WHERE category_to_item.CAT_ID = $categoryId");
        $rows = $query_res->fetchAll();
        foreach($rows as $row) {
            array_push($result_array,
            array("$row[1]\nОписание товара:\n$row[2]\nСтоимость:$row[3]₽", $row[4]));
        }
        return $result_array;
    }

    public function getItemInfo($itemName) {
        $result_array = array();
        $query_res = $this->PDO->query("SELECT * FROM item WHERE item.ITEM_NAME = '$itemName'");
        $rows = $query_res->fetchAll();
        foreach($rows as $row) {
            array_push($result_array, "$row[1]\nОписание товара:\n$row[2]\nСтоимость:$row[3]₽");
        }
        return $result_array[0];
    }

    public function addItem($itemId, $itemName,
                            $itemDescription, $itemPrice,
                             $itemMedia, $categoryId) {
        $this->PDO->query("INSERT INTO item (ID, ITEM_NAME, ITEM_DESCRIPTION, ITEM_PRICE, MEDIA_ID) 
        VALUES ($itemId,
        '$itemName',
        '$itemDescription',
        $itemPrice,
        '$itemMedia'
         )");
        $this->PDO->query("INSERT INTO category_to_item (CAT_ID, ITEM_ID)
                             VALUES ($categoryId, $itemId);");
    }   

    public function addCategory($id, $catName, $description, $mediaId){
        $this->PDO->query("INSERT INTO category (ID, CAT_NAME, CAT_DESCRIPTION, MEDIA_ID)
        VALUES ($id,'$catName','$description', '$mediaId')");
    }

    public function removeItem($itemId) {
        $this->PDO->query("DELETE FROM item WHERE ID = $itemId");
        $this->PDO->query("DELETE FROM category_to_item WHERE ITEM_ID = $itemId");
    }

    public function removeCategory($catId){
        $this->PDO->query("DELETE FROM category WHERE ID = $catId");
        $this->PDO->query("DELETE FROM category_to_item WHERE CAT_ID = $catId");
    }
}