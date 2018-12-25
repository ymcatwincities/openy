#!/bin/sh
#
# Start local selenium server.
#
# Usage:
# selenium-start.sh
#
#=========================================================
SELENIUM_JAR=/var/tmp/selenium-server-standalone.jar
CHROMEDRIVER=/var/tmp/chromedriver
CHROMEBIN=/usr/bin/google-chrome
DISPLAYNUM=10

#=========================================================
echo "Starting selenium..."
sudo Xvfb :$DISPLAYNUM -screen 0 1024x768x24 -ac &
DISPLAY=:$DISPLAYNUM xvfb-run java -jar $SELENIUM_JAR -trustAllSSLCertificates -Dwebdriver.chrome.driver=$CHROMEDRIVER -Dwebdriver.chrome.bin=$CHROMEBIN >/dev/null 2>/dev/null
