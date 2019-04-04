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

if (isset($_POST['check_pending'])) {
    $stmt = $conn->prepare("DELETE FROM aaa_data_product_fetch WHERE name=''");
    $stmt->execute();
    $stmt = $conn->prepare("UPDATE aaa_data_product_link SET status='Pending' WHERE page_link NOT IN (SELECT a.page_link FROM aaa_data_product_fetch as a)");
    $stmt->execute();
    $messages[] = 'Missing products reset to pending.';
    $messages[] = 'Now check pending products count in URL List page.';
}

if (isset($_POST['reset_process_data'])) {
    $reset = $conn->prepare("UPDATE aaa_data_product_fetch SET status='Pending'");
    $reset->execute();
    $stmt = $conn->prepare("TRUNCATE aaa_product_processed");
    $stmt->execute();
    $messages[] = "All product data reset successfully.";
    $messages[] = "Now process all data again.";
}


if(isset($_POST['export_process_data_abudhabi'])){
    
    $stmt = $conn->prepare("SELECT image FROM aaa_product_processed");
    $stmt->execute();
    $img_db_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $img_db_array = array();
    foreach ($img_db_result as $img){
        $img_db_array[] = $img['image'];
    }
    
    $img_files_result = scandir(BASE_PATH."media/import/");
    
    $img_files_result = array_diff($img_files_result, array(".", ".."));
    
    $files_to_remove = array_diff($img_files_result, $img_db_array);
    
    foreach ($files_to_remove as $file){
        $temp = BASE_PATH."media/import/".$file;
        $abu_temp = ABUDHABI_BASE_PATH."media/import/".$file;
        if(file_exists($temp)){
            if (!unlink($temp)) {
                echo ("Error deleting $temp");
            }
        }
        if(file_exists($abu_temp)){
            if (!unlink($abu_temp)) {
                echo ("Error deleting $abu_temp");
            }
        }
    }
    
    //Coping files to abu dhabi website
    $img_files_result = scandir(BASE_PATH."media/import/");
    $img_files_result = array_diff($img_files_result, array(".", ".."));
    foreach ($img_files_result as $img){
        $source = BASE_PATH."media/import/".$img;
        $destination = ABUDHABI_BASE_PATH."media/import/".$img;
        if(!file_exists($destination)){
            try {
                copy($source, $destination);
            } catch (Exception $ex) {
                $messages[] = $ex->getMessage();
            }
        }
    }
    
    //Finally Coping CSV File to import data //Filter Products data with brands
    
    $delimiter = ",";
    $filenameonly = "import" . date('YmdHis') . ".csv";
    $write_filename = ABUDHABI_IMPORT_PATH . $filenameonly;
    $write = fopen($write_filename, "w");
    $columns = array('sku', 'attribute_set', 'type', 'categories', 'name', 'description', 'short_description', 'price', 'qty', 'is_in_stock', 'manage_stock', 'use_config_manage_stock', 'status', 'visibility', 'weight', 'tax_class_id', 'image', 'thumbnail', 'small_image', 'manufacturer', 'country_of_manufacture', 'sidewall', 'special_price', 'tyre_height', 'tyre_load', 'tyre_rim_size', 'tyre_speed', 'tyre_type', 'tyre_width', 'year_manufacture','tyre_is_clearance', 'buy_3_get_1', 'tyre_run_flat');

    fputcsv($write, $columns);
    $excludeBrands = explode(",", $settings['ABUDABHI_BRANDS_EXCLUDE']);
    $selectQry = "SELECT * FROM aaa_product_processed";
    if(count($excludeBrands) > 0){
        $selectQry .= " WHERE manufacturer NOT IN ('".implode("','", $excludeBrands)."')";
    }
    $stmt = $conn->prepare($selectQry);
    $stmt->execute();
    $export_res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($export_res as $product) {
        $product_array = array('sku' => '', 'attribute_set' => '', 'type' => '', 'categories' => '', 'name' => '', 'description' => '', 'short_description' => '', 'price' => '', 'qty' => '', 'is_in_stock' => '', 'manage_stock' => '', 'use_config_manage_stock' => '', 'status' => '', 'visibility' => '', 'weight' => '', 'tax_class_id' => '', 'image' => '', 'thumbnail' => '', 'small_image' => '', 'manufacturer' => '', 'country_of_manufacture' => '', 'sidewall' => '', 'special_price' => '', 'tyre_height' => '', 'tyre_load' => '', 'tyre_rim_size' => '', 'tyre_speed' => '', 'tyre_type' => '', 'tyre_width' => '', 'year_manufacture' => '', 'tyre_is_clearance' => '', 'buy_3_get_1' => '', 'tyre_run_flat' => '');

        $product_array['sku'] = $product['sku'];
        $product_array['attribute_set'] = $product['attribute_set'];
        $product_array['type'] = $product['type'];
        $product_array['categories'] = $product['categories'];
        $product_array['name'] = $product['name'];
        $product_array['description'] = $product['description'];
        $product_array['short_description'] = $product['short_description'];
        $product_array['price'] = $product['price'];
        $product_array['qty'] = $product['qty'];
        $product_array['is_in_stock'] = $product['is_in_stock'];
        $product_array['manage_stock'] = $product['manage_stock'];
        $product_array['use_config_manage_stock'] = $product['use_config_manage_stock'];
        $product_array['status'] = $product['status'];
        $product_array['visibility'] = $product['visibility'];
        $product_array['weight'] = $product['weight'];
        $product_array['tax_class_id'] = $product['tax_class_id'];
        $product_array['image'] = $product['image'];
        $product_array['thumbnail'] = $product['thumbnail'];
        $product_array['small_image'] = $product['small_image'];
        $product_array['manufacturer'] = $product['manufacturer'];
        $product_array['country_of_manufacture'] = $product['country_of_manufacture'];
        $product_array['sidewall'] = $product['sidewall'];
        $product_array['special_price'] = $product['special_price'];
        $product_array['tyre_height'] = $product['tyre_height'];
        $product_array['tyre_load'] = $product['tyre_load'];
        $product_array['tyre_rim_size'] = $product['tyre_rim_size'];
        $product_array['tyre_speed'] = $product['tyre_speed'];
        $product_array['tyre_type'] = $product['tyre_type'];
        $product_array['tyre_width'] = $product['tyre_width'];
        $product_array['year_manufacture'] = $product['year_manufacture'];
        $product_array['tyre_is_clearance'] = $product['tyre_is_clearance'];
        $product_array['buy_3_get_1'] = $product['buy_3_get_1'];
        $product_array['tyre_run_flat'] = $product['tyre_run_flat'];
        fputcsv($write, $product_array, ",", '"');
    }

    fclose($write);
    copy($write_filename, ABUDHABI_IMPORT_PATH."importdata.csv");
    
//    $import_source = IMPORT_PATH."importdata.csv";
//    $import_destination = ABUDHABI_IMPORT_PATH."importdata.csv";
//    
//    if(file_exists($import_source)){
//        if(file_exists($import_destination)){
//            if(!unlink($import_destination)){
//                $messages[] = "Error in removing previous import data file. Delete importdata.csv file manully in var/import directory.";
//            }
//        }
//        copy($import_source, $import_destination);
//        $messages[] = "Data duplicated to Abu Dhabi website successfully. Now proceed with data import.";
//    }else{
//        $messages[] = "Process data before duplicating to Abu Dhabi Website, imort CSV file doesn't exist.";
//    }
    
    $messages[] = "All data duplicated for Abu Dhabi Website.";
    $messages[] = "Next step goto magmi import in Abu Dhabi Website and import data";
//    $messages[] = count($img_db_array)." Files in Database List, ".count($img_files_result)." Files in Direcotry Listing";
    
}


