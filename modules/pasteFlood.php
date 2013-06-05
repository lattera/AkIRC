<?php
function pasteFlood_construct( &$bot, &$vars ){
    global $cfg;
    if( ! isset( $cfg['pasteFlood'] ) )
        return false;
    $vars = array();
    foreach( $cfg['pasteFlood'] as $chan => $vals )
        $bot->addHandler( 'privmsg.'.strtolower( $chan ), 'pasteFlood_privmsg' );
    return true;
}

function pasteFlood_privmsg( &$bot, $parse ){
    global $cfg, $pasteFlood_warnings, $pasteFlood, $modVars;
    $nick = strtolower( $parse['nick'] );
    $chan = strtolower( $parse['src'] );
    $pasteFlood_warnings = &$modVars['pasteFlood'];

    $numLines = 5;
    $timeBuf = 2;
    $warnings = 2;
    $expires = 60;
    if( isset( $cfg['pasteFlood'][ $chan ] ) && is_array( $cfg['pasteFlood'][ $chan ] ) )
        extract( $cfg['pasteFlood'][ $chan ] );

    if( isset( $cfg['pasteFloodExempt'] ) && is_array( $cfg['pasteFloodExempt'] ) ){
        foreach( $cfg['pasteFloodExempt'] as $hostmaskEnding ){
            if( strlen( $parse['host'] ) >= strlen( $hostmaskEnding ) && substr( $parse['host'], strlen( $parse['host'] ) - strlen( $hostmaskEnding ), strlen( $hostmaskEnding ) ) == $hostmaskEnding )
                return;
        }
    }

    // note time(), nick, and channel for current PRIVMSG
    if( ! isset( $pasteFlood[ $chan ][ $nick ] ) )
        $pasteFlood[ $chan ][ $nick ] = array();
    array_push( $pasteFlood[ $chan ][ $nick ], time() );

    // limit history to numLines
    if( count( $pasteFlood[ $chan ][ $nick ] ) > $numLines )
        array_shift( $pasteFlood[ $chan ][ $nick ] );

    // if > numLines w/i timeBuf
    if(    max( $pasteFlood[ $chan ][ $nick ] ) <=
        ( min( $pasteFlood[ $chan ][ $nick ] ) + $timeBuf ) &&
        count( $pasteFlood[ $chan ][ $nick ] ) == $numLines ){

        //
        $warned = ( isset( $pasteFlood_warnings[ $nick ][ $chan ] ) && $pasteFlood_warnings[ $nick ][ $chan ]['lastWarn'] + $expires >= time()
                    ? $pasteFlood_warnings[ $nick ][ $chan ]['warnings']
                    : 0 );
        $warned++;

        if( $warnings == 0 || $warned >= $warnings ){
            // reset warnings
            unset( $pasteFlood_warnings[ $nick ][ $chan ] );
            // reset current count
            unset( $pasteFlood[ $chan ][ $nick ] );
            $bot->kickban( $chan, $nick, '*!*@'.$parse['host'], "Flooding will not be tolerated." );
            queueMessage( time() + (isset( $expires ) ? $expires : 60*60 ), "MODE $chan -b *!*@".$parse['host'] );
        }else{
            $bot->sendMsgHeaded( $nick, 'Warning', 'Stop flooding immediately, it will not be tolerated.' );
            $bot->kick( $chan, $nick, 'Flooding will not be tolerated.' );
            unset( $pasteFlood[ $chan ][ $nick ] );
            $pasteFlood_warnings[ $nick ][ $chan ]['warnings'] = $warned;
            $pasteFlood_warnings[ $nick ][ $chan ]['lastWarn'] = time();
        }
    }
}
?>
