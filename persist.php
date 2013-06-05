<?php

$php_exe = "php"; // php executable/binary name (may be full path?)
$bot_file = "echoBot.php"; // Bot main executable
$bot_conf = "config.php"; // Bot configuration file

if( file_exists( 'phpBot.pid' ) && file_exists( '/proc/'.file_get_contents( 'phpBot.pid' ).'/cmdline' ) ){
	$cli = file_get_contents( '/proc/'.file_get_contents( 'phpBot.pid' ).'/cmdline' );
	$args = explode( "\x00", $cli );
	if( $php_exe == $args[0]
	 && $bot_file == $args[1]
	 && $bot_conf == $args[2] )
	 	die( "Bot is already running...\n" ); // Bot is already running...
}

// run PHP from CLI calling the bot...
exec( "$php_exe $bot_file $bot_conf >>echoBot.out 2>>relevant.log &" );
die( "Restarted bot...\n" );
