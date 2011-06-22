<?php
/**
* @author am[at]swiftly[dot]org
*/
namespace Swiftriver\Core\Modules\SiSPS\PushParsers;
use Swiftriver\Core\ObjectModel;

use Swiftriver\Core\ObjectModel\ObjectFactories;

class MetalensImageTextPushParser implements IPushParser
{
    /**
     * Provided with the raw content, this method parses the raw content
     * and converts it to SwiftRiver content object model
     *
     * @param String $raw_content (if content gets sent raw)
     * @param String $post_content (if content gets sent as HTTP POST)
     * @param String $get_content (if content gets sent as HTTP GET)
     * @param String $file_content (if content gets sent a file over HTTP upload)
     * @return Swiftriver\Core\ObjectModel\Content[] contentItems
     */
    public function PushAndParse($raw_content = null, $post_content = null, $get_content = null, $file_content = null)
    {
    	$logger = \Swiftriver\Core\Setup::GetLogger();

        $logger->log("Core::Modules::SiSPS::PushParsers::MetalensImageTextPushParser::PushAndParse [Method invoked]", \PEAR_LOG_DEBUG);
    	
        $deviceId = $post_content['deviceid'];
        
        $text = $post_content['text'];
        
        $imageid = $post_content['imageid'];
        
        $type = $this->ReturnType();
        
        $source = ObjectFactories\SourceFactory::CreateSourceFromIdentifier($deviceid, true);
        
        $source->parent = $type;
        
        $source->name = "device_$deviceId";
        
        $source->type = $type;
        
        $source->subType = $type;
        
        $item = ObjectFactories\ContentFactory::CreateContent($source);
        
        $item->id = $imageid;
        
        $title = (strlen($text) > 30)
        	? substr($text, 0 , 30) . "..."
        	: $text;
        
        $item->text[] = new ObjectModel\LanguageSpecificText
        (
        	null, //we dotn know the language yet
        	$title,
        	array($text)
        );
        
        $item->date = time();
        
        $logger->log("Core::Modules::SiSPS::PushParsers::MetalensImageTextPushParser::PushAndParse [Method finished]", \PEAR_LOG_DEBUG);
        
        return array
        (
        	$item
        );
    }

    /**
     * This method returns a string describing the implementation details
     * of this parser
     *
     * @return string - implementation details
     */
    public function GetDescription()
    {
    	return "This is the PUSH interface for the gateway functions that pass text extracted via OCR to the core.";
    }

    /**
     * This function allows us to get the settings for each parser
     *
     * @return settings[]
     */
    public function GetSettings()
    {
    	return array
    	(
    		"trusted" => true,
            "file_upload" => false
    	);
    }

    /**
     * This method returns a string describing the type of sources
     * it can parse. For example, the RSSParser returns "Feeds".
     *
     * @return string type of sources parsed
     */
    public function ReturnType()
    {
    	return "metaLens";
    }
}
?>