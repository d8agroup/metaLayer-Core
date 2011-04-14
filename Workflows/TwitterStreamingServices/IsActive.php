<?php
namespace Swiftriver\Core\Workflows\TwitterStreamingServices;
class IsActive extends TwitterStreamingServicesBase
{
    public function RunWorkflow($key)
    {
        $filename = \Swiftriver\Core\Setup::Configuration()->CachingDirectory . "/TwitterStreamingController.go";

        if(!\file_exists($filename))
            return parent::FormatReturn('{"IsActive":false}');

        return parent::FormatReturn('{"IsActive":true}');
    }
}
?>
