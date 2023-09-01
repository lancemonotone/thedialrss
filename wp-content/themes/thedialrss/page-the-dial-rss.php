<?php namespace thedial;

// Set the content type to application/rss+xml
header( 'Content-Type: application/rss+xml; charset=utf-8' );

$url = get_field( 'the_dial_rss', 'options' );

$rss = RSS_Handler::fetchAndParse( $url );

echo $rss;

