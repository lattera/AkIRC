<?php $cfg = array (
  'logFile' => 'irc.log',
  'persistFile' => 'persist.dat',
  'MySQL' =>
  array (
    'host' => 'localhost',
    'user' => 'SQL_User',
    'pass' => 'SQL_Pass',
    'database' => 'SQL_Database',
  ),
  'servers' => 
  array (
    'PrimaryServer' => 'ssl://someServer.com:6697',
	'SecondaryServer' => 'unencrypted.org:6667', // etc
  ),
  'nicks' => 
  array (
    'PrimaryNick',
    'SecondaryNick',
    'TertiaryNick', // etc
  ),
  'autojoin' => // module
  array (
    '#channel_one',
    '#channel_two',
    '#channel_three', // etc
  ),
  'chanModes' => // module
  array (
    '#channel_one' => '+jf 3:10 [20j#R5,10c#M5,10k#K1,5n#N1,50m#M5,50t#b5]:15',
    '#channel_three' => '+jf 3:10 [20j#R5,10c#M5,10k#K1,5n#N1,50m#M5,50t#b5]:15',
  ),
  'pasteFloodExempt' => // module
  array(
    '.example.net',
  ),
  'pasteFlood' => // module
  array (
    '#channel_two' =>
    array (
      'numLines' => 5,
      'timeBuf' => 2,
      'warnings' => 3,
      'expires' => 3600,
    ),
    '#channel_three' =>
    array (
      'numLines' => 6,
      'timeBuf' => 2,
      'warnings' => 2,
      'expires' => 3600,
    ),
  ),
  'massflood' => // module
  array (
    'numPeople' => 10,
    'timeBuf' => 5,
    'action' => 'kline',
  ),
  'operCreds' => // module?
  array (
    'user' => 'ircop_username',
    'pass' => 'ircop_password',
  ),
  'nickservCreds' =>  // module?
  array (
    'user' => 'nickserv_username',
    'pass' => 'nickserv_password',
    'email' => 'nickserv@email.com',
    'logFile' => 'nickserv.creds', // File where nickserv credentials are stored
  ),
  'ctcpReplies' => 
  array (
    'time' => 'TIME Sun Apr 1 03:13:37',
    'version' => 'VERSION xchat 0.8.4 Ubuntu',
  ),
  'tempBanTor' => array(
    'blacklist' => array(
//        '.torservers.net',
    ),
  ),
  'cfg_file' => 'config.php', // Name of this file, should probably be changed to a seperate global in the bot
);
