<?php
header('Content-type: application/json');
//Check for the existance of the unique Swift instance Key
if(!isset($_POST["key"])) {
    //If not found then return a JSON error
    echo '{"error":"The request to this service did not contain the required post data \'key\'"}';
    die();
}
if(!isset($_POST["apptemplate"])) {
    //If not found then return a JSON error
    echo '{"error":"The request to this service did not contain the required post data \'apptemplate\'"}';
    die();
}
//If all pre-checks are ok, attempt to run the API request
else {
    //include the setup file
    include_once(dirname(__FILE__)."/../../Setup.php");

    //create a new workflow instance
    $workflow = new Swiftriver\Core\Workflows\ApiKeys\RegisterApiKeyWithAppTemplate();

    $json = $workflow->RunWorkflow($_POST["key"], $_POST['apptemplate']);

    //Return the JSON result
    echo $json;
    die();
}
?>