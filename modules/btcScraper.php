<?php

$deps['btcScraper'][] = 'help';
$vers['btcScraper'][] = '1.0.3'; // Implement later?

function btcScraper_construct( &$bot, &$vars ){
    global $cfg,$help;
    if( ! isset( $cfg['btcScraper'], $cfg['btcScraper']['key'], $cfg['btcScraper']['secret'], $cfg['btcScraper']['channels'] ) )
        return false;
    $vars = array();
    foreach( $cfg['btcScraper']['channels'] as $chan )
        $bot->addHandler( 'privmsg.'.strtolower( $chan ), 'btcScraper_privmsg' );
    if( ! isset( $help ) ) $help = array();
    $help[] = ".btc\tReturns current Last, High, Low, and Avg Bitcoin rates from Mt. Gox";
    $help['.btc'] = array(
        ".btc",
        "Displays current Bitcoin rates from Mt. Gox ( https://mtgox.com )",
        'ex. [BTC] Last: $xx.xx High: $xx.xx Low: $xx.xx Avg: $xx.xx'
    );
    $help['.ltc'] = array(
        '.ltc',
        'Displays current Litcoin rates from btc-e ( https://btc-e.com/ )',
        'ex. [LTC] Last: $xx.xx High: $xx.xx Low: $xx.xx Avg: $xx.xx',
    );
    return true;
}

function btcScraper_privmsg( &$bot, $parse ){
    global $cfg, $btcScraperCache, $btcScraperLastPublic;
    $my_commands = array('.btc', '.ltc');
    
    $timeout = 300;
    $displayPubliclyTimeout = 30;

    if( $parse['inPM'] )
        return;
    if( ! in_array($parse['cmd'], $my_commands) )
        return;

    if ( $parse['cmd'] == '.ltc' )
        return ltcMode($bot, $parse);

    if( ! isset( $btcScraperLastPublic ) ) $btcScraperLastPublic = 0;
    if( ! isset( $btcScraperCache ) ) $btcScraperCache = array();
    if( ! isset( $btcScraperCache['timestamp'] ) ) $btcScraperCache['timestamp'] = 0;

    // At the moment on a failure, it will allow people to keep tryign until it succeeds... need to figure out how to change this...

    if( time() > $btcScraperCache['timestamp'] + $timeout){
        $res = btcScraper_mtgox_query('1/BTCUSD/ticker');
//if( isset( $parse['cmdargs'][0] ) && "FAIL" == $parse['cmdargs'][0] ) $res = false;
        if( false !== $res )
            $btcScraperCache['timestamp'] = time();
    }

    $dest = ( time() > $btcScraperLastPublic + $displayPubliclyTimeout ? $parse['src'] : $parse['nick'] );
    $diff = time() - $btcScraperCache['timestamp'];

    if( isset( $res ) && false !== $res ){
        $btcScraperCache['last'] = $res['return']['last']['display_short'];
        $btcScraperCache['high'] = $res['return']['high']['display_short'];
        $btcScraperCache['low'] = $res['return']['low']['display_short'];
        $btcScraperCache['avg'] = $res['return']['avg']['display_short'];
        if( isset( $parse['cmdargs'][0] ) && "ALLTHETHINGS" == $parse['cmdargs'][0] ){
            $msg = "";
            foreach( $res['return'] as $k => $v )
                $msg .= "\x02$k:\x0f {$v['display_short']} ";
            $btcScraperLastPublic = time();
            $bot->sendMsgHeaded( $dest, "BTC",
                $msg.
                (   0 == $btcScraperCache['timestamp'] || 0 == $diff
                    ? ''
                    : " $diff second".($diff > 1 ? 's' : '' )." ago"
                )
            );
            return;
        }
    }
    if( isset( $parse['cmdargs'][0] ) && "ALLTHETHINGS" != $parse['cmdargs'][0] && is_numeric( $parse['cmdargs'][0] ) ){
    if( floatval( $parse['cmdargs'][0] ) > 0 && floatval( $parse['cmdargs'][0] ) < 10000000 ){
	$num_btc = floatval( $parse['cmdargs'][0] );
    	$btcScraperLastPublic = time();
	$bot->sendMsgHeaded( $dest, "BTC",
	    "\x02Last:\x0f {$btcScraperCache['last']} ".
	    "\x02High:\x0f {$btcScraperCache['high']} ".
	    "\x02Low:\x0f {$btcScraperCache['low']} ".
	    "\x02Avg:\x0f {$btcScraperCache['avg']} ".
	    (   0 == $btcScraperCache['timestamp'] || 0 == $diff
	        ? ''
	        : " $diff second".($diff > 1 ? 's' : '' )." ago "
	    ).
	    "[VALUE OF] $num_btc BTC: ".
	    "\x02Last:\x0f \$".round($num_btc * floatval( substr( $btcScraperCache['last'], 1 ) ), 2 )." ".
            "\x02High:\x0f \$".round($num_btc * floatval( substr( $btcScraperCache['high'], 1 ) ), 2 )." ".
            "\x02Low:\x0f \$".round($num_btc * floatval( substr( $btcScraperCache['low'], 1 ) ), 2 )." ".
            "\x02Avg:\x0f \$".round($num_btc * floatval( substr( $btcScraperCache['avg'], 1 ) ), 2 )." "
	);
	return;
    }
    }

    if( isset( $res ) && false === $res )
        $bot->sendMsgHeaded( $dest, "BTC",
            "Currently unable to reach Mt. Gox."
        );
    else{
        $btcScraperLastPublic = time();
        $bot->sendMsgHeaded( $dest, "BTC",
            "\x02Last:\x0f {$btcScraperCache['last']} ".
            "\x02High:\x0f {$btcScraperCache['high']} ".
            "\x02Low:\x0f {$btcScraperCache['low']} ".
            "\x02Avg:\x0f {$btcScraperCache['avg']} ".
            (   0 == $btcScraperCache['timestamp'] || 0 == $diff
                ? ''
                : " $diff second".($diff > 1 ? 's' : '' )." ago"
            )
        );
    }

}


