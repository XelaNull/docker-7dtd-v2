<?php
$SMM_VERSION="2.0";
$INSTALL_DIR="/data/7DTD";
@$STEAMCMD_NO_VALIDATE=$_ENV['STEAMCMD_NO_VALIDATE'];
@$STEAMCMD_BETA=$_ENV['STEAMCMD_BETA'];
@$STEAMCMD_BETA_PASSWORD=$_ENV['STEAMCMD_BETA_PASSWORD'];
@$MODS_TO_INSTALL=array(
  array(URL=>'https://github.com/Prisma501/CSMM-Patrons-Mod/releases/download/A19.6-v19.8.1/CPM_19.8.1.zip', Method=>'wget', File=>'CPM.zip', Extract=>'true', CMD=>''),
  array(URL=>'http://botman.nz/Botman_Mods_A19.zip', Method=>'wget', File=>'Botman_Mods.zip', Extract=>'true', CMD=>'cp -rp 7DaysToDieServer_Data/* ../../7DaysToDieServer_Data/'),
  array(URL=>'https://raw.githubusercontent.com/Prisma501/Allocs-Webmap-for-CPM/master/map.js', Method=>'wget', File=>'map.js', Extract=>'false', CMD=>'mv map.js ../1/Mods/Allocs_WebAndMapRendering/webserver/js'),
  array(URL=>'https://github.com/dmustanger/7dtd-ServerTools/releases/download/19.6.5/7dtd-ServerTools-19.6.5.zip', Method=>'wget', File=>'ServerTools.zip', Extract=>'true', CMD=>''),
  array(URL=>'https://github.com/XelaNull/7DTD-Neopolitan/releases/download/A19.3_1.0d/2021-06-03_215241-Modlet_Collection-Shouden.zip', Method=>'wget', File=>'Neopolitan.zip', Extract=>'true', CMD=>'')
);
?>
