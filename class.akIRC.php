<?php
/****************************\
| Jason Rush's PHP IRC Class |
\****************************/

class akIRC{
    /**************\
    | Private vars |
    \**************/
    private $handlers = array();
    private $sigHandlers = array();

    private $servers = array();
    private $curServer = null;
    private $nicks = array();
    private $curNick = "AkIRC";
    private $username = "AkIRC";
    private $realname = "PHP IRC Class by Jason Rush (AkSnowman)";

    private $socket = false; // was null
    private $timeout = 60;
    private $firstLoop = true;
    private $sockStart = 0;
    private $lastPong = 0;

    private $dbg = false;
    
    /*************\
    | Public vars |
    \*************/
    
    

    /******************\
    | Public functions |
    \******************/
    public function sendMsg( $dst, $line ){
        $this->sendCmd( "PRIVMSG $dst :$line" );
    }
    public function sendMsgHeaded($channel, $text1, $text2){
        $this->sendCmd( "PRIVMSG $channel :[$text1] $text2" );
    }
    public function sendMsgTabbed($channel, $text){
        $this->sendCmd( "PRIVMSG $channel :>        $text" );
    }

    public function sendNotice( $dst, $line ){
        $this->sendCmd( "NOTICE $dst :$line" );
    }
    public function sendAction( $dst, $line ){
        $this->sendCmd( "PRIVMSG $dst :\x01ACTION :$line\x01" );
    }

    public function sendCTCP( $dst, $line ){
        $this->sendCmd( "PRIVMSG $dst :\x01$line\x01" );
    }
    public function sendCTCPReply( $dst, $line ){
        $this->sendCmd( "NOTICE $dst :\x01$line\x01" );
    }
    public function oper( $user, $pass ){
        $this->sendCmd( "OPER $user $pass" );
    }
    public function kill( $nick, $msg = ""){
        if( $msg == "" )
            $msg = $nick;
        $this->sendCmd( "KILL $nick :$msg" );
    }
    public function kline( $nick, $msg = ""){
        if( $msg == "" )
            $msg = $nick;
        $this->sendCmd( "KLINE $nick :$msg" );
    }
    public function ghost( $nick, $pass ){
        $this->sendMsg( 'nickserv', "ghost $nick $pass" );
    }
    public function ban( $chan, $mask ){
        $this->mode( $chan, '+b', $mask );
    }
    public function kickban( $chan, $nick, $mask, $msg = ""){
        if( $msg == "" )
            $msg = $nick;
        $this->ban( $chan, $mask );
        $this->kick( $chan, $nick, $msg );
    }
    public function kick( $chan, $user, $msg = "" ){
        if( $msg == "" )
            $msg = $user;
        $this->sendCmd( "KICK $chan $user :$msg" );
    }
    public function mode( $target, $mode, $arg = "" ){
        $this->sendCmd( "MODE $target $mode $arg" );
    }
    public function nick( $new ){
        $this->sendCmd( "NICK $new" );
    }
    
    public function join( $chan, $pass = "" ){
        $this->sendCmd( "JOIN $chan $pass" );
    }
    public function part( $chan ){
        $this->sendCmd( "PART $chan" );
    }
    public function quit( $msg = "" ){
        $this->sendCmd( "QUIT :$msg" );
    }
    
    /* Debug functions */
    public function debugOn(){
        $this->dbg = true;
    }
    public function debugOff(){
        $this->dbg = false;
    }

    /* Send Functions */
    public function sendCmd( $line ){
        if( ! is_string( $line ) || strlen( $line ) < 1 || in_array( $line, array( "\n", "\r", "\r\n", "\n\r" ) ) )
            return;
        /* Send the command. Think of it as writing to a file. */
        $this->debug( '<--'.str_replace( "\x01", "\\x01", $line ) );
        // For enabling logging...
        if( $this->handlerExists( '..' ) )
            $this->callHandlers( '..', $line );
        fputs($this->socket, $line."\n\r");
    }

