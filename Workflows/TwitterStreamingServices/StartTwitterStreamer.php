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

        \exec($command);
    }
}
?>
