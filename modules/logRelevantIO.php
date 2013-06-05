<?php

function logRelevantIO_construct( &$bot, &$vars ){
    $bot->addHandler( '.', 'logRelevantIO_recv' );
    $bot->addHandler( 'ping', 'logRelevantIO_recv' );
    $bot->addHandler( '..', 'logRelevantIO_sent' );
    return true;
}

function logRelevantIO_recv( &$bot,  &$parse ){
    global $logRelevantIORecv;
    
    if( is_array( $parse ) )
        $logRelevantIORecv = $parse['full'];
    else
        $logRelevantIORecv = $parse;
}

function logRelevantIO_sent( &$bot, $line ){
    global $cfg, $logRelevantIORecv, $logRelevantIORecvLast;
    
    $relevantLog = "relevant.log";

    if( "PONG :" == substr( $line, 0, 6 ) )
        return;    

    if( $logRelevantIORecv != $logRelevantIORecvLast ){
        $logRelevantIORecvLast = $logRelevantIORecv;
        file_put_contents( $relevantLog, "=====\n".date("M d,y H:i:s")."<".$logRelevantIORecvLast."\n",  FILE_APPEND );
    }
    file_put_contents( $relevantLog, date("M d,y H:i:s").">".$line."\n" ,  FILE_APPEND );
}
