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

        // Create the stream context, which defines the HTTP settings. The HTTP settings are used
        // to fetch the feed content so it can be parsed.
        $context = stream_context_create( $options );

        // Fetch the feed content using file_get_contents.
        // local testing url for thedial.world.xml inside uploads
        self::$url = home_url() . '/wp-content/uploads/thedial.world.xml';
        $feed_content = file_get_contents( self::$url, false, $context );

        if ( $feed_content === false ) {
            // If the feed could not be retrieved, print the error and exit.
            $error = error_get_last();
            echo "Error loading the RSS feed: " . $error[ 'message' ];

            return false;
        }

        // Parse the feed content with simplexml_load_string.
        $rss = simplexml_load_string( $feed_content, null, LIBXML_NOCDATA );

        // Unset all but the first item using a for loop
        $count = count( $rss->channel->item );
        for ( $i = 1; $i < $count; $i++ ) {
            unset( $rss->channel->item[ 0 ] );
        }

        // Set the title for the channel node
        $rss->channel->title = 'The Dial';

        // Add the snf namespace to the channel
        $rss->addAttribute('xmlns:snf', 'http://www.smartnews.be/snf');

        // Create the snf:logo element in the channel
        $logoElement = $rss->channel->addChild('logo', null, 'http://www.smartnews.be/snf');

        // Add the url child element to snf:logo
        //$logoElement->addChild('url', 'https://images.squarespace-cdn.com/content/v1/6317a497832bf15c3b30f236/1709e958-43f5-4cb3-845a-9a7a9002aa79/Logo.png?format=700w');
        // $logoElement->addChild('url', 'https://images.squarespace-cdn.com/content/6317a497832bf15c3b30f236/2d804336-13a8-4b27-adc2-d3545d9e83c6/The+Dial+logo_black.png?content-type=image%2Fpng');
        $logoElement->addChild('url', home_url() . '/wp-content/themes/thedialrss/assets/build/images/The+Dial+logo_black.png');

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
                    $thumbnailUrl = (string)$media->content->attributes()[ 'url' ];

                    // Convert the SimpleXMLElement object to a DOM object for this specific item
                    $itemDom = dom_import_simplexml( $item );

                    // Create the 'media:thumbnail' element
                    $thumbnailElement = $itemDom->ownerDocument->createElementNS( 'http://www.rssboard.org/media-rss', 'media:thumbnail' );
                    $thumbnailElement->setAttribute( 'url', $thumbnailUrl );

                    // Append the 'media:thumbnail' element to the 'item' node
                    $firstChild = $itemDom->firstChild;
                    $itemDom->insertBefore($thumbnailElement, $firstChild);
                    // $itemDom->appendChild( $thumbnailElement );

                    // Optionally, you can unset the 'content' element if you no longer need it
                    unset( $media->content );



                    // Create a new 'snf:analytics' element
                    $analyticsElement = $itemDom->ownerDocument->createElement('snf:analytics');

                    // Create a CDATA section with the analytics script
                    $gaScript = <<<EOD
<script async src="https://www.googletagmanager.com/gtag/js?id=G-LTGBFE0083"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', 'G-LTGBFE0083');
</script>
EOD;

                    $cdata = $itemDom->ownerDocument->createCDATASection($gaScript);

                    // Append the CDATA section to the 'snf:analytics' element
                    $analyticsElement->appendChild($cdata);

                    // Append the 'snf:analytics' element to the 'item' node after the 'media:thumbnail' element
                    $itemDom->insertBefore($analyticsElement, $thumbnailElement->nextSibling);
                }
                // Add logic for other remapping rules as needed...
            }
        }

        // Convert the SimpleXMLElement object to a DOM object
        $dom               = dom_import_simplexml( $rss )->ownerDocument;
        $dom->formatOutput = true;

        // Find all the 'content:encoded' elements
        $contentEncodedTags = $dom->getElementsByTagNameNS( 'http://purl.org/rss/1.0/modules/content/', 'encoded' );

        // Wrap the content of each 'content:encoded' element in a CDATA section
        foreach ( $contentEncodedTags as $contentEncodedTag ) {
            $cdata                        = $dom->createCDATASection( $contentEncodedTag->nodeValue );
            $contentEncodedTag->nodeValue = ''; // Clear existing content
            $contentEncodedTag->appendChild( $cdata );
        }

        // Save the modified XML as a string
        $xmlString = $dom->saveXML();

        // Decode HTML entities
        return html_entity_decode( $xmlString, ENT_QUOTES, 'UTF-8' );
    }

}
