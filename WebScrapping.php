<?php
require_once './config.php';
require_once './simple_html_dom.php';


//Get All Setting from database
$settings = array();
$stmt = $conn->prepare("SELECT * FROM aaa_settings");
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($res as $set){
    $settings[$set['setting_key']] = $set['setting_value'];
}

if ($settings['TOTAL_PRODUCT_PAGE_COUNT'] == 0) {
    $html = file_get_html('https://www.pitstoparabia.com/index.php/catalog/seo_sitemap/product/');
    if (!empty($html)) {
        $max_page_count = $html->find('div.pagination ul li a.last', 0)->plaintext;
        if ($max_page_count > 0) {
            $upd_stmt = $conn->prepare("UPDATE aaa_settings SET setting_value=:value WHERE setting_key='TOTAL_PRODUCT_PAGE_COUNT'");
            $upd_stmt->execute(array("value" => $max_page_count));
            $settings['TOTAL_PRODUCT_PAGE_COUNT'] = $max_page_count;
        }
    } else {
        echo 'Fetching script is not working or data fetch link is not working. Please check the data link';
        exit();
    }
}


//Scrapping Product Links
if ($settings['DATA_FETCH_PAGE_COUNT'] >= $settings['TOTAL_PRODUCT_PAGE_COUNT']) {
    $stmt = $conn->prepare("UPDATE aaa_settings SET setting_value=:value WHERE setting_key='URL_DATA_FETCH_STATUS'");
    $stmt->execute(array('value' => 1));
    $settings['URL_DATA_FETCH_STATUS'] = 1;
}



if ($settings['URL_DATA_FETCH_STATUS'] == '0') {
    $start = $settings['DATA_FETCH_PAGE_COUNT'];
    $end = $settings['TOTAL_PRODUCT_PAGE_COUNT'];
    for ($i = $start; $i < $end; $i++) {
        $current_page = $i + 1;
        $html = file_get_html('https://www.pitstoparabia.com/index.php/catalog/seo_sitemap/product/?p=' . $current_page);
        if (!empty($html)) {
            foreach ($html->find('ul.sitemap li a') as $element) {
                $page_link = trim($element->href);
                $stmt = $conn->prepare("INSERT INTO aaa_data_product_link(srno, page_no, page_link, added_at, status) VALUES (:srno, :page_no, :page_link, :added_at, :status)");
                $data = array('srno' => NULL, 'page_no' => '', 'page_link' => '', 'added_at' => '', 'status' => '');
                $data['page_no'] = $current_page;
                $data['page_link'] = $page_link;
                $data['added_at'] = date('Y-m-d H:i:s');
                $data['status'] = 'Pending';
                $stmt->execute($data);
            }
        }
        $stmt = $conn->prepare("UPDATE aaa_settings SET setting_value=:value WHERE setting_key='DATA_FETCH_PAGE_COUNT'");
        $stmt->execute(array("value" => $current_page));
//        break;
    }
}


//Product fetching and data storing process to database
$product_fetch_count = $settings['PRODUCT_FETCH_COUNT'];

$sql = "SELECT * FROM aaa_data_product_link WHERE status='Pending' ORDER BY fetched_at ASC LIMIT 0, " . $product_fetch_count;
$stmt = $conn->prepare($sql);
$stmt->execute();
$stmt->setFetchMode(PDO::FETCH_ASSOC);
$result = $stmt->fetchAll();

//Check link fetching status
$link_status = array();