    /* Nicks */
    public function resetNicks(){
        reset( $this->nicks );
        $this->curNick = ( count( $this->nicks ) > 0 ? current( $this->nicks ) : 'AkIRC' );
    }
    public function nextNick(){
        $nick = next( $this->nicks );
        if( $nick === false )
            return false;
//        $this->curNick = $nick;
//        return $this->curNick;
        return $nick;
    }
    public function addNick( $nick ){
        $this->nicks[ $nick ] = $nick;
        reset( $this->nicks );
        $this->curNick = current( $this->nicks );
    }
    public function getNick(){
        return $this->curNick;
    }
    public function delNick( $nick ){
        unset( $this->nicks[ $nick ] );
        reset( $this->nicks );
        $this->curNick = ( count( $this->nicks ) > 0 ? current( $this->nicks ) : 'AkIRC' );
    }
    /* realname */
    public function setRealname( $name ){
        $this->realname = $name;
    }

    /* username */
    public function setUsername( $name ){
        $this->username = $name;
    }
    
    /* Handlers */
    public function addSigHandler( $sig, $func ){
        $this->sigHandlers[ $sig ][ $func ] = $func;
    }
    public function delSigHandler( $sig, $func ){
        unset( $this->sigHandlers[ $sig ][ $func ] );
    }

    public function addHandler( $event, $func ){
        if( ! isset( $this->handlers[ strtolower( $event ) ][ strtolower( $func ) ] ) )
            $this->handlers[ strtolower( $event ) ][ strtolower( $func ) ] = $func;
    }
    public function delHandler( $event, $func ){
        unset( $this->handlers[ strtolower( $event ) ][ strtolower( $func ) ] );
    }
    
    /* Servers */
    public function addServer( $label, $host, $pass = null ){
        $this->servers[ $label ]['host'] = $host;
        $this->servers[ $label ]['pass'] = $pass;
        end( $this->servers );
    }
    public function editServer( $label, $host = null, $pass = null ){
        if( $host !== null )
            $this->servers[ $label ]['host'] = $host;
        if( $pass !== null )
            $this->servers[ $label ]['pass'] = $pass;
    }
    public function delServer( $label ){
        unset( $this->servers[ $label ] );
        end( $this->servers );
    }

