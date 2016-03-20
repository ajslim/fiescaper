<?php

ini_set('error_reporting', -1);
ini_set('display_errors', 1);
ini_set('html_errors', 1); 


require_once("lib.php");


require "vendor/autoload.php";
use Masterminds\HTML5;


// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

$page = 1;
if(isset($_GET['page'])) {
    $page = $_GET['page'];
}

$category = "S";
if(isset($_GET['category'])) {
    $category = $_GET['category'];
}





function getTournamentListUrl($options = array()) {


    $category = assignIfSet($options,'category', "S");
    $weapon = assignIfSet($options,'weapon', "F");
    $gender = assignIfSet($options,'gender', "M");
    $compType = assignIfSet($options,'compType', "I");
    $year = assignIfSet($options,'year', "2016");
    $countryCode = assignIfSet($options,'country_code');
    $competitionCategory = assignIfSet($options,'competition_category');
    $startDate = assignIfSet($options,'start_date');
    $endDate = assignIfSet($options,'end_date');
    $page = assignIfSet($options,'page');


    return "http://fie.org/results-statistic/result?".
    "calendar_models_CalendarsCompetition%5BFencCatId%5D=$category"."&".
    "calendar_models_CalendarsCompetition%5BWeaponId%5D=$weapon"."&".
    "calendar_models_CalendarsCompetition%5BGenderId%5D=$gender"."&".
    "calendar_models_CalendarsCompetition%5BCompTypeId%5D=$compType"."&".
    "calendar_models_CalendarsCompetition%5BCompCatId%5D=$competitionCategory"."&".
    "calendar_models_CalendarsCompetition%5BCPYear%5D=$year"."&".
    "calendar_models_CalendarsCompetition%5BFedId%5D=$countryCode"."&".
    "calendar_models_CalendarsCompetition%5BDateBegin%5D=$startDate"."&".
    "calendar_models_CalendarsCompetition%5BDateEnd%5D=$endDate"."&".
    "calendar_models_CalendarsCompetition_page=$page";

}



function getTournamentsFromListPage($options = array()) {


    $results = array();
    $rows = getPageTableRows(getPage(getTournamentListUrl($options)));
    $year = $options['year'];


    foreach($rows as $row) {
        $tds = $row->getElementsByTagName('td');
    
        if(!empty($tds->item(2))) {
            $url = $tds->
                item(0)->
                getElementsByTagName('a')->
                item(0)->
                getAttribute('href');

            
            $id = explode("/",$url)[3];
            $name = $tds->item(0)->textContent;
            
            $place = $tds->item(1)->textContent;
            $country = $tds->item(2)->textContent;
            $startDate = reformatDate($tds->item(3)->textContent);
            $endDate = reformatDate($tds->item(4)->textContent);
            $weapon = $tds->item(5)->textContent;
            $gender = $tds->item(6)->textContent;
            $category = $tds->item(7)->textContent;
            $type = $tds->item(8)->textContent;
            $event = $tds->item(9)->textContent;

            
            
            

            $results[] = array(
                "place" => $place,
                "name" => $name,
                "country_code" => $country,
                "start_date" => $startDate,
                "end_date" => $endDate,
                "gender" => $gender,
                "category" => $category,
                "weapon" => $weapon,
                "type" => $type,
                "event" => $event,
                "year" => $year,
                "year_id" => $year."-".$id,
                "fie_site_id" => $id,
                "fie_site_url" => "http://fie.org".$url
            );
        }
           
    }
    return $results;

}


function getPlacingsFromTournamentPage($url) {


    $results = array();



    $rows = getPageTableRows(getPage($url));


    foreach($rows as $row) {
        $tds = $row->getElementsByTagName('td');
    
        if(!empty($tds->item(0))) {
            
            $rank = $tds->item(0)->textContent;
            $points =  $tds->item(1)->textContent;
            $url = $tds->
                item(2)->
                getElementsByTagName('a')->
                item(0)->
                getAttribute('href') . " ";

            
            $id = substr($url, strrpos($url, '/') + 1);

            $results[] = array(
               "rank" => $rank,
               "points" => $points,
               "fencer_fie_site_id" => $id
            );

        }
           
    }
    return $results;

}


function addPlacing($conn, $placing) {

    $tournament_year_id = assignIfSet($placing,'tournament_year_id', null);
    $fencer_fie_site_id = assignIfSet($placing,'fencer_fie_site_id', null);
    $rank = assignIfSet($placing,'rank', null);
    $points = assignIfSet($placing,'points', null);

    $sql = "INSERT INTO placings (" .
            "tournament_year_id, fencer_fie_site_id, rank, points" .
        ")". 
        "VALUES (" .
            "'$tournament_year_id', '$fencer_fie_site_id', '$rank', '$points'" .
        ") ".
        "ON DUPLICATE KEY UPDATE ".
        "rank='$rank', points='$points'";

    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully <br/>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}

function addTournament($conn, $tournament) {


    $fie_site_id = assignIfSet($tournament,'fie_site_id', null);
    $name = addslashes(assignIfSet($tournament,'name', null));
    $country_code = assignIfSet($tournament,'country_code', null);
    $start_date = assignIfSet($tournament,'start_date', null);
    $end_date = assignIfSet($tournament,'end_date', null);
    $gender = assignIfSet($tournament,'gender', null);
    $category = assignIfSet($tournament,'category', null);
    $weapon = assignIfSet($tournament,'weapon', null);
    $type = assignIfSet($tournament,'type', null);
    $event = assignIfSet($tournament,'event', null);
    $year = assignIfSet($tournament,'year', null);
    $yearId = assignIfSet($tournament,'year_id', null);

    //The fie number requires looking at a separate page for each fencer so we make this optional
    if(isset($fie_site_id)) {
        $sql = "INSERT INTO tournaments (" .
                "fie_site_id, year_id, year, name, country_code, start_date, end_date, gender, category, weapon, type, event" .
            ")". 
            "VALUES (" .
                "$fie_site_id, '$yearId', '$year', '$name', '$country_code', '$start_date', '$end_date', '$gender', '$category', '$weapon', '$type', '$event'" .
            ") ".
            "ON DUPLICATE KEY UPDATE ".
            "fie_site_id=$fie_site_id, year_id='$yearId', year='$year', name='$name', country_code='$country_code', start_date='$start_date', end_date='$end_date', gender='$gender', category='$category', weapon='$weapon', type='$type', event='$event'";
    } 
    if ($conn->query($sql) === TRUE) {
        echo "New record created successfully <br/>";
    } else {
        echo "Error: " . $sql . "<br>" . $conn->error;
    }
}



for($season = 2000; $season <= 2016; $season += 1) {
    $year = strval($season);
    $tournaments = getTournamentsFromListPage(array("year"=>$year));
     foreach($tournaments as $tournament) {

         addTournament($conn, $tournament);
         $placings = getPlacingsFromTournamentPage($tournament['fie_site_url']."/rank");


        echo $tournament['name'];
        foreach($placings as $placing) {
            $placing['tournament_year_id'] = $tournament['year_id'];

            addPlacing($conn, $placing);
        }
    }
}

$conn->close();

    
?>
