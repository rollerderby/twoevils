#!/bin/bash

# TwoEvils
rm -f rollergirls.html
wget 'http://www.twoevils.org/rollergirls/' -O rollergirls.html
rm -f teams.html
wget 'http://www.twoevils.org/rollergirls/teams.cgi' -O teams.html

# Australia Rollerderby Registrar
rm -f au.html
wget 'http://rollerderbyau.net/names-roster/' -O australia.html

