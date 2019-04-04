<?php
require_once './config.php';
require_once './simple_html_dom.php';

//Get All Setting from database
$settings = array();
$stmt = $conn->prepare("SELECT * FROM aaa_settings");
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($res as $set) {
    $settings[$set['setting_key']] = $set['setting_value'];
}

$messages = array();

if(isset($_POST['clear_related_products'])){
    $stmt = $conn->prepare("DELETE FROM mgty_catalog_product_link WHERE link_type_id=4");
    $stmt->execute();
    $stmt = $conn->prepare("DELETE FROM mgty_catalog_product_link_attribute_int WHERE link_id NOT IN (SELECT link_id FROM mgty_catalog_product_link)");
    $stmt->execute();
    //Clear Related Products on Abu Dhabi Website
    $stmt = $connAbuDhabi->prepare("DELETE FROM mgty_catalog_product_link WHERE link_type_id=4");
    $stmt->execute();
    $stmt = $connAbuDhabi->prepare("DELETE FROM mgty_catalog_product_link_attribute_int WHERE link_id NOT IN (SELECT link_id FROM mgty_catalog_product_link)");
    $stmt->execute();
    $messages[] = "All product links or related products cleared successfully.";
    $messages[] = "Proceed to relate product again.";
}

if(isset($_POST['prepare_related_products'])){  
    $stmt = $conn->prepare("SELECT page_link FROM aaa_product_processed GROUP BY page_link");
    $stmt->execute();
    $page_links = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($page_links as $link){
        $stmt = $conn->prepare("SELECT related FROM `aaa_data_product_fetch` WHERE page_link ='".$link['page_link']."'");
        $stmt->execute();
        $related = $stmt->fetch(PDO::FETCH_ASSOC);
        if(!empty($related['related'])){
            $links = explode(",", $related['related']);
            if(count($links)==1){
                $stmt = $conn->prepare("SELECT sku FROM aaa_product_processed WHERE page_link = '".$links[0]."'");
                $stmt->execute();
                $skus = $stmt->fetch(PDO::FETCH_ASSOC);
                $stmt = $conn->prepare("UPDATE aaa_product_processed SET related_products='".$skus['sku']."' WHERE page_link='".$link['page_link']."'");
                $stmt->execute();
            }else{
                $stmt = $conn->prepare("SELECT sku FROM aaa_product_processed WHERE page_link IN ('".implode("','", $links)."')");
                $stmt->execute();
                $skus = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $sku_list = array();
                foreach ($skus as $sku){
                    $sku_list[] = $sku['sku'];
                }
                $stmt = $conn->prepare("UPDATE aaa_product_processed SET related_products='".(implode(",", $sku_list))."' WHERE page_link='".$link['page_link']."'");
                $stmt->execute();
            }
        }
    }
    
}
if(isset($_POST['process_related_products'])){
    
    $stmt = $conn->prepare("SELECT sku, related_products FROM aaa_product_processed");
    $stmt->execute();
    $related = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($related as $single){
        $sku = $single['sku'];
        $child = explode(",", $single['related_products']);
        $stmt = $conn->prepare("SELECT entity_id FROM mgty_catalog_product_entity WHERE sku = '".$sku."'");
        $stmt->execute();
        $entity_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $entity_id = $entity_result['entity_id'];
        $stmt = $conn->prepare("SELECT entity_id FROM mgty_catalog_product_entity WHERE sku IN ('".implode("','", $child)."')");
        $stmt->execute();
        $entity_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $stmt = $conn->prepare("INSERT INTO mgty_catalog_product_link(product_id, linked_product_id, link_type_id) VALUES(:product_id, :linked_product_id, :link_type_id)");
        foreach ($entity_list as $entity){
            if($entity['entity_id'] != $entity_id){
                $stmt->execute(array('product_id' => $entity_id, 'linked_product_id' => $entity['entity_id'], 'link_type_id' => '4'));
            }
        }
        $stmt = $conn->prepare("SELECT link_id FROM mgty_catalog_product_link WHERE product_id='".$entity_id."'");
        $stmt->execute();
        $link_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if(count($link_result)>0){
            $stmt = $conn->prepare("INSERT INTO mgty_catalog_product_link_attribute_int (product_link_attribute_id, link_id, value) VALUES(:product_link_attribute_id, :link_id, :value);");
            foreach($link_result as $link){
                $stmt->execute(array('product_link_attribute_id' => '4', 'link_id' => $link['link_id'], 'value' => '0'));
            }
        }
    }
    
    //Process Related Products on Abu Dabhi Website

    foreach ($related as $single){
        $sku = $single['sku'];
        $child = explode(",", $single['related_products']);
        $stmt = $connAbuDhabi->prepare("SELECT entity_id FROM mgty_catalog_product_entity WHERE sku = '".$sku."'");
        $stmt->execute();
        $entity_result = $stmt->fetch(PDO::FETCH_ASSOC);
        $entity_id = $entity_result['entity_id'];
        if(!empty($entity_id)){
            $stmt = $connAbuDhabi->prepare("SELECT entity_id FROM mgty_catalog_product_entity WHERE sku IN ('" . implode("','", $child) . "')");
            $stmt->execute();
            $entity_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $stmt = $connAbuDhabi->prepare("INSERT INTO mgty_catalog_product_link(product_id, linked_product_id, link_type_id) VALUES(:product_id, :linked_product_id, :link_type_id)");
            foreach ($entity_list as $entity) {
                if ($entity['entity_id'] != $entity_id) {
                    $stmt->execute(array('product_id' => $entity_id, 'linked_product_id' => $entity['entity_id'], 'link_type_id' => '4'));
                }
            }
            $stmt = $connAbuDhabi->prepare("SELECT link_id FROM mgty_catalog_product_link WHERE product_id='" . $entity_id . "'");
            $stmt->execute();
            $link_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($link_result) > 0) {
                $stmt = $connAbuDhabi->prepare("INSERT INTO mgty_catalog_product_link_attribute_int (product_link_attribute_id, link_id, value) VALUES(:product_link_attribute_id, :link_id, :value);");
                foreach ($link_result as $link) {
                    $stmt->execute(array('product_link_attribute_id' => '4', 'link_id' => $link['link_id'], 'value' => '0'));
                }
            }
        }
    }
    $messages[] = "Product Related process is completed successfully.";
}

