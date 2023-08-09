<?php namespace thedial;
// Set the content type to application/rss+xml
header('Content-Type: application/rss+xml; charset=utf-8');

$rss = TheDialRSSParser::fetchAndParse();

// Echo the XML string
echo $rss;

