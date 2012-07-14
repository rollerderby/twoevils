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
if (!isset($_REQUEST['page'])) { $_REQUEST['page'] = 1; }

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
    $page = $_REQUEST['page'];
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
    $start = intval(($page-1)*$rpp);
    $sql = "select * from rollerderby_players where derbyname like :char order by derbyname limit $start, $rpp";
    $query = $dbh->prepare($sql);
    $query->execute(array( ":char" => $char."%"));
    $data = $query->fetchAll(PDO::FETCH_CLASS);
    # How many pages do we need?
    $pages = intval($header[0]/$rpp)+1;
    if ($pages === 0) {
        # Don't mention the war!
        print @json_encode(array("size" => $header[0], "data" => $data)); /* There is LOTS of bad UTF8 data in the scrape.. */
        exit;
    }

    # Right. Lets assume we have 78 pages, and we're on page.. 12.
    # We want 3 pages on either side of where we are, and then back and forward to the next and previous two multiples of 10.
    # We also want the last page number, too, if next multiple of ten is > npages.
    # So we should have << Previous | Next >> 1 9 10 11 /12/ 13 14 15 ... 20 30 78 
    # Same for the first.
    # So. We'll start by sticking our current page on the array
    $parr = array($page);
    # Now, if $page <= 3, we just want 1.2.3
    if ($page != 1) {
        if ($page <= 3) {
             $parr = array_merge(range(1, $page-1), $parr);
        } else {
             $parr = array_merge(range($page-3, $page-1), $parr);
        }
    }
    # Right. Now, lets add the next three on the end.
    if ($page == $pages) {
        # Already there
    } elseif ($page+3 >= $pages) {
        $parr = array_merge($parr, range($page+1, $pages));
    } else {
        $parr = array_merge($parr, range($page+1, $page+4));
    }

    # OK, so now we have 9 10 11 /12/ 13 14 15. Lets add the next 10 down.
    if ($page <= 4) {
        # Already there
    } elseif ($page <= 13) {
        $parr = array_merge(array(1), $parr);
    } else {
        $parr = array_merge(array(($page-4) - (($page - 4)%10)), $parr);
    }

    # And the next 10 up
    if ($page == $pages || $page+4 >= $pages) {
        # Already there
    } else {
        $next = $page+4 -($page+4)%10 + 10;
        if ($next > $pages) {
            $parr = array_merge($parr, array($pages));
        } else {
            $parr = array_merge($parr, array($next));
        }
    }

    print @json_encode(array("size" => $header[0], "pages" => $parr, "pagecount" => $pages, "thispage" => $page, "data" => $data)); /* There is LOTS of bad UTF8 data in the scrape.. */
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
