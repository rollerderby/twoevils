<?php

/* Load the settings from wp-config.php */
/* Note that just including the file adds about 100msec. So we'll parse it manually */

$dbhost = $dbname = $dbuser = $dbpw = 'undefined';
$all = file("../wp-config.php");

foreach($all as $line_num => $line) {
    if (preg_match("/define.'DB_NAME', '(.+)'/", $line, $m)) { $dbname = $m[1]; }
    if (preg_match("/define.'DB_HOST', '(.+)'/", $line, $m)) { $dbhost = $m[1]; }
    if (preg_match("/define.'DB_PASSWORD', '(.+)'/", $line, $m)) { $dbpw = $m[1]; }
    if (preg_match("/define.'DB_USER', '(.+)'/", $line, $m)) { $dbuser = $m[1]; }
}

$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpw, array( PDO::ATTR_PERSISTENT => true));

/* We should have the stuff typed in _REQUEST['term']. */
if(isset($_REQUEST['term'])) {
      $r = $_REQUEST['term'];
      if (preg_match('/>|</', $r)) {
          print json_encode(array('Error. Invalid Chars'));
          exit;
      }
      print json_encode(array("I have $r"));
  }
  
?>