    /* Main Looping Function */
    public function loop(){
        // if socket isn't connected, for any reason, try to reconnect w/ next server
        if ( false === $this->socket ){
            $this->nextServer();
			$this->debug("[System] Connecting to next server ".$this->getCurHost().":".$this->getCurPort());
            $this->socket = fsockopen( $this->getCurHost(), $this->getCurPort() );

            if( false === $this->socket )
                return false;
            
            stream_set_blocking( $this->socket, 0 );
            stream_set_timeout( $this->socket, $this->timeout );
			$this->debug("[System] Connected to server!");
            $this->sendCmd( "USER ". $this->username ." 8 * :". $this->realname);
            $this->sendCmd( "NICK ". $this->curNick );

            $this->lastPong = time();

            // this tells us if we need to ident w/ nickserv, log in as ircop, etc
            $this->firstLoop = true;
        }

        // Handler for *each* and *every* itteration/loop (ie anything asyncronous)
        if( $this->handlerExists( 'loop' ) )
            $this->callHandlers( 'loop' );

/*
		if( $this->lastPong + $this->timeout < time() ){
			fclose( $this->socket );
			$this->socket = false;
			$this->debug("[System] Timed out (too long since last communication received)");
			return false;
		}
/**/

        $r = array( $this->socket );
        $w = null;
        $e = null;
        
        $select = stream_select( $r, $w, $e, 1 );
        
        if( false === $select ){
            fclose( $this->socket );
            $this->socket = false;
			$this->debug("[System] Closing connection (select failed)");
            return false;
        }

        if( 1 > $select )
            return false;
        
        $buf = trim(fgets($this->socket, 4096));
        
        if( 0 == strlen( $buf ) ){
            fclose( $this->socket );
            $this->socket = false;
			$this->debug("[System] Connection closed by remote server? (zero bytes read)");
            return false;
        }

		$this->lastPong = time();

        /* If the server is PINGing, then PONG. This is to tell the server that
        we are still here, and have not lost the connection */
        if(substr($buf, 0, 6) == 'PING :') {
            $pong = $buf;
            $pong[1] = 'O';
            $this->sendCmd( $pong );
            if( $this->handlerExists( 'ping' ) )
                $this->callHandlers( 'ping', $buf );
            return true;
        }
        if( strlen( $buf ) < 1 || in_array( $buf, array( "\n", "\r", "\r\n", "\n\r" ) ) )
            return true;

        // returns an array of useful info
        $parse = $this->parseInput( $buf );

        $this->debug( '-->'.$parse['full'] );
        // Handler for *everything*
        if( $this->handlerExists( '.' ) )
            $this->callHandlers( '.', $parse );
        
        /* This is our first time through the loop() since connecting... */
        if( $this->firstLoop == true && $parse['act'] == '001' ){
            if( $this->handlerExists( 'onConnect' ) )
                $this->callHandlers( 'onConnect', $parse );
            $this->firstLoop = false;
        }
        
        if( $parse['isCTCP'] ){
            if( $this->handlerExists( 'ctcp' ) )
                $this->callHandlers( 'ctcp', $parse );
            $ctcpAct = strtolower( str_replace( "\x01", "", $parse['all'][3] ) );
            if( $parse['act'] == 'privmsg' ){
                if( $this->handlerExists( 'ctcp.privmsg.'.$ctcpAct ) )
                    $this->callHandlers( 'ctcp.privmsg.'.$ctcpAct, $parse );
            }else if( $parse['act'] == 'notice' ){
                if( $this->handlerExists( 'ctcp.notice.'.$ctcpAct ) )
                    $this->callHandlers( 'ctcp.notice.'.$ctcpAct, $parse );
            }else{
                if( $this->handlerExists( 'ctcp.other.'.$ctcpAct ) )
                    $this->callHandlers( 'ctcp.other.'.$ctcpAct, $parse );
            }
        }else{
            /* Call handlers based on current actions... */
            switch( $parse['act'] ){
                case 'privmsg':
                    if( $this->handlerExists( 'privmsg' ) )
                        $this->callHandlers( 'privmsg', $parse );
                    if( $this->handlerExists( 'privmsg.'.strtolower( $parse['src'] ) ) )
                        $this->callHandlers( 'privmsg.'.strtolower( $parse['src'] ), $parse );
                    if( $this->handlerExists( 'privmsg.' ) && $parse['src'] == $this->curNick )
                        $this->callHandlers( 'privmsg.', $parse );
                    break;
                case 'notice':
                    if( $this->handlerExists( 'notice' ) )
                        $this->callHandlers( 'notice', $parse );
                    if( $this->handlerExists( 'notice.'.strtolower( $parse['src'] ) ) )
                        $this->callHandlers( 'notice.'.strtolower( $parse['src'] ), $parse );
                    if( $this->handlerExists( 'notice.' ) && $parse['src'] == $this->curNick )
                        $this->callHandlers( 'notice.', $parse );
                    break;
                case 'join':
                    if( $this->handlerExists( 'join' ) )
                        $this->callHandlers( 'join', $parse );
                    if( $this->handlerExists( 'join.'.strtolower( $parse['src'] ) ) )
                        $this->callHandlers( 'join.'.strtolower( $parse['src'] ), $parse );
                    break;
                case 'nick':
                    // if we changed our nick, update the library
					$this->debug("[Nick] Current: ".$this->curNick);
                    if( $this->curNick == $parse['nick'] )
                        $this->curNick = $parse['src'];
					$this->debug("[Nick] New: ".$this->curNick);
                    // if anyone changes their nick
                    if( $this->handlerExists( 'nick' ) )
                        $this->callHandlers( 'nick', $parse );
                    // if we changed our nick
                    if( $this->handlerExists( 'nick.' ) && strtolower( $this->curNick ) == strtolower( $parse['src'] ) )
                        $this->callHandlers( 'nick.', $parse );
                    // if someone else changed their nick
                    if( $this->handlerExists( 'nick.'.strtolower( $parse['nick'] ) ) )
                    $this->callHandlers( 'nick.'.strtolower( $parse['nick'] ), $parse );
                    break;
                case 'mode':
                    if( $this->handlerExists( 'mode' ) )
                        $this->callHandlers( 'mode', $parse );
                    if( $this->handlerExists( 'mode.'.strtolower( $parse['src'] ) ) )
                        $this->callHandlers( 'mode.'.strtolower( $parse['src'] ), $parse );
                    break;
                case '433':
                    $nick = $this->nextNick();
                    $this->curNick = ( $nick !== false ? $nick : "AkIRC|".rand(0,9999) );
                    fclose( $this->socket );
                    $this->socket = null;
                    return;
                    break;
                case '001': // :irc.hack3r.com 001 testBot :Welcome to the H3CIRC IRC Network testBot!username@61-127-35-72.mtaonline.net
                case '002': // :irc.hack3r.com 002 testBot :Your host is irc.hack3r.com, running version Unreal3.2.8.1
                case '003': // :irc.hack3r.com 003 testBot :This server was created Fri Jan 7 2011 at 07:48:35 EST
                case '004': // :irc.hack3r.com 004 testBot irc.hack3r.com Unreal3.2.8.1 iowghraAsORTVSxNCWqBzvdHtGp lvhopsmntikrRcaqOALQbSeIKVfMCuzNTGj
                case '005': // :irc.hack3r.com 005 testBot UHNAMES NAMESX SAFELIST HCN MAXCHANNELS=10 CHANLIMIT=#:10 MAXLIST=b:60,e:60,I:60 NICKLEN=30 CHANNELLEN=32 TOPICLEN=3 supported by this server
                            // :irc.hack3r.com 005 testBot WALLCHOPS WATCH=128 WATCHOPTS=A SILENCE=15 MODES=12 CHANTYPES=# PREFIX=(qaohv)~&@%+ CHANMODES=beI,kfL,lj,psmntirRcOA EXTBAN=~,cqnr ELIST=MNUCT STATUSMSG=~&@%+ :are supported by this server
                            // :irc.hack3r.com 005 testBot EXCEPTS INVEX CMDS=KNOCK,MAP,DCCALLOW,USERIP :are supported by this server
                case '251': // :irc.hack3r.com 251 testBot :There are 6 users and 21 invisible on 2 servers
                case '252': // :irc.hack3r.com 252 testBot 7 :operator(s) online
                case '254': // :irc.hack3r.com 254 testBot 7 :channels formed
                case '255': // :irc.hack3r.com 255 testBot :I have 20 clients and 1 servers
                case '265': // :irc.hack3r.com 265 testBot :Current Local Users: 20  Max: 230
                case '266': // :irc.hack3r.com 266 testBot :Current Global Users: 27  Max: 31
                case '422': // :irc.hack3r.com 422 testBot :MOTD File is missing
                case '353': // :irc.hack3r.com 353 testBot = #testing :testBot aksnowman Acheron Snowbot
                case '366': // :irc.hack3r.com 366 testBot #testing :End of /NAMES list.
                default:
                    if( $this->handlerExists( $parse['act'] ) )
                        $this->callHandlers( $parse['act'], $parse );
            }
        }
        return true;
    }

