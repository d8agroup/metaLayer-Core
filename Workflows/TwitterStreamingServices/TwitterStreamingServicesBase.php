<?php
namespace Swiftriver\Core\Workflows\TwitterStreamingServices;
/**
 * @author mg[at]swiftly[dot]org
 */
class TwitterStreamingServicesBase extends \Swiftriver\Core\Workflows\WorkflowBase
{
    public function ParseTwitterStreamingStartParametersFromJson($json)
    {
        $object = \json_decode($json);

        return array
        (
            "TwitterUsername" => $object->TwitterUsername,
            "TwitterPassword" => $object->TwitterPassword,
            "SearchTerms" => $object->SearchTerms,
        );
    }
}
?>
