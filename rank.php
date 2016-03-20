<?php

ini_set('error_reporting', -1);
ini_set('display_errors', 1);
ini_set('html_errors', 1); 

require_once("lib.php");

require "vendor/autoload.php";
use Masterminds\HTML5;

$startDate = "2015-01-01";
$endDate = "2016-01-01";
$types = "GP,A";
$rankBy = "average_rank";

if(isset($_GET['startDate']) && isset($_GET['endDate']) ) {
    $startDate = $_GET['startDate'];
    $endDate = $_GET['endDate'];
}

if(isset($_GET['types'])) {
    $types = $_GET['types'];
}

if(isset($_GET['rankBy'])) {
    $rankBy = $_GET['rankBy'];
}

function getTournamentsSql($startDate, $endDate, $types) {

    $types = "'".str_replace(",", "','", $types)."'";

    $sql = "SELECT * FROM tournaments WHERE 
        start_date BETWEEN '$startDate' AND '$endDate' AND type IN($types)";

    return $sql;
}
    

function getRankSql($startDate, $endDate, $types, $rankBy = "average_rank") {


    $orderBy = "average_rank ASC";
    if($rankBy == "points") {
        $orderBy = "total_points DESC";
    }

    $tournamnents = getTournamentsSql($startDate, $endDate, $types);

    $sql = "SELECT 
        fencer_id,
        fencer_first_name,
        fencer_last_name,
        fencer_country_code,
        COUNT(rank) as tournaments,
        AVG(rank) as average_rank,
        STDDEV(rank)/SQRT(AVG(rank)) as stddev_rank,
        SUM(points) as total_points



        FROM
        (SELECT 
        fencers.fie_site_id as fencer_id,
        fencers.first_name as fencer_first_name,
        fencers.last_name as fencer_last_name,
        fencers.country_code as fencer_country_code,
        placings.rank as rank,
        placings.points as points        
        FROM 
        fencers 
        LEFT JOIN 
        (SELECT * FROM placings WHERE rank != 9999) as placings 
        ON fencers.fie_site_id = placings.fencer_fie_site_id 
        INNER JOIN 
        ($tournamnents) as tournaments
        ON placings.tournament_year_id = tournaments.year_id) as fencer_results
        GROUP BY fencer_id, fencer_last_name
        ORDER BY $orderBy";

        //Add rank
        $sql = "SELECT 
            @rn:=@rn+1 AS world_rank,
            fencer_id,
            fencer_first_name,
            fencer_last_name,
            fencer_country_code,
            average_rank,
            stddev_rank,
            tournaments,
            total_points
            FROM ($sql) as t1,(SELECT @rn:=0) t2";

    return $sql;
}

function getCountryRankSql($sql) {
    $sql = "SELECT
            world_rank,
            fencer_first_name as first_name,
            fencer_last_name as last_name,
            fencer_country_code as country,
            total_points,
            tournaments,
            average_rank,
            stddev_rank,
            ( 
                CASE fencer_country_code 
                WHEN @curType 
                THEN @curRow := @curRow + 1 
                ELSE @curRow := 1 AND @curType := fencer_country_code END
              ) + 1 AS national_rank,
            fencer_id
                        
            

            FROM      
                (SELECT * FROM ($sql) as world_ranking ORDER BY fencer_country_code, world_rank ASC) as world_ranking_by_countries,
                (SELECT @curRow := 0, @curType := '') r";

    //Reorder by average results
    $sql = "SELECT * FROM ($sql) as national_rankings ORDER BY world_rank ASC";

    return $sql;

}


function getRanks($startDate, $endDate, $types, $rankBy) {
    global $servername;
    global $username;
    global $password;
    global $dbname;


    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    } 

    $sql = getCountryRankSql(getRankSql($startDate, $endDate, $types, $rankBy));
    $result = $conn->query($sql);

    $conn->close();
    return $result;
}

$result = getRanks($startDate, $endDate, $types, $rankBy);


if ($result->num_rows > 0) {

        echo "<table>";


        $finfo = $result->fetch_fields();

        echo "<tr>";
        foreach ($finfo as $val) {
            echo "<th>".$val->name."</th>";
        }
        echo "</tr>";

        // output data of each row
        while($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach($row as $item) {
                echo "<td>" . $item. "</td>";    
            }
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "0 results";
    }

?>
