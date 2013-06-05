<?php
function massflood_construct( &$bot, &$vars ){
    global $cfg;
    $vars = array();
    $vars['offenders'] = array();
    if( isset( $cfg['massflood'] ) ){
        $bot->addHandler( 'privmsg', 'massflood_privmsg' );
        return true;
    }
    return false;
}

function massflood_privmsg( &$bot, $parse ){
    global $cfg, $modVars;
    $massflood = &$modVars['massflood'];
    $conf = &$cfg['massflood'];

    $tmp = explode( ' ', $parse['full'], 4 );
    if( array_key_exists( $tmp[3], $massflood['offenders'] ) &&
        $massflood['offenders'][$tmp[3]] + $conf['timeBuf'] >= time() )
        switch( $conf['action'] ){
            case 'kline':
                $bot->kill( $parse['nick'], "Flooding is not tolerated." );
                break;
            case 'kline':
                $bot->kline( $parse['nick'], "Flooding is not tolerated." );
                break;
            case 'kick':
                $bot->kick( $parse['src'], $parse['nick'], "Flooding is not tolerated." );
                break;
            case 'ban':
                $bot->ban( $parse['src'], "*!*@".$parse['host'] );
                break;
            case 'kickban':
            default:
                $bot->ban( $parse['src'], "*!*@".$parse['host'] );
                $bot->kick( $parse['src'], $parse['nick'], "Flooding is not tolerated." );
                break;
        }
    else{
        $nick = strtolower( $parse['nick'] );
        $massflood['track'][ $nick ]['time'] = time();
        $massflood['track'][ $nick ]['msg'] = $tmp[3];
        $massflood['track'][ $nick ]['user'] = $parse['user'];
        $massflood['track'][ $nick ]['host'] = $parse['host'];
        $massflood['track'][ $nick ]['chan'] = $parse['src'];
        $dups = 0;
        foreach( $massflood['track'] as $userNick => $keys ){
            if( $tmp[3] == $keys['msg'] &&
                $keys['time'] + $conf['timeBuf'] >= time() )
                
                $dups++;
        }
        if( $dups >= $conf['numPeople'] ){
            $massflood['offenders'][ $tmp[3] ] = time();
            foreach( $massflood['track'] as $userNick => $keys ){
                if( $tmp[3] == $keys['msg'] &&
                    $keys['time'] + $conf['timeBuf'] >= time() )

                    switch( $conf['action'] ){
                        case 'kline':
                            $bot->kill( $userNick, "Flooding is not tolerated." );
                            break;
                        case 'kline':
                            $bot->kline( $userNick, "Flooding is not tolerated." );
                            break;
                        case 'kick':
                            $bot->kick( $keys['chan'], $userNick, "Flooding is not tolerated." );
                            break;
                        case 'ban':
                            $bot->ban( $keys['chan'], "*!*@".$keys['host'] );
                            break;
                        case 'kickban':
                        default:
                            $bot->ban( $keys['chan'], "*!*@".$keys['host'] );
                            $bot->kick( $keys['chan'], $userNick, "Flooding is not tolerated." );
                            break;
                    }

            }
        }
    }
}
?>
