<?php

namespace VantageMarket\Controllers;

class CatalogController
{
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function index()
    {
        // 1. Capture filter arrays and values from URL parameters
        $maxPrice = $_GET['max_price'] ?? null;
        $search = $_GET['search'] ?? null;
        $selectedCategories = $_GET['category'] ?? null; // This will be an array of checked IDs

        // 2. Select columns using c.* to dynamically extract category text fields safely
        $sql = "SELECT v.*, p.category_id, c.* FROM v_active_products v
                INNER JOIN products p ON v.product_id = p.product_id 
                INNER JOIN categories c ON p.category_id = c.category_id
                WHERE 1=1";
        $params = [];

        // 3. Filter by Keyword Search
        if (!empty($search)) {
            $sql .= " AND v.title LIKE :search";
            $params['search'] = "%" . $search . "%";
        }

        // 4. Filter by Maximum Price
        if (!empty($maxPrice)) {
            $sql .= " AND v.price <= :max_price";
            $params['max_price'] = $maxPrice;
        }

        // 5. Filter by Checked Categories Matrix
        if (!empty($selectedCategories) && is_array($selectedCategories)) {
            $cleanIds = array_map('intval', $selectedCategories);
            $sql .= " AND p.category_id IN (" . implode(',', $cleanIds) . ")";
        }

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll();

        // Send the complete dataset to your layout view
        include __DIR__ . '/../../views/catalogue_view.php';
    }
}
