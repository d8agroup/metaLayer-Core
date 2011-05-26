<?php
// header('Content-type: application/json');
//include the setup file
include_once(dirname(__FILE__)."/../../Setup.php");

// create a new workflow instance
$workflow = new \Swiftriver\Core\Workflows\ChannelServices\PushToChannel();

//If all the key is ok, then run the workflow
$json = json_decode($workflow->RunWorkflow("swiftriver_dev"));

if($json->message == "OK") {
    // Success
}
?>