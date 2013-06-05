<?php

$vers['MySQL'][] = '0.0.1'; // Implement later?

/* To use a socket instead of a host/port, do the following:
$host = "";
$port = "/tmp/mysql.sock"; // replacing with proper path, of course
*/

function MySQL_construct( &$bot, &$vars ){
    global $cfg;
    if( ! isset( $cfg['MySQL'], $cfg['MySQL']['database'] ) )
        return false;
    $host = "localhost";
    $user = "root";
    $pass = "";
    $port = 3306;
    extract( $cfg['MySQL'] );
    if( ! mysql_connect( "$host:$port", $user, $pass ) ){
        echo "[MySQL] Connection failed: ".mysql_error()."\n";
        return false;
    }
    if( ! mysql_select_db( $database ) ){
        echo "[MySQL] Select Database failed: ".mysql_error()."\n";
        return false;
    }
    return true;
}