foreach ($result as $page_link) {
    $product_data = array('srno' => null, 'page_link' => '', 'name' => '', 'brand' => '', 'brand_img' => '', 'main_img' => '', 'size' => '', 'sidewall' => '', 'serv_desc' => '', 'serv_desc_details' => '', 'manufacture' => '', 'stock' => '0', 'v_type' => '', 'utqg' => '', 'model' => '', 'price' => '', 'special_price' => '', 'description' => '', 'related' => '', 'tyre_treadwear' => '', 'tyre_traction' => '', 'tyre_temperature' => '', 'tyre_fuel' => '', 'tyre_wet' => '', 'tyre_noise' => '','tyre_is_clearance' => '', 'buy_3_get_1' => '', 'tyre_run_flat' => 0, 'top_text' => '', 'updated_at' => date('Y-m-d H:i:s'), 'status' => 'Pending');
    
    try {
        $link_status[$page_link['srno']] = array("link" => $page_link['page_link'], "status" => 'FALSE');
        $page_html = file_get_html($page_link['page_link']);
        if (!empty($page_html)) {
            $product_data['page_link'] = $page_link['page_link'];
            if(($page_html->find('.product_detail_right .product_title h1', 0))){
                $product_data['name'] = trim($page_html->find('.product_detail_right .product_title h1', 0)->plaintext);
            }
            if(($page_html->find('div.brand a', 0))){
                $product_data['brand'] = trim($page_html->find('div.brand a', 0)->title);
            }
            if(($page_html->find('div.brand a img', 0))){
                $product_data['brand_img'] = trim($page_html->find('div.brand a img', 0)->src);
            }
            if (($page_html->find("div.detail_left ul li"))) {
                $txth5 = $page_html->find("div.detail_left ul li");
                foreach ($txth5 as $elem) {
                    if (strpos($elem->plaintext, "Country") !== FALSE) {
                        $product_data['manufacture'] = trim(str_replace(array("Country:", " "), "", $elem->plaintext));
                    }elseif (strpos($elem->plaintext, "Size") !== FALSE) {
                        $product_data['size'] = trim(str_replace(array("Size:", " "), "", $elem->plaintext));
                    } elseif (strpos($elem->plaintext, "Sidewall") !== FALSE) {
                        $product_data['sidewall'] = trim(str_replace("Sidewall Style:", "", $elem->plaintext));
                    } elseif (strpos($elem->plaintext, "Serv.") !== FALSE) {
                        $serv_text = trim(str_replace("Serv. Desc:", "", $elem->plaintext));
                        if(strpos($serv_text, "Load") !== FALSE){
                            $serv_arr = explode("Load", $serv_text);
                            $product_data['serv_desc'] = trim(str_replace(array("</span>"), "", $serv_arr[0]));
                        }else{
                            $product_data['serv_desc'] = trim(str_replace("Serv. Desc:", "", $elem->plaintext));
                        }
                    } elseif (strpos($elem->plaintext, "UTQG") !== FALSE) {
                        $childs = $elem->children;
                        foreach ($childs as $child){
                            $attr = $child->attr;
                            if (strpos($attr['title'], "Treadwear") !== FALSE){
                                $product_data['tyre_treadwear'] = trim(str_replace("&nbsp;", "", $child->plaintext));
                            }elseif (strpos($attr['title'], "Traction") !== FALSE){
                                $product_data['tyre_traction'] = trim(str_replace("&nbsp;", "", $child->plaintext));
                            }elseif (strpos($attr['title'], "Temperature") !== FALSE){
                                $product_data['tyre_temperature'] = trim(str_replace("&nbsp;", "", $child->plaintext));
                            }else{
                                $product_data['utqg'] = trim(str_replace("&nbsp;", "", $child->plaintext));
                            }
                        }
                    } elseif (strpos($elem->plaintext, "Year") !== FALSE) {
                        $product_data['model'] = trim(str_replace("Year:", "", $elem->plaintext));
                    }
                }
            }
            
            if(($page_html->find('.detail_left img.v_type'))){
                $elem = $page_html->find('.detail_left img.v_type', 0)->title;
                if($elem == 'Run Flat'){
                    $product_data['tyre_run_flat'] = 1;
                }else{
                    $product_data['tyre_run_flat'] = 0;
                }
            }
            if(($page_html->find('.product_thumbnail_container .availability'))){
                $stock = trim($page_html->find('.product_thumbnail_container .availability', 0)->plaintext);
                if($stock == 'In stock'){
                    $product_data['stock'] = 1;
                }  else {
                    $product_data['stock'] = 0;
                }
            }
            
            if(($page_html->find('.product_img .variants .v_type', 0))){
                $product_data['v_type'] = $page_html->find('.product_img .variants .v_type', 0)->title;
            }
            if(($page_html->find('img#zoom_01', 0))){
                $product_data['main_img'] = trim($page_html->find('img#zoom_01', 0)->src);
            }
            if(($page_html->find('div.serv_desc_detail', 0))){
                $product_data['serv_desc_details'] = trim($page_html->find('div.serv_desc_detail', 0)->plaintext);
            }
            if(($page_html->find('div.price-box span.price', 0))){
                $product_data['price'] = trim(str_replace(array("AED", ","), "", $page_html->find('div.price-box span.price', 0)->plaintext));
            }
            if(($page_html->find('div.price-box span.price', 1))){
                $product_data['special_price'] = trim(str_replace(array("AED", ","), "", $page_html->find('div.price-box span.price', 1)->plaintext));
            }
            if(($page_html->find('div.detail_descrption', 0))){
                $product_data['description'] = trim($page_html->find('div.detail_descrption', 0)->plaintext);
            }
            
            
            if (($page_html->find('div.topbar', 0))) {
                $top_bar = trim($page_html->find('div.topbar', 0)->plaintext);
                if (!empty($top_bar)) {
                    $top_bar = strtoupper(str_replace(" ", "", $top_bar));
                    $product_data['top_text'] = $top_bar;
                    if ($top_bar == 'CLEARANCESALE') {
                        $product_data['tyre_is_clearance'] = 'CLEARANCESALE';
                    } elseif ($top_bar == 'BUY3+1FREE') {
//                        $product_data['buy_3_get_1'] = 'Yes';
                        $product_data['buy_3_get_1'] = 'No';
                    }elseif($top_bar == 'FREEALIGN+NITRO'){
                        $product_data['tyre_is_clearance'] = 'FREEALIGN+NITRO';
                    }
                }
            }
            
            if (($page_html->find(".detail_left .tyres_labels .tyre_label"))) {
                $outer_label = $page_html->find(".detail_left .tyres_labels .tyre_label");
                foreach ($outer_label as $label) {
                    $attr = $label->attr;
                    if (isset($attr['title']) && (strpos($attr['title'], "Fuel") !== FALSE)) {
                        $product_data['tyre_fuel'] = trim($label->plaintext);
                    } elseif (isset($attr['title']) && (strpos($attr['title'], "Wet") !== FALSE)) {
                        $product_data['tyre_wet'] = trim($label->plaintext);
                    } elseif (isset($attr['title']) && (strpos($attr['title'], "External") !== FALSE)) {
                        $product_data['tyre_noise'] = trim($label->plaintext);
                    }
                }
            }

            $related = array();
            if (($page_html->find('li.item .product_name a'))) {
                foreach ($page_html->find('li.item .product_name a') as $elem) {
                    $related[] = trim($elem->href);
                }
            }
            $product_data['related'] = implode(",", $related);
            
            $stmt = $conn->prepare("SELECT srno FROM aaa_data_product_fetch WHERE page_link = '" . $page_link['page_link'] . "'");
            $stmt->execute();
            $stmt->setFetchMode(PDO::FETCH_ASSOC);
            $result = $stmt->fetchAll();
//            echo '<pre>';
//            print_r($product_data);
//            echo '</pre>';
//            exit;
            if (count($result) == 0) {
                $stmt = $conn->prepare("INSERT INTO aaa_data_product_fetch VALUES (:srno, :page_link, :name, :brand, :brand_img, :main_img, :size, :sidewall, :serv_desc, :serv_desc_details, :manufacture, :stock, :v_type, :utqg, :model, :price, :special_price, :description, :related, :tyre_treadwear, :tyre_traction, :tyre_temperature, :tyre_fuel, :tyre_wet, :tyre_noise, :tyre_is_clearance, :buy_3_get_1, :tyre_run_flat, :top_text, :updated_at, :status)");
                $stmt->execute($product_data);
            } 
            $fetched_at = date('Y-m-d H:i:s');
            $sql = "UPDATE aaa_data_product_link SET fetched_at='" . $fetched_at . "', status='Completed' WHERE srno='" . $page_link['srno'] . "'";
            $updstmt = $conn->prepare($sql);
            $updstmt->execute();
            $link_status[$page_link['srno']]['status'] = 'TRUE';
        }
    } catch (Exception $ex) {
        $fetched_at = date('Y-m-d H:i:s');
            $sql = "UPDATE aaa_data_product_link SET fetched_at='" . $fetched_at . "', status='Failed' WHERE srno='" . $page_link['srno'] . "'";
            $updstmt = $conn->prepare($sql);
            $updstmt->execute();
        echo $ex->getMessage();
    }
//    break;
}


