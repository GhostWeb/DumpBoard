<?php
  $path_to_bb = '/var/www/bad-behavior';
  require_once("$path_to_bb/bad-behavior-generic.php");

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

  $trackingscript = file_get_contents('trackscript.html');

  $head = "<html>
  <head>
    <title>$title</title>
    <script src='js/vendor/custom.modernizr.js'></script>
    <script type='text/javascript' src='ZeroClipboard.js'></script>
    <script language='JavaScript'>
      var clip = null;

      function $(id) { return document.getElementById(id); }

      function init() 
      {
        clip = new ZeroClipboard.Client();
        clip.setHandCursor( true );
      }

      function move_swf(ee)
      {    
        copything = document.getElementById(ee.id + ' text').innerHTML;
        clip.setText(copything);

          if (clip.div)
          {	  
            clip.receiveEvent('mouseout', null);
            clip.reposition(ee.id);
          }
          else{ clip.glue(ee.id);   }
 
        clip.receiveEvent('mouseover', null);
 
   }    
   
    </script>
    <script language='javascript' type='text/javascript'>
      function limitText(limitField, limitCount, limitNum) {
      if (limitField.value.length > limitNum) {
      limitField.value = limitField.value.substring(0, limitNum);
      } else {
      limitCount.value = limitNum - limitField.value.length;
      }
      }
    </script>
    <style type='text/css'>
	body {
		background-color: #FEFEFE }
	.shadowtexttitle {
		text-shadow: 2px 2px 1px rgba(0,0,0,0.4);
		font-size:300% ;
	}
	.shadowtexttag {
		text-shadow: 1px 1px 1px rgba(0,0,0,0.4);
		font-size:100%
	}
	pre {
	white-space: pre-wrap;       /* css-3 */
	white-space: -moz-pre-wrap;  /* Mozilla, since 1999 */
	white-space: -pre-wrap;      /* Opera 4-6 */
	white-space: -o-pre-wrap;    /* Opera 7 */
	word-wrap: break-word;       /* Internet Explorer 5.5+ */
	}
    </style>

    <link rel='stylesheet' href='stylesheets/app.css' />

    <META HTTP-EQUIV='refresh' CONTENT='$refreshrate'>
    $trackingscript
  </head>
  <body style='background-image:url($headerimage); background-repeat:no-repeat; background-position:right top;' onload='init();'>
    <center>
    <font class='shadowtexttitle'><b>$title</font></br>
    <font class='shadowtexttag'>$tagline</font><br>
    <form name='dump' action='dump.php' method='POST'>
    <form name='myform'>
    <textarea style='width:95%; color:#$textboxcolor; border:2px solid #$textboxcolor; font-weight:bold;' rows='8' name='limitedtextarea' onKeyDown='limitText(this.form.limitedtextarea,this.form.countdown,$textlength);' 
onKeyUp='limitText(this.form.limitedtextarea,this.form.countdown,$textlength);'></textarea><br>
    <font size='1'>
      You have <input readonly type='text' name='countdown' size='3' value='$textlength'> characters left.</br>
      Limit viewing <input type='checkbox' name='iplimit' title='Limit viewing to your external facing IP address' value='1'>
    </font>
    <INPUT TYPE=SUBMIT VALUE='Dump!'><br>
    <font size='1' color='#888'>Text will fade away after $dumpduration minutes</font><br>
    </form>
    </center>";

  $sql = 'SELECT *  FROM `displaydumps` WHERE timestamp > NOW() - INTERVAL '.$dumpduration.' MINUTE ORDER BY `timestamp` DESC';
  $query = mysql_query($sql);
  while($row = mysql_fetch_array($query)) {
    if( $row['limitedviewing'] == 1 && $dumpersIP != $row['dumpersIP']) {
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

      // rawID for short raw url
      $rawID = substr($row['dumpID'], -2);
      $dumpID = $row['dumpID'];

      // Display text
      $displaytext = (makelinks(htmlentities($row['dumpedtext']),$RGB));

      // places an astrix next to private messages
      $star = "" ;
      if( $row['limitedviewing'] == 1 ){
        $star = " <i>{viewing limited to your public facing IP}</i>" ;
      }

      // puts a X for own posts
      $X = "";
      if( $row['dumpersIP'] == $dumpersIP ){
        $X = " <a href='/ref.php?r=$dumpID' title='Repost this dump' style=color:#$RGB;>^</a>  <a href='/del.php?d=$dumpID' title='Delete this dump' style=color:#$RGB;>X</a>" ;
      }

      // outputs dump with htmlentities removed and nl replaced with <br>
      $dumps = "$dumps
    <p>
    <div align='right'>
      <font size='1' color='#$RGB'>dumped $displaytime ago$star (<a href='/$rawID' title='Raw text id, useful for curl on *nix' style=color:#$RGB;>$rawID</a>) <a id='$dumpID' title='Copy dump' onMouseOver='move_swf(this);return false;'><u>C</u></a>$X</font>
    </div>
    <hr size=2 color='#$RGB'>
      <font color='#$RGB'>
        <PRE><p id='$dumpID text'>$displaytext</p></PRE>
      </font>
    </p>";
    }
  }

    $footer = "
    <hr size=2 color='#555'>
    <div align='center'><font size='1' color='#888'>This free service is brought to you by <a href='http://gho.st'>Gho.st community web services</a>, <a href='https://github.com/GhostWeb/DumpBoard'>fork me!</a> Engine designed & developed by <a href='http://gregology.net'>Gregology</a>. Please respect intellectual property. Enjoy!</font></div>
  </body>
</html>";

  echo $head;
  echo $dumps;
  echo $footer;

?>
