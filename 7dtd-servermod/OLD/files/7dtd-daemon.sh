#!/bin/bash
export LD_LIBRARY_PATH=.

# Create gameserver directory, if it doesn't exist
if [[ ! -d $INSTALL_DIR ]]; then
  mkdir -p $INSTALL_DIR/html;
  echo "<html><head> <meta http-equiv=\"refresh\" content=\"30\" /></head><body><center>7DaysToDie is currently installing in the background.<br><br>This page will automatically refresh every 30 seconds until the server is installed.<center></body></html>" > $INSTALL_DIR/html/index.php
fi
cd $INSTALL_DIR

# Update Steam application
steamcmd +quit

# Make sure this script isn't hung from a previous instantiation.
if [[ `ps awwux | grep -v grep | grep 7dtd-daemon | wc -l` > 2 ]]; then exit; fi
# Install 7DTD gameserver, if it isn't already installed.
while true; do
  if [ -f /7dtd.initialized ]; then break; fi;

  # Set up the installation directory
  [[ ! -d $INSTALL_DIR/.local ]] && mkdir -p $INSTALL_DIR/.local;
  [[ ! -d $INSTALL_DIR/Mods ]] && mkdir -p $INSTALL_DIR/Mods;
  rm -rf /root/.steam/.local && ln -s $INSTALL_DIR/.local /root/.steam/.local

  # Set up extra variables we will use, if they are present
  [ -z "$STEAMCMD_NO_VALIDATE" ]   && validate="validate"
  [ -n "$STEAMCMD_BETA" ]          && beta="-beta $STEAMCMD_BETA"
  [ -n "$STEAMCMD_BETA_PASSWORD" ] && betapassword="-betapassword $STEAMCMD_BETA_PASSWORD"

  echo "Starting Steam to perform 7DTD game server install into $INSTALL_DIR"
  steamcmd +login anonymous +force_install_dir $INSTALL_DIR +app_update 294420 $beta $betapassword $validate +quit
  touch /7dtd.initialized;

  # Install all the mods that go along with the 7DTD ServerMod Manager
  cd $INSTALL_DIR
  ln -s /docker-7dtd/7dtd-servermod 7dtd-servermod
  cd 7dtd-servermod
  echo "<?php
\$INSTALL_DIR=\"$INSTALL_DIR\";
?>" > $INSTALL_DIR/html/vars.inc.php
  [[ ! -f $INSTALL_DIR/server.expected_status ]] && touch $INSTALL_DIR/server.expected_status;
  chown nobody $INSTALL_DIR/server.expected_status
  chown nobody $INSTALL_DIR/Mods
  ./install_mods.sh $INSTALL_DIR

  #chown steam:steam $INSTALL_DIR /home/steam -R
  echo "Completed Installation.";
  echo "Your server should now be accessible at: http://<Your_Servers_IP>/7dtd"; exec "$@"
  sleep 6;
done

# Remove Core files in case any were created from gameserver crashes.
rm -rf $INSTALL_DIR/core.*;

# Infinite loop (make this script a daemon)
# Run the 7DTD Gameserver if it isn't already running
while true; do
if [[ -f $INSTALL_DIR/7DaysToDieServer.x86_64 ]] && [[ `cat $INSTALL_DIR/server.expected_status` == "start" ]]; then
  SERVER_PID=`ps awwux | grep -v grep | grep 7DaysToDieServer.x86_64`;
  if [[ -z $SERVER_PID ]]; then
    cd $INSTALL_DIR;
    ./7DaysToDieServer.x86_64 -configfile=$INSTALL_DIR/serverconfig.xml -logfile $INSTALL_DIR/7dtd.log -quit -batchmode -nographics -dedicated;
  fi
fi
sleep 2
done
