<?php
include_once(dirname(__FILE__) . "/../../Setup.php");
include_once(dirname(__FILE__) . "/TwitterStreamingSearchClient.php");
$filename = \Swiftriver\Core\Setup::Configuration()->CachingDirectory . "/TwitterStreamingController.tmp";
$fp = fopen($filename, "r+");
$line = fread($fp, filesize($filename));
fclose($fp);

$parts = explode("|", $line);
$searchterms = explode("~", $parts[2]);


$gofile = str_replace(".tmp", ".go", $filename);
//delete the old go file - this will cause any existing streams to cancel
if(file_exists($gofile))
    unlink($gofile);

//wait for the existing stream to quit
sleep(10);

$fp = fopen($gofile, "w");
fclose($fp);

//start the new stream
$c = new \Swiftriver\Core\Modules\TwitterStreamingSearchClient($parts[0], $parts[1]);
$c->setTrack($searchterms);
$c->consume();
?>
