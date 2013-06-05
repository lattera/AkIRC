<?php

function rss_construct( &$bot, &$vars ){
    global $cfg, $startEcho, $modVars;
    if( ! isset( $cfg['rss'] ) )
        return false;
    if( ! isset( $startEcho ) ) $startEcho = false;
    $bot->addHandler( 'loop', 'rss_checkFeed' );
    $bot->addHandler( 'join', 'rss_start' );
    return true;
}

function rss_checkFeed( &$bot, $parse ){
    global $modVars, $rss_lastRun, $startEcho, $cfg;
    if( ! $startEcho )
        return; // Not joined to any channels yet...

    if( ! isset( $modVars['rss'] ) )
        $modVars['rss'] = 0;
    if( ! isset( $rss_lastRun ) )
        $rss_lastRun = time();
    if( time() > $rss_lastRun + $cfg['rss']['interval'] ){
        $rss_lastRun = time();
	foreach( $cfg['rss']['feeds'] as $label => $url ){
	    $feed = New BlogFeed( $url );
	    $posts = array_reverse( $feed->posts );
	    $maxTs = $modVars['rss'];
	    foreach( $posts as $post ){
	        if( (int) $post->ts > $modVars['rss'] ){
	            foreach( $cfg['rss']['chans'] as $chan )
	                $bot->sendMsgHeaded( $chan, 'RSS', "\x02Feed:\x0f $label \x02Link:\x0f {$post->link} \x02Title:\x0f ".substr( $post->title, 0, 50 )." \x02Text:\x0f ".substr( strip_tags( $post->text ), 0, 100 ) );
	            $maxTs = max( $modVars['rss'], (int) $post->ts );
	        }
	    }
	    $modVars['rss'] = $maxTs;
	}
    }
}

function rss_start( &$bot, $parse ){
    global $startEcho;
    $startEcho = true;
    $bot->delHandler( 'join', 'rss_start' );
}

class BlogPost
{
    var $date;
    var $ts;
    var $link;

    var $title;
    var $text;
}

class BlogFeed
{
    var $posts = array();

    function BlogFeed($file_or_url){
        if(!eregi('^http:', $file_or_url))
            $feed_uri = $_SERVER['DOCUMENT_ROOT'] .'/shared/xml/'. $file_or_url;
        else
            $feed_uri = $file_or_url;

    $ctx = stream_context_create( array(
                'http'=> array(
                        'timeout' => 5
                )
            )
    );
        $xml_source = file_get_contents( $feed_uri, false, $ctx );
        if( false === $xml_source )
		return;
        $x = simplexml_load_string($xml_source);

        if(count($x) == 0)
            return;

        foreach($x->channel->item as $item)
        {
            $post = new BlogPost();
            $post->date = (string) $item->pubDate;
            $post->ts = strtotime($item->pubDate);
            $post->link = (string) $item->link;
            $post->title = (string) $item->title;
            $post->text = (string) $item->description;

            // Create summary as a shortened body and remove images, extraneous line breaks, etc.
            $summary = $post->text;
            $summary = eregi_replace("<img[^>]*>", "", $summary);
            $summary = eregi_replace("^(<br[ ]?/>)*", "", $summary);
            $summary = eregi_replace("(<br[ ]?/>)*$", "", $summary);

            // Truncate summary line to 100 characters
            $max_len = 100;
            if(strlen($summary) > $max_len)
                $summary = substr($summary, 0, $max_len) . '...';

            $post->summary = $summary;

            $this->posts[] = $post;
        }
    }
}
