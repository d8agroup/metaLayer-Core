<?php
namespace Swiftriver\Core\Workflows\TwitterStreamingServices;
class StopTwitterStreamer extends TwitterStreamingServicesBase
{
    public function RunWorkflow($key)
    {
        parent::RegisterKey($key);
    	
        $logger = \Swiftriver\Core\Setup::GetLogger();
        $logger->log("Core::ServiceAPI::TwitterStreamingServices::StopTwitterStreamer::RunWorkflow [Method invoked]", \PEAR_LOG_INFO);

        $filename = \Swiftriver\Core\Setup::CachingDirectory() . "/TwitterStreamingController.go";

        if(\file_exists($filename))
            \unlink ($filename);

        $logger->log("Core::ServiceAPI::TwitterStreamingServices::StopTwitterStreamer::RunWorkflow [Method finished]", \PEAR_LOG_INFO);

        return '{"message":"OK"}';
    }
}
?>
