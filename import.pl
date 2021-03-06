#!/usr/bin/perl -ws

use Data::Dumper;
use DBI;

my $debug=0;

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
        push @teams, {"league", $1, "teamname", $2, "dateadded", $3, "teamtype", $4, "misc", $5, "registrar", "twoevils"};
    }
}
close FH;
open (FH, "rollergirls.html");
my @players;
while (<FH>) {
    if (/^<tr class=.trc..><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><td>(.+)<.td><.tr>/) {
        push @players, {"name", $1, "number", $2, "dateadded", $3, "league", $4, "registrar", "twoevils"};
    }
}


&loadAustralia;
&loadPlayers(@players);
#&loadTeams(@teams);

sub loadPlayers($) {
    my @players = @_;

    # Drop all the player names
    $dbh->do('delete from rollerderby_players');

    my $sth = $dbh->prepare('insert into rollerderby_players (derbyname, league, dateadded, number, registrar) values (?, ?, ?, ?, ?)') 
        or die $dbh->errstr;

    while (my $tmp = shift @players) {
        # Try to sanitise the date
        # They've fixed it. Woot.
        my $fixeddate = $tmp->{'dateadded'};
#        if ($tmp->{'dateadded'} =~ /(\d+)-(\d+)-(\d+)/) {
#            print "I'm looking at ".$tmp->{'dateadded'}."\n";
#            # OK, this looks like a date. They're MDY, so lets fix that.
#            my ($m, $d, $y) = ($1, $2, $3);
#            # Sometimes they're two digit, sometimes they're three digit.
#            if ($y < 100) { $y = $y + 2000 };
#            $fixeddate = sprintf('%04d', $y)."-".sprintf('%02d', $m)."-".sprintf('%02d', $d);
#        }

        # Remove possible XSS issues. Yes, I'm paranoid.
        foreach $var (qw(name league dateadded number)) {
            $tmp->{$var} =~ s/(<|>|&gt;|&lt;)//g;
            $tmp->{$var} =~ s/‘|’|′/'/g;
            $tmp->{$var} =~ s/“|”/"/g;
        }
        # That appears to be it. Load it in!
        $sth->execute($tmp->{'name'}, $tmp->{'league'}, $fixeddate, $tmp->{'number'}, $tmp->{'registrar'});
    }
}

sub loadTeams($) {
    my @teams = @_;

    # Dump any data we previously had.
    $dbh->do('delete from rollerderby_teams');

    my $sth = $dbh->prepare('insert into rollerderby_teams (league, teamname, dateadded, teamtype, misc, registrar) values (?, ?, ?, ?, ?, ?)') 
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
            $tmp->{$var} =~ s/(<|>|&gt;|&lt;)//g;
        }
        # That appears to be it. Load it in!
        $sth->execute($tmp->{'league'}, $tmp->{'teamname'}, $fixeddate, $tmp->{'teamtype'}, $tmp->{'misc'}, $tmp->{'registrar'});
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
            `league` VARCHAR(64),
            `dateadded` VARCHAR(12),
            `number` VARCHAR(16),
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

sub loadAustralia {
    # Process Australia
    open (FH, "australia.html");
    # This is a bit harder than twoevils, as they have multiple lines per skater.
    while (<FH>) {
        my $playertype=$playername=$number=$league = 'undefined';
        if (/^<tr>$/) {
            # This may be the start of a player profile, OR, the header.
            # First line is the category.
            my $line = <FH>;
            if ($line =~ /<td align="LEFT" height="\d+">(.+)<.td>/) {
                $playertype = $1;
                print "Found $playertype" if ($debug);
            } elsif ($line =~ /<td align="LEFT" width="100" height="22"><strong>/) {
                # Header. ignore.
            } else {
                print "Odd stuff. $line\n";
                exit;
            }

            # Second line is derby name
            $line = <FH>;
            if ($line =~ /<td align="LEFT" width="221"><strong>/) {
                # Header. ignore.
            } elsif ($line =~ /<td align="LEFT".*>(.+)<.td>/) {
                $playername = $1;
                # Now, clean up any </span> or </a> on there. 
                $playername =~ s/<.+>$//;
                # Undo HTML mangling
                $playername =~ s/&#821[67];/'/g;
                $playername =~ s/&#8242;/'/g;
                $playername =~ s/&#8211;/-/g;
                $playername =~ s/&amp;/&/g;
                print ", $playername" if ($debug);
            } else {
                print "Odd stuff. $line\n";
                exit;
            }

            # Third line is number
            $line = <FH>;

            # Ah. Turns out there are some single lines that split up over two lines. YAY!
            if ($line !~ /td>$/) {
                chomp($line);
                $line .= <FH>;
            }

            if ($line =~ /<td align="CENTER.*><strong>(.+)<.td>/) {
                # Header. Ignore.
            } elsif ($line =~ /<td align=".*>(.+)<.td>/) {
                $number = $1;
                # Strip off any HTML left over...
                $number =~ s/<.+>$//;
                $number =~ s/&#215;/x/g;
                $number =~ s/&amp;/&/g;
                print ", $number" if ($debug);
            } elsif ($line =~ /<td align=".+"><.td>/) {
                $number = "";
                print ", (Blank)" if ($debug);
            } else {
                print "Odd stuff. $line\n";
                exit;
            }
    
            # Fourth line is League. 
            $line = <FH>;
            if ($line =~ /<td align=".*><strong>(.+)<.td>/) {
                # Header. Ignore.
            } elsif ($line =~ /<td align=".*>(.+)<.td>/) {
                $league = $1;
                $league =~ s/<.a>//;
                print ", $league\n" if ($debug);
            }
    
            # We're done! Load that up.
            if ($playername ne "undefined") {
                push @players, {"name", $playername, "number", $number, "dateadded", "Unknown", "league", $league, "registrar", "rollerderbyau"};
            }
        }
    }
}