//Check for download failed status

foreach ($link_status as $status){
    if($status['status'] == 'FALSE'){
        echo $sql = "DELETE FROM aaa_data_product_link WHERE page_link='".$status['link']."'";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
    }
}


//UPDATE FETCH STATUS
if(!empty($settings['NOTIFICATOIN_EMAIL'])){
    $chk_stmt = $conn->prepare("SELECT count(*) as 'pending' FROM aaa_data_product_link WHERE status='Pending'");
    $chk_stmt->execute();
    $chk_data = $chk_stmt->fetch(PDO::FETCH_ASSOC);
    if($chk_data['pending'] == 0 && $settings['DATA_FETCH_STATUS'] == 0 ){
        $upd_stmt = $conn->prepare("UPDATE aaa_settings SET setting_value=1 WHERE setting_key='DATA_FETCH_STATUS'");
        $upd_stmt->execute();
    }
}

//NOTIFICATOIN_EMAIL
if(!empty($settings['NOTIFICATOIN_EMAIL'])){
    $chk_stmt = $conn->prepare("SELECT count(*) as 'pending' FROM aaa_data_product_link WHERE status='Pending'");
    $chk_stmt->execute();
    $chk_data = $chk_stmt->fetch(PDO::FETCH_ASSOC);
    if($chk_data['pending'] == 0 && $settings['EMAIL_SENT_STATUS'] == 0 ){
        $result = mail($settings['NOTIFICATOIN_EMAIL'], "Data Scrapping Notification", "All product data is scrapped successfully from Pits Website. Please proceed with data import.");
        $upd_stmt = $conn->prepare("UPDATE aaa_settings SET setting_value=1 WHERE setting_key='EMAIL_SENT_STATUS'");
        $upd_stmt->execute();   
    }
}