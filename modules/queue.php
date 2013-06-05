<?php

// EVENTUALLY NEEDS UPGRADED TO USE MYSQL AS PRIMARY METHOD, FALL-BACK TO NON-SQL
// Also, maybe a queueEval() for PHP evaluation?
function queue_construct( &$bot, &$queue ){
    if( ! isset( $queue ) || ! is_array( $queue ) )
        $queue = array();

    $bot->addHandler( 'loop', 'queueProcess' );
    return true;
}

// Shoves new "message" into the queue for future processing
function queueMessage( $timestamp, $message ){
    global $modVars;
    $modVars['queue'][$timestamp][] = $message;
    queueProcess();
}

// Ran every time the loop is run... This means we can test for upcoming commands every second by default config
function queueProcess(){
    global $modVars, $bot;
    foreach( $modVars['queue'] as $time => $messages ){
        if( time() >= $time ){
            foreach( $messages as $message )
                $bot->sendCmd( $message );
            unset( $modVars['queue'][$time] );
        }
    }
}
