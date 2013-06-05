<?php
function connFlood_construct( &$bot, &$vars ){
    global $cfg;
    if( ! isset( $cfg['connFlood'] ) )
        return false;
    $bot->addHandler( 'notice', 'connFlood_notice' );
    return true;
}

function connFlood_notice( &$bot, $parse ){
    global $modVars, $connFlood, $cfg;
    $jfCfg = &$cfg['connFlood'];
    if( strpos( $parse['full'], "Client connecting on port" ) !== false ){
var_export( $parse );
        if( ! isset( $connFlood['users'] ) )
            $connFlood['users'] = array();
        if( ! isset( $connFlood['times'] ) )
            $connFlood['times'] = array();
        array_push( $connFlood['users'], $parse['all'][11] );
        array_push( $connFlood['times'], time() );
        if( count( $connFlood['users'] ) > $jfCfg['numPeople'] ){
            array_shift( $connFlood['users'] );
            array_shift( $connFlood['times'] );
        }
        if( count( $connFlood['users'] ) == $jfCfg['numPeople'] &&
            ( max( $connFlood['times'] ) - min( $connFlood['times'] ) ) <= $jfCfg['timeBuf'] ){

//          $connFloodParse = explode( '@', substr( $parse['all'][12], 1, -1 ) ); username@hostmask
            $connFlood['test'] = array();
            /* tests for nickXX type names where XX are 2 alpha chars */
            foreach( $connFlood['users'] as $user )
                $connFlood['test'][] = substr( strtolower( $user ), 0, -2 );
            if( count( array_unique( $connFlood['test'] ) ) == 1 ){
                $chars = "qwertyuiopasdfghjklzxcvbnm";
                $charlen = strlen( $chars );
                for( $i = 0; $i < $charlen; $i++ ){
                    for( $j = 0; $j < $charlen; $j++ )
                        $bot->sendCmd( "KLINE ".$joinflood['test'][0].$chars[$i].$chars[$j]." :Spamming... GTFO" );
                }
                unset( $connFlood );
            }else{
                /* tests for nickYYYY type names where YYYY are 4 digits */
                foreach( $connFlood['users'] as $user )
                    $connFlood['test'][] = substr( strtolower( $user ), 0, -4 );
                if( count( array_unique( $connFlood['test'] ) ) == 1 ){
                    for( $i = 1000; $i < 1100; $i++ )
                        $bot->sendCmd( "KLINE ".$connFlood['test'][0].str_pad( $i, 4, '0', STR_PAD_LEFT )." :Spamming... GTFO" );
                    unset( $connFlood );
                }
            }
        }
    }
}
?>