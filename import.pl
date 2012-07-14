#!/usr/bin/perl -ws

use Data::Dumper;
use DBI;

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
close WP;

if ($dbname eq 'undefined') { die "Unable to find dbname in ../wp-config.php"; }
if ($dbhost eq 'undefined') { die "Unable to find dbhost in ../wp-config.php"; }
if ($dbpw eq 'undefined') { die "Unable to find dbpw in ../wp-config.php"; }
if ($dbuser eq 'undefined') { die "Unable to find dbuser in ../wp-config.php"; }

# Connect to the database
my $dbh =  DBI->connect("DBI:mysql:$dbname:$dbhost", $dbuser, $dbpw) || die "Unable to connect: $DBI::errstr\n";

# Check to make sure the databases exist
&checkdb($dbh);

# Process TwoEvils
open (FH, "teams.html");
my @teams;
while (<FH>) {
    if (/^<tr class=.trc..><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><.tr>/) {
        push @teams, {"league", $1, "teamname", $2, "dateadded", $3, "teamtype", $4, "misc", $5};
    }
}
close FH;
&loadTwoEvilsTeams(@teams);



sub loadTwoEvilsTeams($) {
    my @teams = @_;

    # Dump any data we previously had for twoevils
    $dbh->do('delete from rollerderby_teams where registrar="twoevils"');
    my $sth = $dbh->prepare('insert into rollerderby_teams (league, teamname, dateadded, teamtype, misc, registrar) values (?, ?, ?, ?, ?, "twoevils")') 
        or die $dbh->errstr;
    while (my $tmp = shift @teams) {

        # Try to sanitise the date
        my $fixeddate = 'Unknown';
        if ($tmp->{'dateadded'} =~ /(\d+)-(\d+)-(\d+)/) {
            # OK, this looks like a date. They're MDY, so lets fix that.
            my ($m, $d, $y) = ($1, $2, $3);
            # Sometimes they're two digit, sometimes they're three digit.
            if ($y < 100) { $y = $y + 2000 };
            $fixeddate = sprintf('%04d', $y)."-".sprintf('%02d', $m)."-".sprintf('%02d', $d);
        }
        #print "Date is ".$tmp->{'dateadded'}." - fixed to $fixeddate\n";

        # Sanitize Leagues
        #   Some UTF8 errors have crept into the twoevils data.
        $tmp->{'league'} =~ s/Z.rich/Zürich/;
        #   Tucson has two lines. Just drop everything after the <br>
        $tmp->{'league'} =~ s/<br>.+//g;


        # Sanitize Teams
        #   If team is '&nbsp;', set it to the leage name
        if ($tmp->{'teamname'} eq '&nbsp;') {
            $tmp->{'teamname'} = $tmp->{'league'}
        }
        #   Microsoft Word strikes again..
        $tmp->{'teamname'} =~ s/The Daisy Cutter.s/The Daisy Cutters/;

        # Check teamtype
        $tmp->{'teamtype'} =~ s/&nbsp;//;
        #   Wups, typo on Central Chaos
        $tmp->{'teamtype'} =~ s/Leage/League/;

        # Remove possible XSS issues. Yes, I'm paranoid.
        foreach $var (qw(league teamname teamtype misc)) {
            $tmp->{$var} =~ s/(<|>)//g;
        }
        # That appears to be it. Load it in!
        $sth->execute($tmp->{'league'}, $tmp->{'teamname'}, $fixeddate, $tmp->{'teamtype'}, $tmp->{'misc'});
    }
}


sub checkdb($) {
    my $dbh = shift @_;
    if (!&table_exists($dbh, "rollerderby_teams")) {
        print "twoevils_teams does not exist. Creating\n";
        $dbh->do(q(CREATE TABLE rollerderby_teams (
            `league` VARCHAR(64),
            `teamname` VARCHAR(64),
            `dateadded` VARCHAR(12),
            `teamtype` VARCHAR(64),
            `misc` VARCHAR(10),
            `registrar` VARCHAR(64))));
    }
    if (!&table_exists($dbh, "rollerderby_players")) {
        $dbh->do(q(CREATE TABLE rollerderby_players (
            `derbyname` VARCHAR(64),
            `teamname` VARCHAR(64),
            `number` VARCHAR(16),
            `league` VARCHAR(64),
            `dateadded` VARCHAR(12),
            `registrar` VARCHAR(64))));
    }
}

sub table_exists {
    my ($dbh,$table_name) = @_;

    my $sth = $dbh->table_info(undef, undef, $table_name, 'TABLE');
    $sth->execute;
    my @info = $sth->fetchrow_array;

    my $exists = scalar @info;
    return $exists;
}
