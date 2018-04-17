<?php
    function getDatabaseConnection() {
        $host = "us-cdbr-iron-east-05.cleardb.net";
        $username = "bf38758ac9bd19";
        $password = "60af2e62";
        $dbname = "heroku_b6bf6985e868770";
        
        if(strpos($_SERVER['HTTP_HOST'], 'herokuapp') != false) {
           $url = parse_url(getenv("CLEARDB_DATABASE_URL"));
           $host = $url["host"];
           $dbname   = substr($url["path"], 1);
           $username = $url["user"];
           $password = $url["password"];
        }
        $dbConn = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
         $dbConn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
         return $dbConn;
    }

    function insertItemsIntoDB($items) { 
        if (!$items){
            return; 
        }
        
        $db = getDatabaseConnection(); 
        
        foreach ($items as $item) {
            $itemName = $item['name']; 
            $itemPrice = $item['salePrice']; 
            $itemImage = $item['thumbnailImage']; 
            
            $sql = "INSERT INTO item (item_id, name, price, image_url) VALUES (NULL, :itemName, :itemPrice, :itemURL)";
            $statement = $db->prepare($sql); 
            $statement->execute(array(
                itemName => $itemName, 
                itemPrice => $itemPrice, 
                itemURL => $itemImage));
        }
    }
    
    function getMatchingItems($query, $category, $priceFrom, $priceTo, $ordering, $showImages) {
        $db = getDatabaseConnection(); 
        
        $imgSQL = $showImages ? ', item.image_url' : '';
        
        $sql = "SELECT DISTINCT item.item_id, item.name, item.price $imgSQL FROM item 
        INNER JOIN item_category ON item.item_id = item_category.item_id INNER JOIN category 
        ON item_category.category_id =category.category_id  WHERE 1";
        
        if(!empty($query)) {
            $sql .= " AND item.name LIKE '%$query%'";
        }
        
        if (!empty($category)) {
            $sql .= " AND category.category_name = '$category'";
        }
        
        if (!empty($priceFrom)) {
            $sql .= " AND item.price >= '$priceFrom'";
        }

        if (!empty($priceTo)) {
            $sql .= " AND item.price <= '$priceTo'";
        }
        
        if (!empty($ordering)) {
            if ($ordering == 'product') {
                $columnName = 'item.name'; 
            } else {
                $columnName = 'item.price'; 
            }
           
            $sql.= " ORDER BY $columnName";
        }
        
        $statement = $db->prepare($sql);
        $statement->execute();
        $items = $statement->fetchAll();
        
        return $items;
    }
    
    function getCategoriesHTML() {
        $db = getDatabaseConnection(); 
        $categoriesHTML = "<option value=''></option>";  // User can opt to not select a category 
        
        $sql = "SELECT category_name FROM category"; 
        
        $statement = $db->prepare($sql); 
        
        $statement->execute(); 
        
        $records = $statement->fetchAll(PDO::FETCH_ASSOC); 
        
        foreach ($records as $record) {
            $category = $record['category_name']; 
            $categoriesHTML .= "<option value='$category'>$category</option>"; 
        }
        
        return $categoriesHTML; 
    }
    
    function addCategoriesForItems($itemStart, $itemEnd, $category_id) {
        $db = getDatabaseConnection(); 
        
        for ($i = $itemStart; $i <= $itemEnd; $i++) {
            $sql = "INSERT INTO item_category (grouping_id, item_id, category_id) VALUES (NULL, '$i', '$category_id')";
            $db->exec($sql);
        }
    }
?>
