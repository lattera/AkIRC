<?php
function linkTitlesModLink( $link ){
    list( $proto, $domainplus ) = explode( "://", $link, 2 );
    list( $domain, $args ) = explode( "/", $domainplus, 2 );
    echo "[TitleMod] Proto: $proto Domain: $domain Args: $args\n";

    // Convert imgur direct links to "gallery"/html versions
    if( "i.imgur.com" == $domain ){
        $ext = end( explode( '.', strtolower( $link ) ) );
        if( in_array( $ext, array( "jpg", "png", "gif", "jpeg") ) ){
            $link = substr( $link, 0, -( 1 + strlen( $ext ) ) );
            echo "[TitleMod] Link: $link\n";
        }
    }
    return $link;
}

function linkTitlesModTitle( $title, $link = '' ){
    list( $proto, $domainplus ) = explode( "://", $link, 2 );
    list( $domain, $args ) = explode( "/", $domainplus, 2 );

    // Google search results
    if( in_array( $domain, array( "google.com", "www.google.com" ) ) ){
        $tmp = explode( "&q=", $args );
        if( 1 < count( $tmp ) && "Google" == $title ){
            $tmp = explode( "&", $tmp[1] );
            echo "[TitleMod] Google query: ".urldecode( $tmp[0] )."\n";
            $title .= " - Query: ".urldecode( $tmp[0] );
        }
    }
    return $title;
}