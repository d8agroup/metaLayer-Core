<?php
/**
* @author am[at]swiftly[dot]org
*/
namespace Swiftriver\Core\Workflows\ChannelServices;
class PushToChannel extends ChannelServicesBase {
     /**
     * Pushes content to a specific channel via its parser
     *
     * @return string $json
     */
    public function RunWorkflow($key)
    {
        parent::RegisterKey($key);
    	
        //Setup the logger
        $logger = \Swiftriver\Core\Setup::GetLogger();
        $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [Method invoked]", \PEAR_LOG_INFO);

        $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [START: Setting time out]", \PEAR_LOG_DEBUG);

        set_time_limit(300);

        $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [Getting the appropriate parser]", \PEAR_LOG_DEBUG);

        $origin = $_GET["origin"];
        $parser = \Swiftriver\Core\Modules\SiSPS\ParserFactory::GetParserByPushOrigin($origin);

        if(is_null($parser)) {
            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [INFO: No Parser for origin '$origin']", \PEAR_LOG_DEBUG);
            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [END: PushToChannel]", \PEAR_LOG_DEBUG);
            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [Method finished]", \PEAR_LOG_INFO);

            return parent::FormatErrorMessage("No parser exists for '$origin'");
        }

        $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [Extracting raw data]", \PEAR_LOG_DEBUG);

        $raw_content = file_get_contents('php://input');

        $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [END: Fetching next Channel]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [START: Get and parse content]", \PEAR_LOG_DEBUG);

        try
        {
            $post_content = null;

            if($_POST) {
                $post_content = $_POST;
            }

            $get_content = null;

            if($_GET) {
                $get_content = $_GET;
            }

            $file_content = null;

            if($_FILES) {
                $file_content = $_FILES;
            }

            $SiSPS = new \Swiftriver\Core\Modules\SiSPS\SwiftriverPushParsingService();
            $rawContent = $SiSPS->FetchContentFromChannel($parser, $raw_content, $post_content, $get_content, $file_content);
        }
        catch (\Exception $e)
        {
            $message = $e->getMessage();
            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [An exception was thrown]", \PEAR_LOG_DEBUG);
            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [$message]", \PEAR_LOG_ERR);

            return parent::FormatErrorMessage("An exception was thrown: $message");
        }


        if(isset($rawContent) && is_array($rawContent) && count($rawContent) > 0)
        {

            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [END: Get and parse content]", \PEAR_LOG_DEBUG);

            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [START: Running core processing]", \PEAR_LOG_DEBUG);

            try
            {
                $preProcessor = new \Swiftriver\Core\PreProcessing\PreProcessor();
                $processedContent = $preProcessor->PreProcessContent($rawContent);
            }
            catch (\Exception $e)
            {
                //get the exception message
                $message = $e->getMessage();
                $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [An exception was thrown]", \PEAR_LOG_DEBUG);
                $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [$message]", \PEAR_LOG_ERR);
                $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [Method finished]", \PEAR_LOG_INFO);
                return parent::FormatErrorMessage("An exception was thrown: $message");
            }

            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [END: Running core processing]", \PEAR_LOG_DEBUG);

            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [START: Save content to the data store]", \PEAR_LOG_DEBUG);

            try
            {
                $contentRepository = new \Swiftriver\Core\DAL\Repositories\ContentRepository();
                $contentRepository->SaveContent($processedContent);

                // Raise the event handler that handles the post processing of content

                $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [Raise the Ushahidi Push Event Handler]", \PEAR_LOG_DEBUG);

                $event = new \Swiftriver\Core\EventDistribution\GenericEvent(
                    \Swiftriver\Core\EventDistribution\EventEnumeration::$ContentPostProcessing,
                    $processedContent);

                $eventDistributor = new \Swiftriver\Core\EventDistribution\EventDistributor();

                $eventDistributor->RaiseAndDistributeEvent($event);

                $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [End Ushahidi Push event]", \PEAR_LOG_DEBUG);

            }
            catch (\Exception $e)
            {
                //get the exception message
                $message = $e->getMessage();
                $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [An exception was thrown]", \PEAR_LOG_DEBUG);
                $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [$message]", \PEAR_LOG_ERR);
                $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [Method finished]", \PEAR_LOG_INFO);
                return parent::FormatErrorMessage("An exception was thrown: $message");
            }

            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [END: Save content to the data store]", \PEAR_LOG_DEBUG);
        }
        else
        {
            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [END: Get and parse content]", \PEAR_LOG_DEBUG);
            $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [No content found.]", \PEAR_LOG_DEBUG);
        }

        $logger->log("Core::Workflows::ChannelServices::PushToChannel::RunWorkflow [Method finished]", \PEAR_LOG_INFO);

        return parent::FormatMessage("OK");
    }
}