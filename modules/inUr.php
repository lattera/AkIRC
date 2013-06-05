<?php
function inUr_construct( &$bot, &$vars ){
    global $help;
    $bot->addHandler( 'privmsg', 'inUr_privmsg' );
    if( ! isset( $help ) ) $help = array();
    $help[] = ".inur\t'im in ur ___, ___ing ur ___z'";
    $help['.inur'] = array(
        ".inur <noun> <verb> <noun>",
        "Displays 'im in ur ___, ___ing ur ___z'",
    );
    return true;
}

function inUr_privmsg( &$bot, $parse ){
    if( ".inur" == $parse['cmd'] && 2 < $parse['cmdargs'] )
        $bot->sendMsgHeaded( $parse['src'], "inur", "im in ur {$parse['cmdargs'][0]}, {$parse['cmdargs'][1]}ing ur {$parse['cmdargs'][2]}z" );
}