function btcScraper_mtgox_query($path, array $req = array()) {
	global $cfg;
    // API settings
    extract( $cfg['btcScraper'] );
 
    // generate a nonce as microtime, with as-string handling to avoid problems with 32bits systems
    $mt = explode(' ', microtime());
    $req['nonce'] = $mt[1].substr($mt[0], 2, 6);
 
    // generate the POST data string
    $post_data = http_build_query($req, '', '&');
 
    $prefix = '';
    if (substr($path, 0, 2) == '2/'){
        $prefix = substr($path, 2)."\0";
    }
 
    // generate the extra headers
    $headers = array(
        'Rest-Key: '.$key,
        'Rest-Sign: '.base64_encode(hash_hmac('sha512', $prefix.$post_data, base64_decode($secret), true)),
    );
 
    // our curl handle (initialize if required)
    static $ch = null;
    if (is_null($ch)) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/4.0 (compatible; MtGox PHP client; '.php_uname('s').'; PHP/'.phpversion().')');
    }
    curl_setopt($ch, CURLOPT_URL, 'https://data.mtgox.com/api/'.$path);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_TIMEOUT, 2);

    // run the query
    $res = curl_exec($ch);
    if ($res === false){
        echo('Could not get reply: '.curl_error($ch));
        return false;
    }
    $dec = json_decode($res, true);
    if (!$dec){
        echo('Invalid data received, please make sure connection is working and requested API exists');
        return false;
    }
    return $dec;
}

function ltcMode(&$bot, $parse) {
    global $cfg, $ltcCache, $ltcLastPublic;
    static $ch = null;

    $fields = array(
        'Last' => true,
        'High' => true,
        'Low' => true,
        'Avg' => true,
        'updated' => false,
    );

    $timeout = 300;
    $displayPubliclyTimeout = 30;

    if ( ! isset( $ltcLastPublic ) )
        $ltcLastPublic = 0;
    if ( ! isset( $ltcCache ) )
        $ltcCache = array();
    if ( ! isset( $ltcCache['updated'] ) )
        $ltcCache['updated'] = 0;

    $dest = ( time() > $ltcLastPublic + $displayPubliclyTimeout ? $parse['src'] : $parse['nick'] );

    if ( time() > $ltcCache['updated'] + $timeout ) {
        if (is_null($ch)) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_URL, 'https://btc-e.com/api/2/ltc_usd/ticker');
            curl_setopt($ch, CURLOPT_TIMEOUT, 2);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        }

        $res = curl_exec($ch);
        if ($res !== FALSE) {
            $dec = json_decode($res, true);
            if (!is_null($dec) && $dec != FALSE) {
                foreach ($fields as $field => $display) {
                    $ltcCache[$field] = $dec['ticker'][strtolower($field)];
                }
            } else {
                $bot->sendMsgHeaded($dest, 'LTC', 'Unable to parse API results');
                return false;
            }
        } else if (!isset($ltcCache['Last'])) {
            $bot->sendMsgHeaded($dest, 'LTC', 'Unable to reach BTC-e');
            return false;
        }
    }

    $diff = time() - $ltcCache['updated'];

    $msg = '';
    foreach ($fields as $field => $display) {
        if ($display)
            $msg .= ($msg == '' ? '' : ' ') . "\x02" . $field . "\x0f" . ': $' . $ltcCache[$field];
    }

    $msg .= ($ltcCache['updated'] == 0 || $diff == 0 ? '' : ' ' . $diff . ' second' . ($diff > 1 ? '' : 's') . ' ago');

    $bot->sendMsgHeaded($dest, 'LTC', $msg);

    return true;
}
