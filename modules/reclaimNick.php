<?php
function reclaimNick_construct( &$bot, &$vars ){
    global $cfg;
    if( ! isset( $cfg['nicks'] ) || count( $cfg['nicks'] ) == 0 ){
        echo "[reclaimNick] No nicks configured.\n";
        return false;
    }
    $bot->addHandler( 'privmsg', 'reclaimNick_callback' );
    $bot->addHandler( 'join', 'reclaimNick_callback' );
    $bot->addHandler( 'nick', 'reclaimNick_callback' );
    $bot->addHandler( 'notice.', 'reclaimNick_notice' );
    return true;
}

function reclaimNick_notice( &$bot, $parse){
    global $cfg;
    if( $parse['nick'] == "NickServ" ){
        $tmp = explode( ' ', $parse['full'], 4 );
        if( count( $tmp ) > 3 ){
            if( $tmp[3] == ":Ghost with your nick has been killed." )
                $bot->nick( $cfg['nicks'][0] );
        }
    }
}

function reclaimNick_callback( &$bot, $parse ){
    global $cfg;
    if( strtolower( $bot->getNick() ) != strtolower( $cfg['nicks'][0] ) ){
        if( isset( $cfg['nickservCreds'] ) && isset( $cfg['nickservCreds']['pass'] ) )
            $bot->ghost( $cfg['nicks'][0], $cfg['nickservCreds']['pass'] );
        $bot->nick( $cfg['nicks'][0] );
    }
}

?>
