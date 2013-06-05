<?php
function logging_construct( &$bot, &$vars ){
    global $cfg;
    if( isset( $cfg['logFile'] ) ){
        $bot->addHandler( '.', 'logging_logIncomming' );
        $bot->addHandler( '..', 'logging_logOutgoing' );
        return true;
    }
    return false;
}

function logging_logIncomming( &$bot, $parse ){
    global $cfg;
    logging_writeToFile( $cfg['logFile'], date("[d/m @ H:i]")."<--".$parse['full'] );
}
function logging_logOutgoing( &$bot, $line ){
    global $cfg;
    logging_writeToFile( $cfg['logFile'], date("[d/m @ H:i]")."-->$line" );
}
function logging_writeToFile( $file, $line ){
    file_put_contents( $file, $line."\n", FILE_APPEND | LOCK_EX );
}
?>