//Export Product Data
if (isset($_POST['export_process_data'])) {

    $delimiter = ",";
    $filenameonly = "import" . date('YmdHis') . ".csv";
    $write_filename = $import_path . $filenameonly;
    $write = fopen($write_filename, "w");
    $columns = array('sku', 'attribute_set', 'type', 'categories', 'name', 'description', 'short_description', 'price', 'qty', 'is_in_stock', 'manage_stock', 'use_config_manage_stock', 'status', 'visibility', 'weight', 'tax_class_id', 'image', 'thumbnail', 'small_image', 'manufacturer', 'country_of_manufacture', 'sidewall', 'special_price', 'tyre_height', 'tyre_load', 'tyre_rim_size', 'tyre_speed', 'tyre_type', 'tyre_width', 'year_manufacture','tyre_is_clearance', 'buy_3_get_1', 'tyre_run_flat');

    fputcsv($write, $columns);
    $excludeBrands = explode(",", $settings['DUBAI_BRANDS_EXCLUDE']);
    $selectQry = "SELECT * FROM aaa_product_processed";
    if(count($excludeBrands) > 0){
        $selectQry .= " WHERE manufacturer NOT IN ('".implode("','", $excludeBrands)."')";
    }
    $stmt = $conn->prepare($selectQry);
    $stmt->execute();
    $export_res = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($export_res as $product) {
        $product_array = array('sku' => '', 'attribute_set' => '', 'type' => '', 'categories' => '', 'name' => '', 'description' => '', 'short_description' => '', 'price' => '', 'qty' => '', 'is_in_stock' => '', 'manage_stock' => '', 'use_config_manage_stock' => '', 'status' => '', 'visibility' => '', 'weight' => '', 'tax_class_id' => '', 'image' => '', 'thumbnail' => '', 'small_image' => '', 'manufacturer' => '', 'country_of_manufacture' => '', 'sidewall' => '', 'special_price' => '', 'tyre_height' => '', 'tyre_load' => '', 'tyre_rim_size' => '', 'tyre_speed' => '', 'tyre_type' => '', 'tyre_width' => '', 'year_manufacture' => '', 'tyre_is_clearance' => '', 'buy_3_get_1' => '', 'tyre_run_flat' => '');

        $product_array['sku'] = $product['sku'];
        $product_array['attribute_set'] = $product['attribute_set'];
        $product_array['type'] = $product['type'];
        $product_array['categories'] = $product['categories'];
        $product_array['name'] = $product['name'];
        $product_array['description'] = $product['description'];
        $product_array['short_description'] = $product['short_description'];
        $product_array['price'] = $product['price'];
        $product_array['qty'] = $product['qty'];
        $product_array['is_in_stock'] = $product['is_in_stock'];
        $product_array['manage_stock'] = $product['manage_stock'];
        $product_array['use_config_manage_stock'] = $product['use_config_manage_stock'];
        $product_array['status'] = $product['status'];
        $product_array['visibility'] = $product['visibility'];
        $product_array['weight'] = $product['weight'];
        $product_array['tax_class_id'] = $product['tax_class_id'];
        $product_array['image'] = $product['image'];
        $product_array['thumbnail'] = $product['thumbnail'];
        $product_array['small_image'] = $product['small_image'];
        $product_array['manufacturer'] = $product['manufacturer'];
        $product_array['country_of_manufacture'] = $product['country_of_manufacture'];
        $product_array['sidewall'] = $product['sidewall'];
        $product_array['special_price'] = $product['special_price'];
        $product_array['tyre_height'] = $product['tyre_height'];
        $product_array['tyre_load'] = $product['tyre_load'];
        $product_array['tyre_rim_size'] = $product['tyre_rim_size'];
        $product_array['tyre_speed'] = $product['tyre_speed'];
        $product_array['tyre_type'] = $product['tyre_type'];
        $product_array['tyre_width'] = $product['tyre_width'];
        $product_array['year_manufacture'] = $product['year_manufacture'];
        $product_array['tyre_is_clearance'] = $product['tyre_is_clearance'];
        $product_array['buy_3_get_1'] = $product['buy_3_get_1'];
        $product_array['tyre_run_flat'] = $product['tyre_run_flat'];
        fputcsv($write, $product_array, ",", '"');
    }

    fclose($write);
    copy($write_filename, $import_path . "importdata.csv");
    $messages[] = 'Product data has been exported successfully.';
    $messages[] = 'Now goto data import tool to import product data.';
    $messages[] = 'After run data import come back to scrapper and click on Related Product button';
}


