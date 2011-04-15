<?php
namespace Swiftriver\Core\Workflows\TwitterStreamingServices;
class StartTwitterStreamer extends TwitterStreamingServicesBase
{
    public function RunWorkflow($json, $key)
    {
        $logger = \Swiftriver\Core\Setup::GetLogger();
        $logger->log("Core::ServiceAPI::TwitterStreamingServices::StartTwitterStreamer::RunWorkflow [Method invoked]", \PEAR_LOG_INFO);

        $parameters = parent::ParseTwitterStreamingStartParametersFromJson($json);

        if(!isset($parameters))
        {
            $logger->log("Core::ServiceAPI::TwitterStreamingServices::StartTwitterStreamer::RunWorkflow [ERROR: Method ParseTwitterStreamingStartParametersFromJson returned null]", \PEAR_LOG_DEBUG);
            parent::FormatErrorMessage("There was an error in the JSON supplied, please consult the API documentation and try again.");
        }

        //create a channel for the streamed content
        $channelJson = '{"id":"TWITTERSTREAM","type":"Twitter Stream","subType":"Filter","name":"Twitter Stream","active":false}';
        $channel = \Swiftriver\Core\ObjectModel\ObjectFactories\ChannelFactory::CreateChannelFromJSON($channelJson);
        $channelRepository = new \Swiftriver\Core\DAL\Repositories\ChannelRepository();
        $channelRepository->SaveChannels(array($channel));

        $filename = \Swiftriver\Core\Setup::Configuration()->CachingDirectory . "/TwitterStreamingController.tmp";

        $fp = \fopen($filename, "w");
        $done = false;
        while(!$done)
        {
            if(\flock($fp, \LOCK_EX))
            {
                $searchArray = "";
                foreach($parameters["SearchTerms"] as $term)
                    $searchArray .= $term . "~";
                $searchArray = \rtrim($searchArray, '~');

                \fwrite($fp, $parameters["TwitterUsername"] . "|" . $parameters["TwitterPassword"] . "|" . $searchArray);

                \flock($fp, \LOCK_UN);

                $done = true;
            }
            else
            {
                \sleep(1);
            }
        }

        \fclose($fp);

        $command = "php " . \dirname(__FILE__) . "/../../Modules/Phirehose/starttwitterstreamer.php";

        $this->execInBackground($command);

        $logger->log("Core::ServiceAPI::TwitterStreamingServices::StartTwitterStreamer::RunWorkflow [Method finished]", \PEAR_LOG_INFO);

        return;
    }

    private function execInBackground($cmd)
    {
        if (substr(php_uname(), 0, 7) == "Windows")
        {
            pclose(popen("start /B ". $cmd, "r"));
        }
        else
        {
            exec($cmd . " > /dev/null &");
        }
    }
}
?>
