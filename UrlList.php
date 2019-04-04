<?php
require_once './config.php';
require_once './simple_html_dom.php';

$messages = array();


if(isset($_REQUEST['urlid']) && !empty($_REQUEST['urlid']) && isset($_REQUEST['action']) && !empty($_REQUEST['action'])){
    $del_sql = "Delete FROM aaa_data_product_link Where srno=".$_REQUEST['urlid'];
    $stmt = $conn->prepare($del_sql);
    $stmt->execute();
    header("location:".$_SERVER['SCRIPT_URI']);
    exit;
}



if(isset($_POST['clear_fetch_link']) && isset($_POST['agree_check'])){
    $stmt = $conn->prepare("TRUNCATE aaa_data_product_link");
    $stmt->execute();
    $stmt = $conn->prepare("TRUNCATE aaa_data_product_fetch");
    $stmt->execute();
    $stmt = $conn->prepare("TRUNCATE aaa_product_processed");
    $stmt->execute();
    $upd_stmt = $conn->prepare("Update aaa_settings SET setting_value=:value WHERE setting_key=:key");
    $upd_stmt->execute(array('value' => 0, 'key' => 'DATA_FETCH_STATUS'));
    $upd_stmt->execute(array('value' => 0, 'key' => 'DATA_FETCH_PAGE_COUNT'));
    $upd_stmt->execute(array('value' => 0, 'key' => 'TOTAL_PRODUCT_PAGE_COUNT'));
    $upd_stmt->execute(array('value' => 0, 'key' => 'EMAIL_SENT_STATUS'));
    $upd_stmt->execute(array('value' => 0, 'key' => 'URL_DATA_FETCH_STATUS'));
    $messages[] = "Product link data has been cleared successfully.";
    $messages[] = "Fresh data fetching process will be start with next cron job";
}

$total_product_count = 0;
$actual_product_count = 0;
$pending_product_count = 0;
$processed_product_count = 0;

$stmt = $conn->prepare("SELECT count(*) as total_count FROM aaa_data_product_link");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$total_product_count = $result['total_count'];
$stmt = $conn->prepare("SELECT count(*) as pending_count FROM aaa_data_product_link WHERE status='Pending'");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$pending_product_count = $result['pending_count'];
$stmt = $conn->prepare("SELECT count(*) as complete_count FROM aaa_data_product_link WHERE status='Completed'");
$stmt->execute();
$result = $stmt->fetch(PDO::FETCH_ASSOC);
$processed_product_count = $result['complete_count'];
$stmt = $conn->prepare("SELECT count(page_link) as actual_count FROM aaa_data_product_link GROUP BY page_link");
$stmt->execute();
$result = $stmt->fetchAll(PDO::FETCH_ASSOC);
$actual_product_count = count($result);




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
                <th><a href="UrlList.php" class="active">Product URL List</a></th>
                <th><a href="ProductData.php">Product Data</a></th>
				<th><a href="RelatedProducts.php">Related Products</a></th>
            </tr>
        </table>
            <br/>

            <hr>
        </div>
        <div class="form-section"  style="background-color: #ccc;padding: 20px 0">           
            <table align="center" width="60%">
                <tr>
                    <td>Click this button to clear the old product urls and fetch then again.</td>
                    <td><form action="" method="post"><input type="checkbox" name="agree_check" value="0" style="-moz-transform: scale(1.5);margin-right: 20px;" onclick="confirm('You will loss all you previous data.')"/><button class="button" type="submit" name="clear_fetch_link">Clear & Fetch Product Links</button></form></td>
                </tr>
                <tr>
                    <td>Totel Product Count :  <?php echo $total_product_count ?></td>
                    <td>Actual Product Count :  <?php echo $actual_product_count ?></td>
                </tr>
                <tr>
                    <td>Processed Product count: <?php echo $processed_product_count ?></td>
                    <td>Pending Product Count: <?php echo $pending_product_count ?></td>
                </tr>
            </table>
            
            <br/>
        </div>
        <div class="messages">
            <ul>
                <?php
                foreach($messages as $msg){
                    echo '<li>'.$msg.'</li>';
                }
                ?>
            </ul>
        </div>
        <div class="main">
            <?php
            $stmt = $conn->prepare("SELECT * FROM aaa_data_product_link");
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $table = array();
            $table[] = '<table width="80%" align="center" border="1" cellpadding="5" cellspacing="0">';
            $table[] = '<tr><th>SrNo</th><th>Page No</th><th>Page URL/Link</th><th>Date</th><th>Fetched At</th><th>Status</th></tr>';
            $srno = 1;
            foreach ($result as $row){
                $table[] = '<tr><td align="center">'.$srno.'</td><td align="center">'.$row['page_no'].'</td><td><a href="'.$row['page_link'].'" target="_new">'.$row['page_link'].'</a></td><td>'.  date('d-m-Y H:i:s', strtotime($row['added_at'])).'</td><td>'.(($row['fetched_at']!=NULL)?date('d-m-Y H:i:s', strtotime($row['fetched_at'])):'Not Fetched / '.'<a href="UrlList.php?urlid='.$row['srno'].'&action=delete">Delete</a>').'</td><td>'.$row['status'].'</td></tr>';
                $srno++;
            }
            $table[] = '</table>';
            echo join("", $table);
            ?>
        </div>
    </div>
    <div class="footer"></div>
</div>