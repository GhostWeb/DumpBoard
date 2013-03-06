<?php
$path_to_bb = '/var/www/bad-behavior';
require_once("$path_to_bb/bad-behavior-generic.php");

session_start();

// Includes the settings from the config file
include("config.php");
include("functions.php");

$ip = ip_address_to_number($_SERVER['REMOTE_ADDR']);

// Opens database connections
$conn = mysql_connect($dbhost, $dbuser, $dbpass) or die ('Error connecting to mysql');
mysql_select_db($dbname);

$dumpID = substr(mysql_real_escape_string($_GET['dump']), -2);

$sql = "SELECT * FROM  `displaydumps` WHERE SUBSTRING( dumpID, -2 ) =  '".$dumpID."' ORDER BY  `displaydumps`.`dumpID` DESC LIMIT 0 , 1";
$query = mysql_query($sql);
while($row = mysql_fetch_array($query)) {
if( $row['limitedviewing'] == "1" && $ip != $row['dumpersIP']) {
} else {
echo $row['dumpedtext'];
}
}

?>

