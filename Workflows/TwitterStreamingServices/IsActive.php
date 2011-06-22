<?php
namespace Swiftriver\Core\Workflows\TwitterStreamingServices;
class IsActive extends TwitterStreamingServicesBase
{
    public function RunWorkflow($key)
    {
        parent::RegisterKey($key);
    	
        $filename = \Swiftriver\Core\Setup::CachingDirectory() . "/TwitterStreamingController.go";

        if(!\file_exists($filename))
            return parent::FormatReturn('{"IsActive":false}');

        return parent::FormatReturn('{"IsActive":true}');
    }
}
?>
