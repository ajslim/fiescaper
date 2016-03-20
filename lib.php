<?php

ini_set('error_reporting', -1);
ini_set('display_errors', 1);
ini_set('html_errors', 1); 



require "vendor/autoload.php";
use Masterminds\HTML5;

require "auth.php";

$page = 1;
if(isset($_GET['page'])) {
    $page = $_GET['page'];
}

$category = "S";
if(isset($_GET['category'])) {
    $category = $_GET['category'];
}

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 


function assignIfSet($var, $index, $default="") {
    return isset($var[$index]) ? $var[$index] : $default;
}



function getPage($Url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $Url);
    curl_setopt($ch, CURLOPT_REFERER, "http://www.example.org/yay.htm");
    curl_setopt($ch, CURLOPT_USERAGENT, "MozillaXYZ/1.0");
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $output = curl_exec($ch);
    curl_close($ch);    
    return $output;
}
 
function getPageTableRows($page) {
    $html5 = new HTML5();
    $dom = $html5->loadHTML($page);

    $rows = array();
    foreach($dom->getElementsByTagName('table') as $table) {
        # Show the <a href>

        if($table->getAttribute("class") == "items") {

            foreach($table->getElementsByTagName('tr') as $row) {
                array_push($rows, $row);
            }
        }

    }

    return $rows;
}

function splitName($name) {
    

    $name = trim($name);
    $firstLowerCasePosition = strcspn($name, 'abcdefghijklmnopqrstuvwxyz');
    
    $lastNameChunk = substr($name, 0, $firstLowerCasePosition);
    $spacePosition = strrpos($lastNameChunk, " ");

    $lastName = substr($name, 0, $spacePosition);
    $firstName = substr($name, $spacePosition);
    
    return array(
        "last_name" => trim($lastName),
        "first_name" => trim($firstName)
    );  
    
}

function reformatDate($date) {
    $year = substr($date, 6, 2);
            if(intval($year) <= 20) {
                $year = "20" . $year;
            } else {
                $year = "19" . $year;
            }
            $month = substr($date, 3, 2);
            $day = substr($date, 0, 2);

            
    return $year . "-" . $month . "-" . $day;
}
    






$conn->close();

    
?>