    /*******************\
    | Private functions |
    \*******************/
    
    private function debug( $msg ){
        if( $this->dbg == true )
            echo $msg."\n";
    }
    
    private function sigHandler( $sig ){
        if( isset( $this->sigHandlers[ $sig ] ) && count( $this->sigHandlers[ $sig ] ) != 0 ){
            foreach( $this->sigHandlers[ $sig ] as $func )
                $func( $sig );
        }
    }
    
    private function handlerExists( $handle ){
        if( isset( $this->handlers[ strtolower( $handle ) ] ) && count( $this->handlers[ strtolower( $handle ) ] ) > 0 )
            return true;
        return false;
    }

    private function callHandlers( $handle, $p = null ){
        foreach( $this->handlers[ strtolower( $handle ) ] as $k => $v ){
            $v( $this, $p );
        }
    }
    
    /* Server Management Functions */
    private function nextServer(){
        if( next( $this->servers ) === false )
            reset( $this->servers );
        $this->curServer = current( $this->servers );
    }
    public function getCurHost(){
        $h = $this->curServer['host'];
        $pos = strrpos( $h, ':' );
        if( $pos !== false && substr( $h, $pos, 2 ) !== ':/' )
            return substr( $h, 0, $pos );
        return $h;
    }
    public function getCurPort(){
        $h = $this->curServer['host'];
        $pos = strrpos( $h, ':' );
        if( $pos !== false && substr( $h, $pos, 2 ) !== ':/' )
            return substr( $h, $pos + 1 );
        return 6667;

        $e = explode( ':', $this->curServer['host'], 2);
        return ( count( $e ) == 1 ? 6667 : (int) end( $e ) );
    }
    private function getCurPass(){
        return $this->curServer['pass'];
    }

