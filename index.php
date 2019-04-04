<?php
require_once './config.php';
require_once './simple_html_dom.php';


if(isset($_POST['save_settings'])){

    $upd_stmt = $conn->prepare("Update aaa_settings SET setting_value=:value WHERE setting_key=:key");
    $upd_stmt->execute(array('value' => $_POST['PRODUCT_FETCH_COUNT'], 'key' => 'PRODUCT_FETCH_COUNT'));
    $upd_stmt->execute(array('value' => $_POST['PRICE_CHANGE_VALUE'], 'key' => 'PRICE_CHANGE_VALUE'));
    $upd_stmt->execute(array('value' => $_POST['PRODUCT_DEFAULT_STOCK'], 'key' => 'PRODUCT_DEFAULT_STOCK'));
    $upd_stmt->execute(array('value' => $_POST['NOTIFICATOIN_EMAIL'], 'key' => 'NOTIFICATOIN_EMAIL'));
    
    $dubaiBrandToExclude = array();
    if(isset($_POST['brand_exclude_dubai'])){
        foreach ($_POST['brand_exclude_dubai'] as $brd){
            $dubaiBrandToExclude[] = $brd;
        }
    }
    if(isset($_POST['brand_left_dubai'])){
        foreach ($_POST['brand_left_dubai'] as $brd){
            $dubaiBrandToExclude[] = $brd;
        }
    }

    $dubaiBrandToExclude = implode(",", $dubaiBrandToExclude);
    $upd_stmt->execute(array('value' => $dubaiBrandToExclude, 'key' => 'DUBAI_BRANDS_EXCLUDE'));
    
    $abuDhabiBrandToExclude = array();
    if(isset($_POST['brand_exclude_abudhabi'])){
        foreach ($_POST['brand_exclude_abudhabi'] as $brd){
            $abuDhabiBrandToExclude[] = $brd;
        }
    }
    if(isset($_POST['brand_left_abudhabi'])){
        foreach ($_POST['brand_left_abudhabi'] as $brd){
            $abuDhabiBrandToExclude[] = $brd;
        }
    }
    $abuDhabiBrandToExclude = implode(",", $abuDhabiBrandToExclude);
    $upd_stmt->execute(array('value' => $abuDhabiBrandToExclude, 'key' => 'ABUDABHI_BRANDS_EXCLUDE'));
    
}

//Get All Setting from database
$settings = array();
$stmt = $conn->prepare("SELECT * FROM aaa_settings");
$stmt->execute();
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($res as $set) {
    $settings[$set['setting_key']] = $set['setting_value'];
}

$messages = array();
$total_product_url_count = 0;
$actual_product_count = 0;
$total_fetched_product_count = 0;
$fetch_pending_product_count = 0;
$stmt = $conn->prepare("SELECT count(*) as total_url_count FROM aaa_data_product_link");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_product_url_count = $result['total_url_count'];
$stmt = $conn->prepare("SELECT count(page_link) as actual_total_count FROM aaa_data_product_link GROUP BY page_link");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
$actual_product_count = count($result);
$stmt = $conn->prepare("SELECT count(*) as fetch_total_count FROM aaa_data_product_fetch");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_fetched_product_count = $result['fetch_total_count'];
$stmt = $conn->prepare("SELECT count(*) as pending_url_count FROM aaa_data_product_link WHERE status='Pending'");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$fetch_pending_product_count = $result['pending_url_count'];


$stmt = $conn->prepare("SELECT brand FROM aaa_data_product_fetch GROUP BY brand");
$stmt->execute();
$brandsResult = $stmt->fetchAll(PDO::FETCH_ASSOC);
$brandsList = array();
foreach ($brandsResult as $brd){
    $brandsList[] = $brd['brand'];
}


$dubailSelectedBrands = explode(",", $settings['DUBAI_BRANDS_EXCLUDE']);
$abuDhabiSelectedBrands = explode(",", $settings['ABUDABHI_BRANDS_EXCLUDE']);

$dubaiBrandsLeft = array_diff($brandsList, $dubailSelectedBrands);
$abuDubaiBrandsLeft = array_diff($brandsList, $abuDhabiSelectedBrands);


