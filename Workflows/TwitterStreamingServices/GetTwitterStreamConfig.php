<?php
namespace Swiftriver\Core\Workflows\TwitterStreamingServices;
class GetTwiterStreamConfig extends TwitterStreamingServicesBase
{
    public function RunWorkflow($key)
    {
        parent::RegisterKey($key);
    	
        $filename = \Swiftriver\Core\Setup::CachingDirectory() . "/TwitterStreamingController.tmp";

        if(!\file_exists($filename))
            return parent::FormatMessage("no config");

        $fp = fopen($filename, "r+");
        $line = fread($fp, filesize($filename));
        fclose($fp);
        $parts = explode("|", $line);

        $object->TwitterUsername = $parts[0];
        $object->TwitterPassword = $parts[1];
        $object->SearchTerms = explode("~", $parts[2]);

        return parent::FormatReturn(\json_encode($object));
    }
}
?>
