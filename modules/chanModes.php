<?php
function chanModes_construct( &$bot, &$vars ){
    global $cfg;
    if( isset( $cfg['chanModes'] ) ){
        $bot->addHandler( '366', 'chanModes_handler' );
        return true;
    }
    return false;
}

function chanModes_handler( &$bot, $parse ){
    global $cfg;
    $chan = strtolower( $parse['all'][3] );

    if( isset( $cfg['chanModes'][ $chan ] ) ){
        $bot->sendCmd( "MODE $chan ".$cfg['chanModes'][ $chan ] );
//        unset( $cfg['chanModes'][ $chan ] );
    }
}
?>
