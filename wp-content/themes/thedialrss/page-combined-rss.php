<?php namespace thedial;

// Set the content type to application/rss+xml
header( 'Content-Type: application/rss+xml; charset=utf-8' );

$feeds = get_field( 'all_feeds', 'options' );

if ( ! count( $feeds ) ) {
    echo 'No feeds found';
    exit;
}


$all_feeds = RSS_Handler::fetchAndParseMultiple( $feeds );

// Echo the XML string
echo $all_feeds;

