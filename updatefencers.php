<?php

ini_set('error_reporting', -1);
ini_set('display_errors', 1);
ini_set('html_errors', 1); 

require_once("lib.php");

require "vendor/autoload.php";
use Masterminds\HTML5;


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


function getFencerRankUrl($options = array()) {


    $category = assignIfSet($options,'category', "S");
    $weapon = assignIfSet($options,'weapon', "F");
    $gender = assignIfSet($options,'gender', "M");
    $compType = assignIfSet($options,'compType', "I");
    $year = assignIfSet($options,'year', "2016");
    $nationality = assignIfSet($options,'nationality');
    $lastName = assignIfSet($options,'lastname');
    $page = assignIfSet($options,'page');


    return "http://fie.org/results-statistic/ranking?".
    "result_models_Ranks%5BFencCatId%5D=$category"."&".
    "result_models_Ranks%5BWeaponId%5D=$weapon"."&".
    "result_models_Ranks%5BGenderId%5D=$gender"."&".
    "result_models_Ranks%5BCompTypeId%5D=$compType"."&".
    "result_models_Ranks%5BCPYear%5D=$year"."&".
    "result_models_Ranks%5BNationality%5D=$nationality"."&".
    "result_models_Ranks%5BLastName%5D=$lastName"."&".
    "&result_models_Ranks_page=$page";   
}



function getFencersFromRankingPage($options = array()) {


    $results = array();
    $rows = getPageTableRows(getPage(getFencerRankUrl($options)));

    foreach($rows as $row) {
        $tds = $row->getElementsByTagName('td');
    
        if(!empty($tds->item(2))) {
            $url = $tds->
                item(2)->
                getElementsByTagName('a')->
                item(0)->
                getAttribute('href') . " ";

            
            $id = substr($url, strrpos($url, '/') + 1);
            $name = $tds->item(2)->textContent . " ";
            $names = splitName($name);
            $firstName = $names['first_name'];
            $lastName = $names['last_name'];


            $country = $tds->item(3)->textContent. " ";
            $birthDate = $tds->item(4)->textContent. " ";
            
            
            $birthDate = reformatDate($birthDate);


            $results[] = array(
                "first_name" => $firstName,
                "last_name" => $lastName,
                "birth_date" => $birthDate,
                "country_code" => $country,
                "fie_site_id" => $id,
                "fie_site_url" => "http://fie.org".$url
            );
        }
           
    }
    return $results;

}

function getLicenseNumber($url) {
    $page = getPage($url);

    $html5 = new HTML5();
    $dom = $html5->loadHTML($page);

    $rows = array();
    foreach($dom->getElementsByTagName('div') as $div) {
        # Show the <a href>

        if($div->getAttribute("class") == "fencer-profile") {

            foreach($div->getElementsByTagName('li') as $listItem) {
                if(substr($listItem->textContent, 0, 8) === "license:") {
                    return trim(substr($listItem->textContent, 8));
                }
            }
        }

    }

    return null;
}



function addFencer($conn, $fencer) {


    $first_name = assignIfSet($fencer,'first_name', null);
    $last_name = assignIfSet($fencer,'last_name', null);
    $fie_site_id = assignIfSet($fencer,'fie_site_id', null);
    $country_code = assignIfSet($fencer,'country_code', null);
    $birth_date = assignIfSet($fencer,'birth_date', null);
    $fie_number = assignIfSet($fencer,'fie_number', null);


    $first_name = addslashes($first_name);
    $last_name = addslashes($last_name);

    //The fie number requires looking at a separate page for each fencer so we make this optional
    if(isset($fie_number)) {
        $sql = "INSERT INTO fencers (fie_site_id, first_name, last_name, country_code, birth_date, fie_number)". 
        "VALUES ($fie_site_id, '$first_name', '$last_name', '$country_code', '$birth_date', '$fie_number') ON DUPLICATE KEY UPDATE ".
        "fie_site_id=$fie_site_id, first_name='$first_name', last_name='$last_name', country_code='$country_code', birth_date='$birth_date', fie_number='$fie_number'";
    } else {
        $sql = "INSERT INTO fencers (fie_site_id, first_name, last_name, country_code, birth_date)". 
        "VALUES ($fie_site_id, '$first_name', '$last_name', '$country_code', '$birth_date') ON DUPLICATE KEY UPDATE ".
        "fie_site_id=$fie_site_id, first_name='$first_name', last_name='$last_name', country_code='$country_code', birth_date='$birth_date'";
    }
    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully <br/>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

function hasFieNumber($conn, $fie_site_id) {
    $sql = "SELECT * FROM fencers WHERE fencers.fie_site_id = $fie_site_id and fencers.fie_number IS NOT NULL";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        return true;
    } else {
        return false;
    }
}

for ($page = 1; $page <= 28; $page++) {
    $fencers = getFencersFromRankingPage(array("page"=>$page));

    foreach($fencers as $fencer) {
        if(!hasFieNumber($conn, $fencer['fie_site_id'])) {
            $fencer['fie_number'] = getLicenseNumber($fencer['fie_site_url']);
            echo $fencer['first_name'] . " ". $fencer['last_name'] . " " .$fencer['fie_number'] . "<br/>";
            addFencer($conn, $fencer);
        }
    }

}




$conn->close();

    
?>
