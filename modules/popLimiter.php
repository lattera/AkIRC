<?php
function popLimiter_construct( &$bot, &$vars ){
    global $cfg;
    if( ! isset( $cfg['popLimiter'] ) )
        return false;
    $vars = array();
    $bot->addHandler( '353', 'popLimiter_353' );
    $bot->addHandler( '366', 'popLimiter_366' );
    $bot->addHandler( 'join', 'popLimiter_join' );
    $bot->addHandler( 'part', 'popLimiter_part' );
    $bot->addHandler( 'kick', 'popLimiter_kick' );
    $bot->addHandler( '441', 'popLimiter_441' );
    $bot->addHandler( 'quit', 'popLimiter_quit' );
    return true;
}
function popLimiter_353( &$bot, $parse ){
    global $cfg, $modVars;
    $pop = &$modVars['popLimiter'];
    $chan = strtolower( $parse['all'][4] );
    if( ! isset( $cfg['popLimiter'][ $chan ] ) || ! isset( $pop[ $chan ]['finalized'] ) || $pop[ $chan ]['finalized'] == true ){
        $pop[ $chan ]['finalized'] = false;
        $pop[ $chan ]['pop'] = 0;
    }
    if( ! isset( $pop[ $chan ]['pop'] ) )
        $pop[ $chan ]['pop'] = 0;
    $pop[ $chan ]['pop'] += count( $parse['all'] ) - 5;
}
function popLimiter_366( &$bot, $parse ){
    global $cfg, $modVars;
    $pop = &$modVars['popLimiter'];
    $chan = strtolower( $parse['all'][3] );
    if( ! isset( $cfg['popLimiter'][ $chan ] ) )
        return;
    $pop[ $chan ]['finalized'] = true;

    if( popLimiter_pop( $chan ) !== false )
        queueMessage( time() + $cfg['popLimiter'][$chan]['timeBuf'],
                "MODE $chan +l " . ( popLimiter_pop( $chan ) + $cfg['popLimiter'][$chan]['popBuf'] )
                );
}
function popLimiter_join( &$bot, $parse ){
    global $cfg, $modVars;
    $pop = &$modVars['popLimiter'];
    $chan = strtolower( $parse['src'] );
    if( ! isset( $cfg['popLimiter'][ $chan ] ) )
        return;
    $pop[ $chan ]['pop']++;

    if( popLimiter_pop( $chan ) !== false )
        queueMessage( time() + $cfg['popLimiter'][$chan]['timeBuf'],
                "MODE $chan +l " . ( popLimiter_pop( $chan ) + $cfg['popLimiter'][$chan]['popBuf'] )
                );
}
function popLimiter_part( &$bot, $parse ){
    global $cfg, $modVars;
    $pop = &$modVars['popLimiter'];
    $chan = strtolower( $parse['src'] );
    if( ! isset( $cfg['popLimiter'][ $chan ] ) )
        return;
    $pop[ $chan ]['pop']--;

    if( popLimiter_pop( $chan ) !== false )
        queueMessage( time() + $cfg['popLimiter'][$chan]['timeBuf'],
                "MODE $chan +l " . ( popLimiter_pop( $chan ) + $cfg['popLimiter'][$chan]['popBuf'] )
                );
}
function popLimiter_kick( &$bot, $parse ){
    global $cfg, $modVars;
    $pop = &$modVars['popLimiter'];
    $chan = strtolower( $parse['chan'] );
    if( ! isset( $cfg['popLimiter'][ $chan ] ) )
        return;
    $pop[ $chan ]['pop']--;

    if( $parse['isInChan'] && popLimiter_pop( $parse['chan'] ) !== false )
        queueMessage( time() + $cfg['popLimiter'][$chan]['timeBuf'],
                "MODE " . $chan . " +l " . ( popLimiter_pop( $chan ) + $cfg['popLimiter'][$chan]['popBuf'] )
                );
}
function popLimiter_441( &$bot, $parse ){
    global $cfg, $modVars;
    $pop = &$modVars['popLimiter'];
    $chan = strtolower( $parse['all'][4] );
    if( ! isset( $cfg['popLimiter'][ $chan ] ) )
        return;
    $pop[ $chan ]['pop']++;

    if( popLimiter_pop( $chan ) !== false )
        queueMessage( time() + $cfg['popLimiter'][$chan]['timeBuf'],
                "MODE $chan +l " . ( popLimiter_pop( $chan ) + $cfg['popLimiter'][$chan]['popBuf'] )
                );
}
function popLimiter_quit( &$bot, $parse ){
    global $cfg;
    foreach( $cfg['popLimiter'] as $chan => $vals )
        $bot->sendCmd( "NAMES $chan" );
}

/* Returns current channel population */
function popLimiter_pop( $channel ){
    global $modVars;
    $pop = &$modVars['popLimiter'];
    if( ! isset( $pop[$channel] ) )
        return false;
    if( ! isset( $pop[$channel]['finalized'] ) || $pop[$channel]['finalized'] != true )
        return false;
    return $pop[$channel]['pop'];
}

?>