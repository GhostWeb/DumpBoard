<?php
$path_to_bb = '/var/www/bad-behavior';
require_once("$path_to_bb/bad-behavior-generic.php");

// Start time
$time = microtime();
$time = explode(" ", $time);
$time = $time[1] + $time[0];
$start = $time;

session_start();

// Includes the settings from the config file
include("config.php");
include("functions.php");

// Opens database connections
$conn = mysql_connect($dbhost, $dbuser, $dbpass) or die ('Error connecting to mysql');
mysql_select_db($dbname);

//collect information for tracker
$browser  = mysql_real_escape_string($_SERVER['HTTP_USER_AGENT']) ; // get the browser name
$ip = ip_address_to_number($_SERVER['REMOTE_ADDR']);
$from_page = mysql_real_escape_string($_SERVER['HTTP_REFERER']) ;//  page from which visitor came
$uri = mysql_real_escape_string($_SERVER['REQUEST_URI']) ; //get uri
$language = mysql_real_escape_string($_SERVER['HTTP_ACCEPT_LANGUAGE']) ; //language code

// gets info for MySQL insert
$dumpersIP = ip_address_to_number($_SERVER['REMOTE_ADDR']);
$dumpedtext = mysql_real_escape_string($_POST['limitedtextarea']);
$limitedviewing = $_POST['iplimit'];

// stops no language with http
if (strpos($dumpedtext, 'http') !== true) {
if($language ==''){
die ('No links because of naughty spam bots :(');
}
}

// stops empty posts
if($dumpedtext == ""){
// Nothing to input
} else
{
// checks dump limits
$sql = 'SELECT dumpersIP, COUNT(dumpersIP) FROM `dumps` WHERE dumpersIP = "'.$dumpersIP.'" AND timestamp > NOW() - INTERVAL '.$limitperiod.' MINUTE';
$query = mysql_query($sql);
while($row = mysql_fetch_array($query)) {
if( $row['COUNT(dumpersIP)'] > $dumplimit ) {
// if dump limits exceded it creates error message for display below
echo "<font size='3' color='red'>Dump limited exceded from your IP address</font><br>";
exit;
} else {
// if dump limit not exceded it enters the dump
$query_insert  ="INSERT INTO dumps (dumpersIP,dumpedtext,limitedviewing) VALUES ('$dumpersIP','$dumpedtext','$limitedviewing')" ;
$result=mysql_query ( $query_insert);
if(!$result){
die(mysql_error());
}
}
}
}

header("Location: /");

// adds data to db
$time = microtime(); // gets current time
$time = explode(" ", $time);
$time = $time[1] + $time[0];
$finish = $time;
$totaltime = ($finish - $start); // figures out time
//Insert the data in the table...
$query_insert  ="INSERT INTO webstats
(browser,ip,uri,from_page,language,loadtime) VALUES
('$browser','$ip','$uri','$from_page','$language','$totaltime')" ;
$result=mysql_query ( $query_insert);
if(!$result){
die(mysql_error());
}

?>