?>
<style type="text/css">
.button{background-color: #4584f0;padding: 10px 20px;border-radius: 5px;border-width: 0;font-weight: bold;font-size: 16px;cursor: pointer}
.button:hover{background: #ff0000;color: #ffffff;}
.textbox {width: 90%;padding: 8px;border-radius: 2px;border: 1px solid #000;}
.menu a {color: #000;}
.menu a.active {color: blue;}
.checkbox {width: 30%;float: left;}
.checkbox-remove {width: 30%;float: left;color: #ff0000}
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
                    <th><a href="index.php" class="active">Home</a></th>
                    <th><a href="UrlList.php">Product URL List</a></th>
                    <th><a href="ProductData.php">Product Data</a></th>
                    <th><a href="RelatedProducts.php">Related Products</a></th>
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
                    <td colspan="2" align="center"><h2>Settings and Statistic</h2></td>
                </tr>
                <tr>
                    <td>Totel Product URL Count :  <?php echo $total_product_url_count ?></td>
                    <td>Actual Product Count :  <?php echo $actual_product_count ?></td>
                </tr>
                <tr>
                    <td>Totel Fetched Product Count :  <?php echo $total_fetched_product_count ?></td>
                    <td>Fetch Pending Product Count :  <?php echo $fetch_pending_product_count ?></td>
                </tr>
            </table>
            <br/>
        </div>
        
        <div class="main">
            <form method="post">
                <table width="60%" align="center">
                    <tr>
                        <td colspan="2">Settings Data</td>
                    </tr>
                    <tr><td colspan="2"><hr/></td></tr>
                    <tr>
                        <td width="40%">Current Data Fetch Status</td>
                        <td><input type="text" class="textbox" name="DATA_FETCH_STATUS" value="<?= ($settings['DATA_FETCH_STATUS'] ==0 )?"Running":"Completed" ?>" readonly/></td>
                    </tr>
                    <tr>
                        <td>Number of Product to fetched with cron job</td>
                        <td><input type="number" class="textbox" name="PRODUCT_FETCH_COUNT" value="<?= $settings['PRODUCT_FETCH_COUNT'] ?>" required/></td>
                    </tr>
                    <tr>
                        <td>Price Amount Need to Less</td>
                        <td><input type="number" class="textbox" name="PRICE_CHANGE_VALUE" value="<?= $settings['PRICE_CHANGE_VALUE'] ?>" required /></td>
                    </tr>
                    <tr>
                        <td>Default Stock for Products</td>
                        <td><input type="number" class="textbox" name="PRODUCT_DEFAULT_STOCK" value="<?= $settings['PRODUCT_DEFAULT_STOCK'] ?>" required /></td>
                    </tr>
                    <tr>
                        <td>Email id to get Notification:</td>
                        <td><input type="email" class="textbox" name="NOTIFICATOIN_EMAIL" value="<?= $settings['NOTIFICATOIN_EMAIL'] ?>" required/></td>
                    </tr>
                    <tr>
                        <td>Data Finish Email Status</td>
                        <td><input type="text" class="textbox" name="EMAIL_SENT_STATUS" value="<?= ($settings['EMAIL_SENT_STATUS']==0) ? "Not Sent" : "Notified" ?>" readonly /></td>
                    </tr>
                    <tr>
                        <td>Excluded Brands on Dubai Tyre Shop</td>
                        <td><hr/><?php
                            foreach ($dubailSelectedBrands as $brd){
                                if(!empty($brd)){
                                    echo '<div class="checkbox-remove"><input type="checkbox" name="brand_exclude_dubai[]" value="'.$brd.'" checked="checked"/>'.$brd.'</div>';
                                }
                            }
                        ?></td>
                    </tr>
                    <tr>
                        <td>Brands on Dubai Tyre Shop</td>
                        <td><hr/><?php
                            foreach ($dubaiBrandsLeft as $brd){
                                if(!empty($brd)){
                                    echo '<div class="checkbox"><input type="checkbox" name="brand_left_dubai[]" value="'.$brd.'"/>'.$brd.'</div>';
                                }
                            }
                        ?></td>
                    </tr>
                    <tr>
                        <td>Excluded Brands on Abu Dabhi Shop</td>
                        <td><hr/><?php
                            foreach ($abuDhabiSelectedBrands as $brd){
                                if(!empty($brd)){
                                    echo '<div class="checkbox-remove"><input type="checkbox" name="brand_exclude_abudhabi[]" value="'.$brd.'" checked="checked"/>'.$brd.'</div>';
                                }
                            }
                        ?></td>
                    </tr>
                    <tr>
                        <td>Brands on Abu Dabhi Shop</td>
                        <td><hr/><?php
                            foreach ($abuDubaiBrandsLeft as $brd){
                                if(!empty($brd)){
                                    echo '<div class="checkbox"><input type="checkbox" name="brand_left_abudhabi[]" value="'.$brd.'"/>'.$brd.'</div>';
                                }
                            }
                        ?></td>
                    </tr>
                    <tr><td colspan="2"><hr/></td></tr>
                    <tr>
                        <td></td>
                        <td><input type="submit" class="button" name="save_settings" value="Save Settings"/></td>
                    </tr>
                </table>
            </form>
        </div>
    </div>
    <div class="footer"></div>
</div>