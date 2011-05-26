<?php
/**
* @author am[at]swiftly[dot]org
*/
namespace Swiftriver\Core\Modules\SiSPS;
class SwiftriverPushParsingService {
/**
     * This method will take the information prvided in the
     * instance of a \Swiftriver\Core\ObjectModel\Source object
     * and will make a call to the channel to fetch and content
     * that can be fetched and then parse the content into an array
     * of Swiftriver\Core\ObjectModel\Content items
     *
     * @param \Swiftriver\Core\Modules\SiSPS\PushParser $parser
     * @param String $raw_content
     * @param $post_content
     * @param $get_content
     * @return Swiftriver\Core\ObjectModel\Content[] $contentItems
     */
    public function FetchContentFromChannel($parser, $raw_content = null, $post_content = null, $get_content = null, $file_content = null) {
        $logger = \Swiftriver\Core\Setup::GetLogger();
        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [Method invoked]", \PEAR_LOG_DEBUG);

        if((!isset($raw_content) || $raw_content == null)
           && (!isset($post_content) || $post_content == null)
           && (!isset($get_content) || $get_content == null)
           && (!isset($file_content) || $file_content == null)){
            $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [The channel object param is null]", \PEAR_LOG_DEBUG);
            $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [Method finished]", \PEAR_LOG_DEBUG);
            return;
        }

        //get the type of the channel

        $parserType = $parser->ReturnType();

        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [Parser type is $parserType]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [START: Constructed parser from factory]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [END: Constructed parser from factory]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [START: parser->GetAndParse]", \PEAR_LOG_DEBUG);

        try
        {
            //Get and parse all avaliable content items from the parser
            $contentItems = $parser->PushAndParse($raw_content, $post_content, $get_content, $file_content);
        }
        catch(\Exception $e)
        {
            $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [$e]", \PEAR_LOG_ERR);

            $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [Method finished]", \PEAR_LOG_DEBUG);

            return array();
        }

        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [END: parser->GetAndParse]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::FetchContentFromChannel [Method finished]", \PEAR_LOG_DEBUG);

        //Return the content items
        return $contentItems;
    }

    public function ListAvailableParsers(){
        $logger = \Swiftriver\Core\Setup::GetLogger();
        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::ListAvailableChannels [Method invoked]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::ListAvailableChannels [START: Getting All Parsers from the ParserFactory]", \PEAR_LOG_DEBUG);

        $parsers = ParserFactory::ReturnAllAvailablePushParsers();

        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::ListAvailableChannels [END: Getting All Parsers from the ParserFactory]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Modules::SiSPS::SwiftriverPushParsingService::ListAvailableChannels [Method finished]", \PEAR_LOG_DEBUG);

        return $parsers;
    }
}