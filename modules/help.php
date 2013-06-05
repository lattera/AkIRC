<?php

function help_construct( &$bot, &$vars ){
    global $cfg, $help;
    $bot->addHandler( 'privmsg', 'help_privmsg' );
    if( ! isset( $help ) ) $help = array();
    return true;
}

function help_privmsg( &$bot, $parse ){
    global $help;
    if( ".help" == $parse['cmd'] ){
        $tHelp = $help;
        $args = $parse['cmdargs'];
        $bot->sendMsgHeaded( $parse['nick'], "Help", "Offers documentation on using the bots public commands." );
        if( 0 < count( $parse['cmdargs'] ) ){
            $dig = true;
            while( true === $dig ){
                $next = array_shift( $args );
                if( null !== $next && isset( $tHelp[ $next ] ) ){
                    $tHelp = $tHelp[ $next ];
                }else
                    $dig = false;
            }
        }else
            $bot->sendMsgTabbed( $parse['nick'], "Syntax: '.help .command [ subarg [ sub-subarg ] ]' ex. '.help .inur'" );
        foreach( $tHelp as $val ){
            if( is_string( $val ) )
                $bot->sendMsgTabbed( $parse['nick'], $val );
        }
    }
}