#!/usr/openwin/bin/tcsh

set str = ""
# Parse the inputs

setenv PGPLOT_PNG_WIDTH 800
setenv PGPLOT_PNG_HEIGHT 550

setenv PGPLOT_FONT /var/www/vhosts/pulseatparkes.atnf.csiro.au/apps/NE2001/bin.NE2001/grfont.dat
setenv PGPLOT_DIR /var/www/vhosts/pulseatparkes.atnf.csiro.au/apps/NE2001/bin.NE2001/

echo "/var/www/vhosts/pulseatparkes.atnf.csiro.au/htdocs/plotSky $argv[1]"

/var/www/vhosts/pulseatparkes.atnf.csiro.au/htdocs/plotSky $argv[1]
