#!/bin/sh
#
# Install Selenium, xvfb, Chrome and Chromedriver
#
#=========================================================
SELENIUM_URL=https://selenium-release.storage.googleapis.com/2.53/selenium-server-standalone-2.53.1.jar
CHROMEDRIVER_URL=http://chromedriver.storage.googleapis.com/2.25/chromedriver_linux64.zip
#=========================================================

cd /var/tmp

#=========================================================
echo "Install the packages..."
#=========================================================
sudo apt-get update
sudo apt-get -y install fluxbox xorg default-jre rungetty xvfb gtk2-engines-pixbuf xfonts-cyrillic xfonts-100dpi xfonts-75dpi xfonts-base xfonts-scalable imagemagick x11-apps

#=========================================================
echo "Download latest Selenium server..."
#=========================================================
wget $SELENIUM_URL -O selenium-server-standalone.jar
chown vagrant:vagrant selenium-server-standalone.jar

#=========================================================
echo "Download the latest Chrome..."
#=========================================================
wget "https://dl.google.com/linux/direct/google-chrome-stable_current_amd64.deb"
sudo dpkg -i google-chrome-stable_current_amd64.deb
sudo rm google-chrome-stable_current_amd64.deb
sudo apt-get install -y -f

#=========================================================
echo "Download latest chromedriver..."
#=========================================================
wget $CHROMEDRIVER_URL
unzip chromedriver_linux64.zip
sudo rm chromedriver_linux64.zip
chown vagrant:vagrant chromedriver

#=========================================================
echo "Apply workaround for Chrome..."
#=========================================================
# Make Chrome run with additional flags to resolve content scaling issues.
echo '#! /bin/bash' | sudo tee /opt/google/chrome/google-chrome-hack
echo '/opt/google/chrome/google-chrome --high-dpi-support=1 --force-device-scale-factor=1' | sudo tee --append /opt/google/chrome/google-chrome-hack
sudo chmod 755 /opt/google/chrome/google-chrome-hack
sudo mv /usr/bin/google-chrome-stable /usr/bin/google-chrome-stable.backup
cd /usr/bin && sudo ln -s /opt/google/chrome/google-chrome-hack google-chrome-stable
