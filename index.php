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
//$ip  =  mysql_real_escape_string($_SERVER['REMOTE_ADDR']) ;   // get the IP address
$from_page = mysql_real_escape_string($_SERVER['HTTP_REFERER']) ;//  page from which visitor came
$uri = mysql_real_escape_string($_SERVER['REQUEST_URI']) ; //get uri
$language = mysql_real_escape_string($_SERVER['HTTP_ACCEPT_LANGUAGE']) ; //language code

// gets info for MySQL insert
$dumpersIP = ip_address_to_number($_SERVER['REMOTE_ADDR']);

// Converts $dumpduration into seconds
$dumpdurationsec = $dumpduration * 60;

// Text box colour
$h = $ip/4294967295;
$hsv = array($h, '1', $value);
$textboxcolor = HSVtoRGB($hsv);

?>
<html>
<head>
<title><?php echo $title; ?></title>
<script language="javascript" type="text/javascript">
function limitText(limitField, limitCount, limitNum) {
	if (limitField.value.length > limitNum) {
		limitField.value = limitField.value.substring(0, limitNum);
	} else {
		limitCount.value = limitNum - limitField.value.length;
	}
}
</script>
<style type="text/css">
	body {
		font-family: arial, verdana, sans-serif;
		background-color: #FEFEFE }
	.shadowtexttitle {
		text-shadow: 2px 2px 1px rgba(0,0,0,0.4);
		font-size:300% ;
	}
	.shadowtexttag {
		text-shadow: 1px 1px 1px rgba(0,0,0,0.4);
		font-size:100%
	}
</style>
<META HTTP-EQUIV="refresh" CONTENT="<?php echo $refreshrate; ?>">
</head>
<body style="background-image:url('<?php echo $headerimage; ?>'); background-repeat:no-repeat; background-position:right top;">
<center>
<font class="shadowtexttitle"><b><?php echo $title; ?></font></br>
<font class="shadowtexttag"><?php echo $tagline; ?></font><br>
<form name="dump" action="dump.php" method="POST">
<form name="myform">
	<textarea style="width:95%; color:#<?php echo $textboxcolor; ?>; border:2px solid #<?php echo $textboxcolor; ?>; font-weight:bold;" rows="8" name="limitedtextarea" onKeyDown="limitText(this.form.limitedtextarea,this.form.countdown,<?php echo $textlength; ?>);" 
onKeyUp="limitText(this.form.limitedtextarea,this.form.countdown,<?php echo $textlength; ?>);"></textarea><br>
	<font size="1">
		You have <input readonly type="text" name="countdown" size="3" value="<?php echo $textlength; ?>"> characters left.</br>
		Limit viewing to your IP <input type="checkbox" name="iplimit" value="1">
	</font>
	<INPUT TYPE=SUBMIT VALUE="Dump!"><br>
	<font size="1" color="#888">Text will fade away after <?php echo $dumpduration; ?> minutes</font><br>
</form>
</center>
<?php
$sql = 'SELECT *  FROM `displaydumps` WHERE timestamp > NOW() - INTERVAL '.$dumpduration.' MINUTE ORDER BY `timestamp` DESC';
$query = mysql_query($sql);
while($row = mysql_fetch_array($query)) {
if( $row['limitedviewing'] == "1" && $dumpersIP != $row['dumpersIP']) {
// dump not displayed because it is private
} else {

// gets time since dump
$displaytime = $row['sincetime'];

// gets uptime for HSV conversion
$uptime = time()-strtotime($row['timestamp']);

// Creates a number between 0 and 1 for saturation
$s = ($dumpdurationsec-$uptime)/$dumpdurationsec;

// converts hsv to RGB
$hsv = array($row['hue'], $s, $value);
$RGB = HSVtoRGB($hsv);

// places an astrix next to private messages
$star = "" ;
if( $row['limitedviewing'] == "1" ){
$star = " <i>(viewing limited to your public facing IP)</i>" ;
}

// puts a X for own posts
$X = "";
if( $row['dumpersIP'] == $dumpersIP ){
$X = '<a href="/del.php?d='.$row['dumpID'].'" style=color:#'.$RGB.';> X</a>' ;
}

// outputs dump with htmlentities removed and nl replaced with <br>
echo "	<p>\n";
echo "	<div align='right'>\n";
echo "		<font size='1' color='#".$RGB."'>dumped ".$displaytime." ago".$star."".$X."</font>\n";
echo "	</div>\n";
echo "	<hr size=2 color='#".$RGB."'>\n";
echo "		<font color='#".$RGB."'><PRE>".(makelinks(htmlentities($row['dumpedtext']),$RGB))."</PRE></font>\n";
echo "	</p>\n";
}
}


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

<hr size=2 color='#"555"'>
<div align='center'><font size="1" color="#888">This free service is brought to you by <a href="http://gho.st">Gho.st community web services</a>. Engine designed & developed by <a href="http://gregology.net">Gregology</a> with help from users of <a href="http://stackoverflow.com">Stack Overflow</a>. Please respect intellectual property. Enjoy!</font></div>
</body>
</html>
