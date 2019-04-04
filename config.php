<?php
error_reporting( E_ALL & ~E_DEPRECATED | E_STRICT );
ini_set('display_errors', 1);
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    define("HOST", "localhost");
    define("DBNAME", "dubaishop");
    define("USERNAME", "root");
    define("PASSWORD", "");
} else {
    define("HOST", "localhost");
    define("DBNAME", "dubaityr_dubaityreshop");
    define("USERNAME", "dubaityr_dubaity");
    define("PASSWORD", "admin@123#");

    define("ABUDHABIDBNAME", "dubaityr_abudhabi");
    define("ABUDHABIUSERNAME", "dubaityr_abudhab");
    define("ABUDHABIPASSWORD", "admin@123#");
}

//Setting_Constatnt
define('DATA_FETCH_STATUS', 'DATA_FETCH_STATUS');
define('DATA_FETCH_PAGE_COUNT', 'DATA_FETCH_PAGE_COUNT');
define('TOTAL_PRODUCT_PAGE_COUNT', 'TOTAL_PRODUCT_PAGE_COUNT');
//define('', '');
//define('', '');

$base_path = str_replace("scraper", '', __DIR__);
$import_path = "";
$abudhabi_import_path = "";
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $import_path = $base_path . "var\\import\\";
    $abudhabi_import_path = $base_path . "tireshopabudhabi.com\\var\\import\\";
} else {
    $import_path = $base_path . "var/import/";
    $abudhabi_import_path = $base_path . "tireshopabudhabi.com/var/import/";
}

define("IMPORT_PATH", $import_path);
define("BASE_PATH", $base_path);
define("ABUDHABI_BASE_PATH", "/home/dubaityr/tireshopabudhabi.com/");
define("ABUDHABI_IMPORT_PATH", "/home/dubaityr/tireshopabudhabi.com/var/import/");
define("ABUDHABI_MEDIA_PATH", "/home/dubaityr/tireshopabudhabi.com/media/import/");



$conn = new PDO('mysql:host=' . HOST . ';dbname=' . DBNAME, USERNAME, PASSWORD);
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$connAbuDhabi = new PDO('mysql:host=' . HOST . ';dbname=' .ABUDHABIDBNAME, ABUDHABIUSERNAME, ABUDHABIPASSWORD);
$connAbuDhabi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