if (isset($_POST['process_data'])) {
    $stmt = $conn->prepare("TRUNCATE aaa_product_processed");
    $stmt->execute();
    
    $excludeBrandsDubai = explode(",", $settings['DUBAI_BRANDS_EXCLUDE']);
    $excludeBrandsAbuDhabi = explode(",", $settings['ABUDABHI_BRANDS_EXCLUDE']);
    $commonExcludes = array_intersect($excludeBrandsDubai, $excludeBrandsAbuDhabi);

    if(count($commonExcludes) > 0){
        $updQry = "UPDATE aaa_data_product_fetch SET status='Disabled' WHERE brand in ('".  implode("','", $commonExcludes)."')";
        $upd = $conn->prepare($updQry);
        $upd->execute();
    }
    
    
    $stmt = $conn->prepare("SELECT * FROM aaa_data_product_fetch WHERE status='Pending' AND name <> ''");
    $stmt->execute();
    $pending_result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $process_count = 0;
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $images_path = str_replace("scraper", '', __DIR__) . "media\\import\\";
    } else {
        $images_path = str_replace("scraper", '', __DIR__) . "media/import/";
    }

    //Country Code Array
    $country = array('Afghanistan' => 'AF', 'ÅlandIslands' => 'AX', 'Albania' => 'AL', 'Algeria' => 'DZ', 'AmericanSamoa' => 'AS', 'Andorra' => 'AD', 'Angola' => 'AO', 'Anguilla' => 'AI', 'Antarctica' => 'AQ', 'AntiguaandBarbuda' => 'AG', 'Argentina' => 'AR', 'Armenia' => 'AM', 'Aruba' => 'AW', 'Australia' => 'AU', 'Austria' => 'AT', 'Azerbaijan' => 'AZ', 'Bahamas' => 'BS', 'Bahrain' => 'BH', 'Bangladesh' => 'BD', 'Barbados' => 'BB', 'Belarus' => 'BY', 'Belgium' => 'BE', 'Belize' => 'BZ', 'Benin' => 'BJ', 'Bermuda' => 'BM', 'Bhutan' => 'BT', 'Bolivia' => 'BO', 'BosniaandHerzegovina' => 'BA', 'Botswana' => 'BW', 'BouvetIsland' => 'BV', 'Brazil' => 'BR', 'BritishIndianOceanTerritory' => 'IO', 'BritishVirginIslands' => 'VG', 'Brunei' => 'BN', 'Bulgaria' => 'BG', 'BurkinaFaso' => 'BF', 'Burundi' => 'BI', 'Cambodia' => 'KH', 'Cameroon' => 'CM', 'Canada' => 'CA', 'CapeVerde' => 'CV', 'CaymanIslands' => 'KY', 'CentralAfricanRepublic' => 'CF', 'Chad' => 'TD', 'Chile' => 'CL', 'China' => 'CN', 'ChristmasIsland' => 'CX', 'Cocos(Keeling)Islands' => 'CC', 'Colombia' => 'CO', 'Comoros' => 'KM', 'Congo-Brazzaville' => 'CG', 'Congo-Kinshasa' => 'CD', 'CookIslands' => 'CK', 'CostaRica' => 'CR', 'Côted’Ivoire' => 'CI', 'Croatia' => 'HR', 'Cuba' => 'CU', 'Cyprus' => 'CY', 'CzechRepublic' => 'CZ', 'Denmark' => 'DK', 'Djibouti' => 'DJ', 'Dominica' => 'DM', 'DominicanRepublic' => 'DO', 'Ecuador' => 'EC', 'Egypt' => 'EG', 'ElSalvador' => 'SV', 'EquatorialGuinea' => 'GQ', 'Eritrea' => 'ER', 'Estonia' => 'EE', 'Ethiopia' => 'ET', 'FalklandIslands' => 'FK', 'FaroeIslands' => 'FO', 'Fiji' => 'FJ', 'Finland' => 'FI', 'France' => 'FR', 'FrenchGuiana' => 'GF', 'FrenchPolynesia' => 'PF', 'FrenchSouthernTerritories' => 'TF', 'Gabon' => 'GA', 'Gambia' => 'GM', 'Georgia' => 'GE', 'Germany' => 'DE', 'Ghana' => 'GH', 'Gibraltar' => 'GI', 'Greece' => 'GR', 'Greenland' => 'GL', 'Grenada' => 'GD', 'Guadeloupe' => 'GP', 'Guam' => 'GU', 'Guatemala' => 'GT', 'Guernsey' => 'GG', 'Guinea' => 'GN', 'Guinea-Bissau' => 'GW', 'Guyana' => 'GY', 'Haiti' => 'HT', 'Heard&amp;McDonaldIslands' => 'HM', 'Honduras' => 'HN', 'HongKongSARChina' => 'HK', 'Hungary' => 'HU', 'Iceland' => 'IS', 'India' => 'IN', 'Indonesia' => 'ID', 'Iran' => 'IR', 'Iraq' => 'IQ', 'Ireland' => 'IE', 'IsleofMan' => 'IM', 'Israel' => 'IL', 'Italy' => 'IT', 'Jamaica' => 'JM', 'Japan' => 'JP', 'Jersey' => 'JE', 'Jordan' => 'JO', 'Kazakhstan' => 'KZ', 'Kenya' => 'KE', 'Kiribati' => 'KI', 'Kuwait' => 'KW', 'Kyrgyzstan' => 'KG', 'Laos' => 'LA', 'Latvia' => 'LV', 'Lebanon' => 'LB', 'Lesotho' => 'LS', 'Liberia' => 'LR', 'Libya' => 'LY', 'Liechtenstein' => 'LI', 'Lithuania' => 'LT', 'Luxembourg' => 'LU', 'MacauSARChina' => 'MO', 'Macedonia' => 'MK', 'Madagascar' => 'MG', 'Malawi' => 'MW', 'Malaysia' => 'MY', 'Maldives' => 'MV', 'Mali' => 'ML', 'Malta' => 'MT', 'MarshallIslands' => 'MH', 'Martinique' => 'MQ', 'Mauritania' => 'MR', 'Mauritius' => 'MU', 'Mayotte' => 'YT', 'Mexico' => 'MX', 'Micronesia' => 'FM', 'Moldova' => 'MD', 'Monaco' => 'MC', 'Mongolia' => 'MN', 'Montenegro' => 'ME', 'Montserrat' => 'MS', 'Morocco' => 'MA', 'Mozambique' => 'MZ', 'Myanmar(Burma)' => 'MM', 'Namibia' => 'NA', 'Nauru' => 'NR', 'Nepal' => 'NP', 'Netherlands' => 'NL', 'NetherlandsAntilles' => 'AN', 'NewCaledonia' => 'NC', 'NewZealand' => 'NZ', 'Nicaragua' => 'NI', 'Niger' => 'NE', 'Nigeria' => 'NG', 'Niue' => 'NU', 'NorfolkIsland' => 'NF', 'NorthernMarianaIslands' => 'MP', 'NorthKorea' => 'KP', 'Norway' => 'NO', 'Oman' => 'OM', 'Pakistan' => 'PK', 'Palau' => 'PW', 'PalestinianTerritories' => 'PS', 'Panama' => 'PA', 'PapuaNewGuinea' => 'PG', 'Paraguay' => 'PY', 'Peru' => 'PE', 'Philippines' => 'PH', 'PitcairnIslands' => 'PN', 'Poland' => 'PL', 'Portugal' => 'PT', 'PuertoRico' => 'PR', 'Qatar' => 'QA', 'Réunion' => 'RE', 'Romania' => 'RO', 'Russia' => 'RU', 'Rwanda' => 'RW', 'SaintBarthélemy' => 'BL', 'SaintHelena' => 'SH', 'SaintKittsandNevis' => 'KN', 'SaintLucia' => 'LC', 'SaintMartin' => 'MF', 'SaintPierreandMiquelon' => 'PM', 'Samoa' => 'WS', 'SanMarino' => 'SM', 'SãoToméandPríncipe' => 'ST', 'SaudiArabia' => 'SA', 'Senegal' => 'SN', 'Serbia' => 'RS', 'Seychelles' => 'SC', 'SierraLeone' => 'SL', 'Singapore' => 'SG', 'Slovakia' => 'SK', 'Slovenia' => 'SI', 'SolomonIslands' => 'SB', 'Somalia' => 'SO', 'SouthAfrica' => 'ZA', 'SouthGeorgia&amp;SouthSandwichIslands' => 'GS', 'SouthKorea' => 'KR', 'Spain' => 'ES', 'SriLanka' => 'LK', 'St.Vincent&amp;Grenadines' => 'VC', 'Sudan' => 'SD', 'Suriname' => 'SR', 'SvalbardandJanMayen' => 'SJ', 'Swaziland' => 'SZ', 'Sweden' => 'SE', 'Switzerland' => 'CH', 'Syria' => 'SY', 'Taiwan' => 'TW', 'Tajikistan' => 'TJ', 'Tanzania' => 'TZ', 'Thailand' => 'TH', 'Timor-Leste' => 'TL', 'Togo' => 'TG', 'Tokelau' => 'TK', 'Tonga' => 'TO', 'TrinidadandTobago' => 'TT', 'Tunisia' => 'TN', 'Turkey' => 'TR', 'Turkmenistan' => 'TM', 'TurksandCaicosIslands' => 'TC', 'Tuvalu' => 'TV', 'Uganda' => 'UG', 'Ukraine' => 'UA', 'UnitedArabEmirates' => 'AE', 'UnitedKingdom' => 'GB', 'UnitedStates' => 'US', 'Uruguay' => 'UY', 'U.S.OutlyingIslands' => 'UM', 'U.S.VirginIslands' => 'VI', 'Uzbekistan' => 'UZ', 'Vanuatu' => 'VU', 'VaticanCity' => 'VA', 'Venezuela' => 'VE', 'Vietnam' => 'VN', 'WallisandFutuna' => 'WF', 'WesternSahara' => 'EH', 'Yemen' => 'YE', 'Zambia' => 'ZM', 'Zimbabwe' => 'ZW');


    $ins_stmt = $conn->prepare('INSERT INTO aaa_product_processed VALUES(:srno, :page_link, :sku, :attribute_set, :type, :categories, :name, :description, :short_description, :price, :qty, :is_in_stock, :manage_stock, :use_config_manage_stock, :status, :visibility, :weight, :tax_class_id, :image, :thumbnail, :small_image, :manufacturer, :country_of_manufacture, :sidewall, :special_price, :tyre_height, :tyre_load, :tyre_rim_size, :tyre_speed, :tyre_type, :tyre_width, :year_manufacture, :tyre_is_clearance, :buy_3_get_1, :tyre_run_flat, :related_products, :created_at)');
    $upd_stmt = $conn->prepare("UPDATE aaa_data_product_fetch SET status='Processed' WHERE srno=:srno");
    foreach ($pending_result as $item) {
        /* warranty_text */
        $product_array = array('srno' => NULL, 'page_link' => '', 'sku' => '', 'attribute_set' => 'Default', 'type' => 'simple', 'categories' => 'Our Products', 'name' => '', 'description' => '', 'short_description' => '', 'price' => '', 'qty' => '', 'is_in_stock' => '', 'manage_stock' => '1', 'use_config_manage_stock' => '1', 'status' => '1', 'visibility' => '4', 'weight' => '1', 'tax_class_id' => 'Taxable Goods', 'image' => '', 'thumbnail' => '', 'small_image' => '', 'manufacturer' => '', 'country_of_manufacture' => '', 'sidewall' => '', 'special_price' => '', 'tyre_height' => '', 'tyre_load' => '', 'tyre_rim_size' => '', 'tyre_speed' => '', 'tyre_type' => '', 'tyre_width' => '', 'year_manufacture' => '', 'tyre_is_clearance' => '', 'buy_3_get_1' => '', 'tyre_run_flat' => '', 'related_products' => '', 'created_at' => '');

        $product_array['page_link'] = $item['page_link'];
        $desc = explode("SKU:", strtoupper($item['description']));
        $product_array['sku'] = trim($desc[1]);
        $product_array['name'] = $item['name'];
        $product_array['description'] = $item['description'];
        $product_array['short_description'] = $item['name'];
        if ($item['price'] > 50) {
            $product_array['price'] = round(($item['price'] - $settings['PRICE_CHANGE_VALUE']) * 95 /100 , 2);
        } else {
            $product_array['price'] = $item['price'];
        }
        if ($item['special_price'] > 50) {
            $product_array['special_price'] = round(($item['special_price'] - $settings['PRICE_CHANGE_VALUE']) * 95 /100, 2);
        } else {
            $product_array['special_price'] = $item['special_price'];
        }
        $product_array['qty'] = $settings['PRODUCT_DEFAULT_STOCK'];
        $product_array['is_in_stock'] = $item['stock'];
        $img_path = "";
        if (!empty($item['main_img'])) {
            $img = explode("/", $item['main_img']);
            $img_path = str_replace(array("/"), "", $product_array['sku']) . "_" . $img[count($img) - 1];
        }
        $product_array['image'] = $img_path;
        $product_array['thumbnail'] = $img_path;
        $product_array['small_image'] = $img_path;
        $product_array['manufacturer'] = $item['brand'];
        if (!empty(trim($item['manufacture']))) {
            $product_array['country_of_manufacture'] = $country[$item['manufacture']];
        }
        if (!empty($item['serv_desc'])) {
            $load = explode(" ", $item['serv_desc']);
            $product_array['tyre_load'] = $load[0];
            if (isset($load[1])) {
                $product_array['tyre_speed'] = $load[1];
            }
        }

        $product_array['tyre_type'] = $item['v_type'];
        $size = explode("/", $item['size']);
        if (count($size) > 0) {
            $product_array['tyre_width'] = $size[0];
        }
        if (count($size > 1)) {
            $height = explode("R", $size[1]);
            if (count($height) > 0) {
                $product_array['tyre_height'] = $height[0];
                if (count($height) > 1) {
                    $product_array['tyre_rim_size'] = "R" . $height[1];
                }
            }
        }
        $product_array['sidewall'] = $item['sidewall'];
        $product_array['year_manufacture'] = $item['model'];
        $product_array['tyre_is_clearance'] = $item['tyre_is_clearance'];
        $product_array['buy_3_get_1'] = $item['buy_3_get_1'];
        $product_array['tyre_run_flat'] = $item['tyre_run_flat'];
        
        $product_array['related_products'] = '';
        $product_array['created_at'] = date('Y-m-d H:i:s');
        //        echo '<pre>'; print_r($product_array); echo '<pre>';
        $ins_stmt->execute($product_array);
        $upd_stmt->execute(array('srno' => $item['srno']));
        //Img Download
        if (!empty($item['main_img'])) {
            if ($img[count($img) - 1] != 'thumbnail.jpg' && $img[count($img) - 1] != 'image.jpg') {
                $img_url = 'http://www.pitstoparabia.com/media/catalog/product/' . $img[count($img) - 3] . "/" . $img[count($img) - 2] . "/" . $img[count($img) - 1];
                $filename = str_replace(array("/"), "", $product_array['sku']) . "_" . $img[count($img) - 1];
                $location = $images_path . $filename;
                $img_url = $file_path = preg_replace('/\s/i', '%20', $img_url);
//                echo $img_url;
//                echo $location;
//                exit;
                if (!file_exists($location)) {
                    copy($img_url, $location);
                }
            }
        }
        $process_count++;
    }
    $messages[] = "Product data has been processed successfully. Now click on Export button to export data.";
    $messages[] = "$process_count Products processed successfully.";
}