    /* Misc Functions */
    private function safe_feof($fp, &$start = NULL) {
        $start = microtime(true);
        return feof($fp);
    }

    /* Core Parsing Function */
    private function parseInput( $buf ){
        // Parse everything out
        $p = array(
                'inPM'  => false,
                'inChan'=> false,

                'isCTCP'=> false,

                'act'   => "",  // privmsg, notice, etc...

                'nick'  => "",
                'user'  => "",
                'host'  => "",
                
                'chan'  => "",

                'src'   => "",
                'cmd'   => "",
                'cmdargs'   => "",
                'cmdtxt'=> "",                
                );
        
        $p['full'] = $buf;
        $p['all'] = explode(' ',$p['full']);

        // remove preceding colons
        if ( count($p['all']) > 2 && substr($p['all'][2],0,1)==":")
            $p['all'][2] = substr($p['all'][2],1);
        if ( count($p['all']) > 3 && substr($p['all'][3],0,1)==":")
            $p['all'][3] = substr($p['all'][3],1);
        // :aksnowman!root@h3c-C662C872.mtaonline.net PRIVMSG #testing :\x01ACTION bleh\x01
        if ( isset( $p['all'], $p['all'][3] ) && substr( $p['all'][3], 0, 1 ) === "\x01" )
            $p['isCTCP'] = true;

        // nick!user@host => nick,user,host
        $p['hostmask'] = ( substr( $p['all'][0], 0, 1 ) == ':' ? substr( $p['all'][0], 1 ) : $p['all'][0] );
        $nick_userhost = explode( "!", $p['hostmask'] );
        if ( count( $nick_userhost ) == 2 && strpos( $nick_userhost[1], '@' ) !== false ){
            $p['nick'] = $nick_userhost[0];
            $user_host = explode( "@", $nick_userhost[1] );
            if (count($user_host) == 2){
                $p['user'] = $user_host[0];
                $p['host'] = $user_host[1];
            }
        }

        // num,act
        // would probably be better w/ some type of regex against [0-9]
    /*    if ( (int) $p['all'][1] == $p['all'][1] )
            $p['num'] = (int) $p['all'][1];
        else
        */
            $p['act'] = strtolower( $p['all'][1] );

        //  isInPM,isInChan,chan
        if (count($p['all']) > 2){
            $p['src'] = $p['all'][2];
            if (substr($p['src'],0,1)=="#")
                $p['inChan'] = true;
            else
                $p['inPM'] = true;
        }

        // if PRIVMSG, set $p['msg']
        // <msg>
        if( $p['act'] == 'privmsg' || $p['act'] == 'notice' ){
            $tmp = explode( ' ', $p['full'], 4 );
            if( count( $tmp ) > 3 ){
                $p['msg'] = end( $tmp );
                if( substr( $p['msg'], 0, 1 ) == ':' )
                    $p['msg'] = substr( $p['msg'], 1 );
            }
        }
        
        // cmd,cmdtxt,cmdargs
        // <cmd> <cmdargs[0]> <cmdargs[n]>
        // <cmd> <cmdtxt>
        if ( $p['act'] == "privmsg" ){
            $tmp = $p['all'];
            array_shift($tmp);
            array_shift($tmp);
            array_shift($tmp);
            $p['cmd'] = str_replace( "\x01", "", array_shift($tmp) );
            $p['cmdargs'] = $tmp;
            if (count($p['cmdargs']) > 0){
                $tmp = explode(' ',$p['full'], 5);
                $p['cmdtxt'] = $tmp[4];
            }
        }
        return $p;
    }
}
