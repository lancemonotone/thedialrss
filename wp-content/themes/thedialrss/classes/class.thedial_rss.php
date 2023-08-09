<?php namespace thedial;

use Exception;

class TheDialRSSParser {
    private static string $url;
    // Define the remapping rules.
    private static array $remapping = [
        'item.media:content' => 'item.media:thumbnail',
        // Add more remapping rules here if needed.
    ];

    public function __construct() {
    }

    /**
     * @throws Exception
     */
    public static function fetchAndParse(): bool|string {
        // Retrieve the URL from the ACF options page.
        self::$url = get_field( 'the_dial_rss', 'options' );

        if ( ! self::$url ) {
            return false;
        }

        // Enable user agent, some servers require it.
        $options = [
            'http' => [
                'header' => "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13\r\n"
            ]
        ];
        $context = stream_context_create( $options );

        // Fetch the feed content using file_get_contents.
        $feed_content = file_get_contents( self::$url, false, $context );

        if ( $feed_content === false ) {
            // If the feed could not be retrieved, print the error and exit.
            $error = error_get_last();
            echo "Error loading the RSS feed: " . $error[ 'message' ];

            return false;
        }

        // Parse the feed content with simplexml_load_string.
        $rss = simplexml_load_string( $feed_content, null, LIBXML_NOCDATA );

        // After loading the RSS feed into $rss
        foreach ( $rss->getNamespaces( true ) as $key => $value ) {
            $rss->registerXPathNamespace( $key, $value );
        }

        // Apply the remapping rules.
        foreach ( $rss->channel->item as $item ) {
            // Access the namespaced elements
            $content = $item->children( 'http://purl.org/rss/1.0/modules/content/' );
            $media   = $item->children( 'http://www.rssboard.org/media-rss' );
            $dc      = $item->children( 'http://purl.org/dc/elements/1.1/' );

            foreach ( self::$remapping as $from => $to ) {
                [ $parent, $fromElement ] = explode( '.', $from );
                [ , $toElement ] = explode( '.', $to );

                // Handle the specific remapping of 'item.media:content' to 'item.media:thumbnail'
                if ( $parent === 'item' && $fromElement === 'media:content' ) {
                    /// Get the URL from the content attribute
                    $thumbnailURL = (string)$media->content->attributes()['url'];

                    // Check if the URL is retrieved successfully
                    if ($thumbnailURL) {
                        // Add the thumbnail element within the media namespace
                        $media->addChild('thumbnail', $thumbnailURL, 'http://www.rssboard.org/media-rss');

                        // Optionally, unset the content element
                        // unset($media->content);
                    }
                }
                // Add logic for other remapping rules as needed...
            }
        }

        // Convert the SimpleXMLElement object to a string
        $xmlString = $rss->asXML();

        // Decode HTML entities
        return html_entity_decode( $xmlString, ENT_QUOTES, 'UTF-8' );
    }
}
