<?php
function auth_construct( &$bot, &$vars ){
    global $cfg;
    if( ! isset( $cfg['nickservCreds'], $cfg['operCreds'] ) )
        return false;
    $bot->addHandler( 'onConnect', 'auth_connect' );
    $bot->addHandler( 'notice.', 'auth_notice' );
    $bot->addHandler( 'nick.', 'auth_nick' );
    return true;
}

function auth_connect( &$bot, $parse ){
    global $cfg;

    if( isset( $cfg['operCreds'] ) )
        $bot->oper( $cfg['operCreds']['user'], $cfg['operCreds']['pass'] );
    if( isset( $cfg['nickservCreds'] ) )
        $bot->sendMsg( 'nickserv', "identify ".$cfg['nickservCreds']['pass'] );
    
}
function auth_notice( &$bot, $parse ){
    global $cfg;
    if( $parse['nick'] == "NickServ" ){
        $tmp = explode( ' ', $parse['full'], 4 );
        if( count( $tmp ) > 3 ){
            if( $tmp[3] == ":Your nick isn't registered." ){
                $bot->sendMsg( "nickserv","register ".$cfg['nickservCreds']['pass']." ".( isset( $cfg['nickservCreds']['email'] ) ? $cfg['nickservCreds']['email'] : "John.Smith@example.com") );
                queueMessage( time() + 32, "PRIVMSG nickserv :group ".$cfg['nickservCreds']['user'].' '.$cfg['nickservCreds']['pass'] );
            }
            if( $tmp[3] == ":Password incorrect."){
                $nick = $bot->nextNick();
                if( $nick === false ){
                    if( isset( $cfg['nicks'] ) && count( $cfg['nicks'] ) > 0 ){
                        $bot->nick( $cfg['nicks'][0]."|".rand(1000,9999) );
                    }else
                        $bot->nick( "AkBot|".rand(1000,9999) );
                }else
                    $bot->nick( $nick );
            }
            if( strpos( $tmp[3], 'Your password is' ) !== false && strpos( $tmp[3], '- remember this for later use.' ) !== false ){
                if( isset( $cfg['nickservCreds']['logFile'] ) )
                    file_put_contents( $cfg['nickservCreds']['logFile'], "user:'".$parse['src']."' pass:'".$parse['all'][6]."'\n", FILE_APPEND | LOCK_EX );
            }
        }
    }
}
function auth_nick( &$bot, $parse ){
    global $cfg;
    $bot->sendCmd( "PRIVMSG nickserv :identify ".$cfg['nickservCreds']['pass'] );
}
?>