?>
<style type="text/css">
.button{background-color: #4584f0;padding: 10px 20px;border-radius: 5px;border-width: 0;font-weight: bold;font-size: 16px;cursor: pointer}
.button:hover{background: #ff0000;color: #ffffff;}
.textbox {width: 90%;padding: 8px;border-radius: 2px;border: 1px solid #000;}
.menu a {color: #000;}
.menu a.active {color: blue;}
</style>
<!--Main Page Started-->
<div class="page">
    <div class="header">
        <h1 style="text-align: center;text-decoration: underline">Dubai Tyre Shop Scrapping Tool</h1>
    </div>
    <div class="content">
        <div class="menu">
            <table width="60%" align="center">
                <tr>
                    <th><a href="index.php">Home</a></th>
                    <th><a href="UrlList.php">Product URL List</a></th>
                    <th><a href="ProductData.php">Product Data</a></th>
                    <th><a href="RelatedProducts.php" class="active">Related Product</a></th>
                </tr>
            </table>
            <br/>

            <hr>
        </div>
        <div class="messages">
            <ul>
<?php
foreach ($messages as $msg) {
    echo '<li>' . $msg . '</li>';
}
?>
            </ul>
        </div>
        <div class="form-section">
            <table align="center" width="60%">
                <tr>
                    <td>Prepare Related Products</td>
                    <td><form method="post"><input type="submit" class="button" name="prepare_related_products" value="Prepare Related Products" /></form></td>
                </tr>
                <tr>
                    <td>Process Related Products</td>
                    <td><form method="post"><input type="submit" class="button" name="process_related_products" value="Process Related Products" /></form></td>
                </tr>
                <tr>
                    <td>Clear Related Products</td>
                    <td><form method="post"><input type="submit" class="button" name="clear_related_products" value="Clear Related Products" /></form></td>
                </tr>
            </table>
            <br/>
        </div>
        
        <div class="main">
            <?php
            $stmt = $conn->prepare('SELECT srno, page_link, sku, related_products FROM aaa_product_processed ORDER BY srno');
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            ?>
            <table width="80%" border="1" cellspacing="0" border="1" align="center">
                <tr>
                    <th>Sr No</th><th>Product Sku</th><th>Related Count</th><th>Related Product Sku</th>
                </tr>
                <?php
                if(count($result)>0){
                    $rows = array();
                    foreach ($result as $row){
                        $rows[] = '<tr><td align="center">'.$row['srno'].'</td><td><a href="'.$row['page_link'].'">'.$row['sku'].'</a></td><td align="center">'.((!empty($row['related_products']))?count(explode(",", $row['related_products'])):"0").'</td><td>'.$row['related_products'].'</td></tr>';
                    }
                    echo join("", $rows);
                }
                ?>
            </table>
        </div>
    </div>
    <div class="footer"></div>
</div>