$actual_product_count = 0;
$total_product_count = 0;
$stmt = $conn->prepare("SELECT count(page_link) as actual_count FROM aaa_data_product_link GROUP BY page_link");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
$actual_product_count = count($result);
//
$stmt = $conn->prepare("SELECT count(*) total_count FROM aaa_product_processed WHERE name <> ''");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_product_count = $result['total_count'];
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
                    <th><a href="ProductData.php" class="active">Product Data</a></th>
                    <th><a href="RelatedProducts.php">Related Products</a></th>
                </tr>
            </table>
            <br/>

            <hr>
        </div>
        <div class="form-section">
            <table align="center" width="60%">
                <tr>
                    <td colspan="3" align="center"><h2>Process Product Data to Import</h2></td>
                </tr>
                <tr>
                <form action="" method="post" style="background-color: #ccc;padding: 20px 0">
                    <td align="center"><input class="textbox" type="text" name="price_change" placeholder="Enter Price Change Value || Default <?= $settings['PRICE_CHANGE_VALUE'] ?>" /></td>
                    <td align="center"><input class="textbox" type="text" name="stock_change" placeholder="Enter Product Stock || Default <?= $settings['PRODUCT_DEFAULT_STOCK'] ?>" /></td>
                    <td align="center"><input class="button" type="submit" name="process_data" value="Process Data" /></td>
                </form>
                </tr>
                <tr>
                    <td colspan="3"><br/></td>
                </tr>
                <tr>
                    <td align="center"><form action="" method="post"><input class="button" type="submit" name="reset_process_data" value="Reset Process Data" /></form></td>
                    <td align="center"><form action="" method="post"><input class="button" type="submit" name="export_process_data" value="Export Product Data" /></form></td>
                    <td align="center"><form action="" method="post"><input class="button" type="submit" name="export_process_data_abudhabi" value="Export Data For Abu Dhabi" /></form></td>
                </tr>
                <tr>
                    <td>Totel Product Count :  <?php echo $total_product_count ?></td>
                    <td>Actual Product Count :  <?php echo $actual_product_count ?></td>
                    <td><form method="post"><input type="submit" class="button" name="check_pending" value="Check Pending Products" /></form></td>
                </tr>
            </table>
            <br/>
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
        <div class="main">
                <?php
                $stmt = $conn->prepare("SELECT * FROM aaa_product_processed");
                $stmt->execute();
                $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
                $table = array();
                $table[] = '<table width="80%" align="center" border="1" cellpadding="5" cellspacing="0">';
                $table[] = '<tr><th>SrNo</th><th>SKU</th><th>Size</th><th>Price</th><th>Special Price</th><th>Stock Status</th><th>Created At</th></tr>';
                $srno = 1;
                foreach ($result as $row) {
                    $table[] = '<tr><td align="center">' . $srno . '</td><td align="center"><a href="' . $row['page_link'] . '" target="_new">' . $row['sku'] . '</a></td><td>' . $row['tyre_width'] . "/" . $row['tyre_height'] . " " . $row['tyre_rim_size'] . '</td><td>' . $row['price'] . '</td><td>' . $row['special_price'] . '</td><td>' . (($row['is_in_stock'] == 0) ? 'Out of Stock' : 'In Stock') . '</td><td>' . $row['created_at'] . '</td></tr>';
                    $srno++;
                }
                $table[] = '</table>';
                echo join("", $table);
                ?>
        </div>
    </div>
    <div class="footer"></div>
</div>