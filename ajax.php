<?php
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
header('Content-type: application/json');

/* Load the settings from wp-config.php */
/* Note that just including the file adds about 100msec. So we'll parse it manually */

$dbhost = $dbname = $dbuser = $dbpw = 'undefined';
$all = file("../wp-config.php");

# Results per page
$rpp = 50;

if (!isset($_REQUEST['action'])) { $_REQUEST['action'] = 'getchar'; }
if (!isset($_REQUEST['char'])) { $_REQUEST['char'] = "A"; }
if (!isset($_REQUEST['name'])) { $_REQUEST['name'] = "eddi"; }
if (!isset($_REQUEST['page'])) { $_REQUEST['page'] = "1"; }

foreach($all as $line_num => $line) {
    if (preg_match("/define.'DB_NAME', '(.+)'/", $line, $m)) { $dbname = $m[1]; }
    if (preg_match("/define.'DB_HOST', '(.+)'/", $line, $m)) { $dbhost = $m[1]; }
    if (preg_match("/define.'DB_PASSWORD', '(.+)'/", $line, $m)) { $dbpw = $m[1]; }
    if (preg_match("/define.'DB_USER', '(.+)'/", $line, $m)) { $dbuser = $m[1]; }
}

$dbh = new PDO("mysql:host=$dbhost;dbname=$dbname", $dbuser, $dbpw, array( PDO::ATTR_PERSISTENT => true));

if ($_REQUEST['action'] === 'list') {
    $sql = 'select distinct(substr(derbyname, 1,1)) as initial from rollerderby_players order by initial';
    $query = $dbh->query($sql);
    $data = $query->fetchAll(PDO::FETCH_COLUMN, 0);
    print json_encode($data);
    exit;
}

if ($_REQUEST['action'] === 'getchar') {
    if (!is_numeric($_REQUEST['page'])) { 
        # Dear customer, fuck off. Sincerely, Rob.
        print "noint from ".$_REQUEST['page']."\n";
        exit;
    }
    $page = $_REQUEST['page'] - 1;
    $char = urldecode($_REQUEST['char']);
    if (strlen($char) != 1) {
        exit;
    }
    # Get how many rows there are
    $sql = "select count(derbyname) from rollerderby_players where derbyname like :char";
    $query = $dbh->prepare($sql);
    $query->execute(array( ":char" => $char."%"));
    $header = $query->fetchAll(PDO::FETCH_COLUMN, 0);

    # Now, get the page of results..
    $start = (int) $page*$rpp;
    $end = (int) (($page+1)*$rpp)-1;
    $sql = "select * from rollerderby_players where derbyname like :char limit $start, $end";
    $query = $dbh->prepare($sql);
    $query->execute(array( ":char" => $char."%"));
    $data = $query->fetchAll(PDO::FETCH_CLASS);
    # How many pages do we need?
    $pages = (int) $header[0]/$rpp;
    if ($pages === 0) {
        # Don't mention the war!
        print @json_encode(array("size" => $header[0], "data" => $data)); /* There is LOTS of bad UTF8 data in the scrape.. */
        exit;
    }
    $parr = range(1, $pages);
    print @json_encode(array("size" => $header[0], "pages" => $parr, "thispage" => $page+1, "data" => $data)); /* There is LOTS of bad UTF8 data in the scrape.. */
    exit;
}

if ($_REQUEST['action'] === 'search') {
    $char = $_REQUEST['name'];
    $sql = "select * from rollerderby_players where derbyname like concat('%', :char, '%')";
    $query = $dbh->prepare($sql);
    $query->execute(array( ":char" => $char));
    $data = $query->fetchAll(PDO::FETCH_CLASS);
    print @json_encode($data); /* There is LOTS of bad UTF8 data in the scrape.. */
    exit;
}
  

?>
