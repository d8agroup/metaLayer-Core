<?php

/**
 * @author am[at]swiftly[dot]org
 */

namespace Swiftriver\Core\Modules\SiSPS\PushParsers;
interface IPushParser{
    /**
     * Provided with the raw content, this method parses the raw content
     * and converts it to SwiftRiver content object model
     *
     * @param String $raw_content (if content gets sent raw)
     * @param String $post_content (if content gets sent as HTTP POST)
     * @param String $get_content (if content gets sent as HTTP GET)
     * @return Swiftriver\Core\ObjectModel\Content[] contentItems
     */
    public function PushAndParse($raw_content = null, $post_content = null, $get_content = null);

    /**
     * This method returns a string describing the implementation details
     * of this parser
     *
     * @return string - implementation details
     */
    public function GetDescription();

    /**
     * This method returns a string describing the type of sources
     * it can parse. For example, the RSSParser returns "Feeds".
     *
     * @return string type of sources parsed
     */
    public function ReturnType();
}
?>
