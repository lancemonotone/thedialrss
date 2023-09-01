<?php namespace thedial;

use Exception;

class RSS_Handler {
    private static bool $debug = false;
    private static string $debugFeed = '/wp-content/uploads/thedial.world.xml';
    private static string $url;

    // Define the remapping rules.
    private static array $remapping = [
        'item.media:content' => 'item.media:thumbnail',
        // Add more remapping rules here if needed.
    ];

    // Analytics script
    private static string $analyticsScript = '
  <script>
  (function(i,s,o,g,r,a,m){i[\'GoogleAnalyticsObject\']=r;i[r]=i[r]||function(){
  (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
  m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
  })(window,document,\'script\',\'//www.google-analytics.com/analytics.js\',\'ga\');

  ga(\'create\', \'UA-xxx-2\', \'examplecom\');
  ga(\'require\', \'displayfeatures\');
  ga(\'set\', \'referrer\', \'http://www.smartnews.com/\');
  ga(\'send\', \'pageview\', \'/260984/upsee/\');
  </script>
';

    public static function fetchAndParseMultiple( $feeds ): string {
        $combined = [];
        foreach ( $feeds as $feed => $url ) {
            try {
                $combined [] = RSS_Handler::fetchAndParse( $url[ 'issue_feed' ] );
            } catch ( \Exception $e ) {
                echo $e->getMessage();
            }
        }

        return implode( ' ', $combined );
    }

    /**
     * @throws Exception
     */
    public static function fetchAndParse( string $url ): bool|string {
        self::getUrl( $url );

        if ( ! self::$url ) {
            throw new Exception( 'No URL provided:' . self::$url );
        }

        $url_hash = md5( self::$url );
        if ( $rss = get_transient( 'thedial_rss_' . $url_hash ) ) {
            return $rss;
        }

        try {
            $context      = self::createContext();
            $feed_content = self::fetchFeedContent( $context );
        } catch ( \Exception $e ) {
            return $e->getMessage();
        }

        $rss = self::parseFeedContent( $feed_content );

        self::modifyFeed( $rss );

        // if we get this far, clear all the caches
        // Cache::clearCache();

        // save to a transient with a 12-hour expiration
        $rss = self::generateXMLString( $rss );
        set_transient( 'thedial_rss_' . $url_hash, $rss, 12 * HOUR_IN_SECONDS );

        return $rss;
    }

    private static function getUrl( $url ): void {
        if ( self::$debug ) {
            self::$url = home_url() . self::$debugFeed;

            return;
        }

        self::$url = $url ?: 'This is not a valid URL: ' . $url;
    }

    private static function createContext() {
        $options = [
            'http' => [
                'header' => "User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13\r\n"
            ]
        ];

        return stream_context_create( $options );
    }

    private static function fetchFeedContent( $context ) {
        // self::$url = home_url() . '/wp-content/uploads/thedial.world.xml';
        $feed_content = file_get_contents( self::$url, false, $context );

        if ( $feed_content === false ) {
            $error = error_get_last();
            echo "Error loading the RSS feed: " . $error[ 'message' ];
        }

        return $feed_content;
    }

    private static function parseFeedContent( $feed_content ) {
        return simplexml_load_string( $feed_content, null, LIBXML_NOCDATA );
    }

    private static function modifyFeed( $rss ) {
        // All the logic related to modifying the RSS feed goes here
        // ...
        // For example:
        self::setTitleAndLogo( $rss );
        self::applyRemappingRules( $rss );
        self::wrapContentEncodedInCDATA( $rss );
    }

    private static function setTitleAndLogo( $rss ) {
        // Set the title for the channel node
        $rss->channel->title = 'The Dial';

        // Add the snf namespace to the channel
        $rss->addAttribute( 'xmlns:snf', 'http://www.smartnews.be/snf' );

        // Create the snf:logo element in the channel
        $logoElement = $rss->channel->addChild( 'logo', null, 'http://www.smartnews.be/snf' );
        $logoElement->addChild( 'url', home_url() . '/wp-content/themes/thedialrss/assets/build/images/The+Dial+logo_black.png' );
    }

    private static function applyRemappingRules( $rss ) {
        foreach ( $rss->channel->item as $item ) {
            $content = $item->children( 'http://purl.org/rss/1.0/modules/content/' );
            $media   = $item->children( 'http://www.rssboard.org/media-rss' );

            foreach ( self::$remapping as $from => $to ) {
                if ( $from === 'item.media:content' ) {
                    self::handleMediaContentRemapping( $item, $content, $media );
                }
                // Add logic for other remapping rules as needed...
            }

            self::sanitizeCreator( $item );
            self::addAnalyticsElement( $rss, $item );
        }
    }

    private static function sanitizeCreator( $item ) {
        $dc = $item->children( 'http://purl.org/dc/elements/1.1/' );
        if ( isset( $dc->creator ) ) {
            $dc->creator = str_replace( '&', '&amp;', $dc->creator );
        }
    }

    private static function addAnalyticsElement( $rss, $item ) {
        // Get the DOM object for the 'item' node
        $itemDom = dom_import_simplexml( $item );

        // Convert the SimpleXMLElement object to a DOM object
        $dom               = dom_import_simplexml( $rss )->ownerDocument;
        $dom->formatOutput = true;


        // Create a new 'snf:analytics' element
        $analyticsElement = $dom->createElement( 'snf:analytics' );

        // Create a CDATA section with the analytics script
        $cdata = $dom->createCDATASection( self::$analyticsScript );

        // Append the CDATA section to the 'snf:analytics' element
        $analyticsElement->appendChild( $cdata );

        // Append the 'snf:analytics' element to the 'item' node after the 'media:thumbnail' element
        $itemDom->appendChild( $analyticsElement );
    }

    private static function handleMediaContentRemapping( $item, $content, $media ) {
        $thumbnailUrl = (string)$media->content->attributes()[ 'url' ];
        $contentDom   = self::createContentDom( $content );
        self::insertThumbnail( $contentDom, $thumbnailUrl );
        $content->encoded = $contentDom->saveHTML();
        $thumbnailElement = self::createThumbnailElement( $item, $thumbnailUrl );
        unset( $media->content );
    }

    private static function createContentDom( $content ) {
        $contentDom            = new \DOMDocument();
        $libxml_previous_state = libxml_use_internal_errors( true );
        $contentDom->loadHTML( mb_convert_encoding( (string)$content->encoded, 'HTML-ENTITIES', 'UTF-8' ) );
        libxml_use_internal_errors( $libxml_previous_state );

        return $contentDom;
    }

    private static function insertThumbnail( $contentDom, $thumbnailUrl ) {
        $img = $contentDom->createElement( 'img' );
        $img->setAttribute( 'src', $thumbnailUrl );
        $img->setAttribute( 'alt', 'Thumbnail' );
        $p = $contentDom->createElement( 'p' );
        $p->appendChild( $img );
        $firstParagraph = $contentDom->getElementsByTagName( 'p' )->item( 0 );
        $firstParagraph->parentNode->insertBefore( $p, $firstParagraph );
    }

    private static function createThumbnailElement( $item, $thumbnailUrl ) {
        $itemDom          = dom_import_simplexml( $item );
        $thumbnailElement = $itemDom->ownerDocument->createElementNS( 'http://www.rssboard.org/media-rss', 'media:thumbnail' );
        $thumbnailElement->setAttribute( 'url', $thumbnailUrl );
        $firstChild = $itemDom->firstChild;
        $itemDom->insertBefore( $thumbnailElement, $firstChild );

        return $thumbnailElement;
    }

    private static function wrapContentEncodedInCDATA( $rss ) {
        // Convert the SimpleXMLElement object to a DOM object
        $dom = dom_import_simplexml( $rss )->ownerDocument;

        // Find all the 'content:encoded' elements
        $contentEncodedTags = $dom->getElementsByTagNameNS( 'http://purl.org/rss/1.0/modules/content/', 'encoded' );

        // Wrap the content of each 'content:encoded' element in a CDATA section
        foreach ( $contentEncodedTags as $contentEncodedTag ) {
            $cdata                        = $dom->createCDATASection( $contentEncodedTag->nodeValue );
            $contentEncodedTag->nodeValue = ''; // Clear existing content
            $contentEncodedTag->appendChild( $cdata );
        }
    }

    private static function generateXMLString( $rss ) {
        $dom               = dom_import_simplexml( $rss )->ownerDocument;
        $dom->formatOutput = true;
        $xmlString         = $dom->saveXML();

        return html_entity_decode( $xmlString, ENT_QUOTES, 'UTF-8' );
    }
}
