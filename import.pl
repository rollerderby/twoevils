#!/usr/bin/perl -ws

use Data::Dumper;
#use LWP::Simple;
#
# So. lets grab the twoevils site, and stick it in a temp file.
#
# .. disabled for the moment. Just use the imported files

# Get database permissions from Wordpresses wp-config.php. 
#
open (WP, "../wp-config.php");

my ($dbname, $dbhost, $dbuser, $dbpw) = 'undefined'; 
while (<WP>) {
    if ( /define.'DB_NAME', '(.+)'/) { $dbname = $1; }
    if ( /define.'DB_HOST', '(.+)'/) { $dbhost = $1; }
    if ( /define.'DB_PASSWORD', '(.+)'/) { $dbpw = $1; }
    if ( /define.'DB_USER', '(.+)'/) { $dbuser = $1; }
}

print "I have $dbname, $dbuser, $dbhost, $dbpw\n";

# Grab teams into an array.
open (FH, "teams.html");
my @teams;
while (<FH>) {
    if (/^<tr class=.trc..><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><.tr>/) {
        push @teams, {"league", $1, "teamname", $2, "dateadded", $3, "teamtype", $4, "misc", $5};
    }
}

#print Dumper(@teams);
