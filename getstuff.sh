#!/bin/bash

rm -f rollergirls.html
wget 'http://www.twoevils.org/rollergirls/' -O rollergirls.html

rm -f teams.html
wget 'http://www.twoevils.org/rollergirls/teams.cgi' -O teams.html

