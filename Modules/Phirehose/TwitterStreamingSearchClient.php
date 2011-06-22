<?php
namespace Swiftriver\Core\Modules;
include_once(dirname(__FILE__)."/Phirehose.php");
class TwitterStreamingSearchClient extends Phirehose
{
    /**
    * Subclass specific constants
    */
    const QUEUE_FILE_PREFIX = 'phirehose-queue';
    const QUEUE_FILE_ACTIVE = '.phirehose-queue.current';

    /**
    * Member attributes specific to this subclass
    */
    protected $queueDir;
    protected $rotateInterval;
    protected $streamFile;
    protected $statusStream;
    protected $lastRotated;


    public function __construct($username, $password)
    {
        $this->queueDir = \Swiftriver\Core\Setup::CachingDirectory();
        $this->rotateInterval = 5;

        return parent::__construct($username, $password, Phirehose::METHOD_FILTER);
    }

    public function enqueueStatus($status)
    {
        fputs($this->getStream(), $status);

        $now = time();
        if (($now - $this->lastRotated) > $this->rotateInterval)
        {
            // Mark last rotation time as now
            $this->lastRotated = $now;

            // Rotate it
            $this->rotateStreamFile();
        }
    }

    public function checkFilterPredicates()
    {
        $filename = \Swiftriver\Core\Setup::CachingDirectory() . "/TwitterStreamingController.go";
        if(!\file_exists($filename))
            parent::disconnect();

        $sec = (int) date('s');

        //this is called every 5 secs so to give us a break we ease off in the last 30 seconds of every minute
        if($sec > 30)
            return;

        $logger = \Swiftriver\Core\Setup::GetLogger();

        $queueFiles = glob($this->queueDir . '/phirehose-queue*.queue');

        $logger->log("Core::Modules::TwitterStreamingSearchClient Found " . count($queueFiles) . " queue files.", \PEAR_LOG_DEBUG);

        foreach ($queueFiles as $queueFile)
            $this->processQueueFile($queueFile);

    }

    public function log($message)
    {
        $logger = \Swiftriver\Core\Setup::GetLogger();
        if (\strpos($message, "HTTP ERROR 401: Unauthorized ("))
        {
            $logger->log("Core::Modules::TwitterStreamingSearchClient " . $message, \PEAR_LOG_ERR);
            $this->disconnect();
        }
        else
        {
            $logger->log("Core::Modules::TwitterStreamingSearchClient " . $message, \PEAR_LOG_DEBUG);
        }
    }

      /**
   * Returns a stream resource for the current file being written/enqueued to
   *
   * @return resource
   */
    private function getStream()
    {
        // If we have a valid stream, return it
        if (is_resource($this->statusStream))
            return $this->statusStream;

        // If it's not a valid resource, we need to create one
        if (!is_dir($this->queueDir) || !is_writable($this->queueDir))
            throw new Exception('Unable to write to queueDir: ' . $this->queueDir);

        // Construct stream file name, log and open
        $this->streamFile = $this->queueDir . '/' . self::QUEUE_FILE_ACTIVE;
        $this->log('Opening new active status stream: ' . $this->streamFile);
        $this->statusStream = fopen($this->streamFile, 'a'); // Append if present (crash recovery)

        // Ok?
        if (!is_resource($this->statusStream))
            throw new Exception('Unable to open stream file for writing: ' . $this->streamFile);

        // If we don't have a last rotated time, it's effectively now
        if ($this->lastRotated == NULL)
            $this->lastRotated = time();

        // Looking good, return the resource
        return $this->statusStream;
    }

    /**
    * Rotates the stream file if due
    */
    private function rotateStreamFile()
    {
        // Close the stream
        fclose($this->statusStream);

        // Create queue file with timestamp so they're both unique and naturally ordered
        $queueFile = $this->queueDir . '/' . self::QUEUE_FILE_PREFIX . '.' . date('Ymd-His') . '.queue';

        // Do the rotate
        rename($this->streamFile, $queueFile);

        // Did it work?
        if (!file_exists($queueFile))
            throw new Exception('Failed to rotate queue file to: ' . $queueFile);

        // At this point, all looking good - the next call to getStream() will create a new active file
        $this->log('Successfully rotated active stream to queue file: ' . $queueFile);
    }

    /**
    * Processes a queue file and does something with it (example only)
    * @param string $queueFile The queue file
    */
    protected function processQueueFile($queueFile)
    {
        $logger = \Swiftriver\Core\Setup::GetLogger();

        $logger->log("Core::Modules::TwitterStreamingSearchClient start processing " . $queueFile, \PEAR_LOG_DEBUG);

        // Open file
        $fp = fopen($queueFile, 'r');

        // Check if something has gone wrong, or perhaps the file is just locked by another process
        if (!is_resource($fp))
        {
            $logger->log("Core::Modules::TwitterStreamingSearchClient file: " . $queueFile . " already open, skipping", \PEAR_LOG_DEBUG);
            return FALSE;
        }

        // Lock file
        flock($fp, LOCK_EX);

        $content = array();

        $logger->log("Core::Modules::TwitterStreamingSearchClient: START Looping through content", \PEAR_LOG_DEBUG);

        while ($rawStatus = fgets($fp, 4096))
        {
            try
            {
                $status = \json_decode($rawStatus);

                $source_name = $status->user->screen_name;
                if ($source_name == null || $source_name == "")
                    $source_name = "UNKNOWN";
                $source = \Swiftriver\Core\ObjectModel\ObjectFactories\SourceFactory::CreateSourceFromIdentifier($source_name, false);
                $source->name = $source_name;
                $source->link = "http://twitter.com/" . $source_name;

                $source->parent = "TWITTERSTREAM";
                $source->type = "Twitter Stream";
                $source->subType = "Filter";
                $source->applicationIds["twitter"] = $status->user->id;
                $source->applicationProfileImages["twitter"] = $status->user->profile_image_url;

                //Create a new Content item
                $item = \Swiftriver\Core\ObjectModel\ObjectFactories\ContentFactory::CreateContent($source);

                //Fill the Content Item
                $item->text[] = new \Swiftriver\Core\ObjectModel\LanguageSpecificText(
                        null, //here we set null as we dont know the language yet
                        $status->text,
                        array($status->text));

                $item->link = "http://twitter.com/" . $source_name . "/statuses/" . $status->id_str;
                $item->date = strtotime($status->created_at);

                /* GEO is not yet supported on this streamin feed
                if($tweet->geo != null && $tweet->geo->type == "Point" && \is_array($tweet->geo->coordinates))
                    $item->gisData[] = new \Swiftriver\Core\ObjectModel\GisData (
                            $tweet->geo->coordinates[1],
                            $tweet->geo->coordinates[0],
                            "");
                */

                //Sanitize the tweet text into a DIF collection
                $sanitizedTweetDiffCollection = $this->ParseTweetToSanitizedTweetDiffCollection($item);

                //Add the dif collection to the item
                $item->difs = array($sanitizedTweetDiffCollection);

                $content[] = $item;
            }
            catch (\Exception $e)
            {
                $logger->log("Core::Modules::TwitterStreamingSearchClient: $e", \PEAR_LOG_ERR);
            }
        }

        $logger->log("Core::Modules::TwitterStreamingSearchClient: START Looping through content", \PEAR_LOG_DEBUG);

        $workflow = new \Swiftriver\Core\Workflows\ContentServices\ProcessContent();

        //Here we are running the workflow without pre processing.
        $workflow->RunWorkflow($content, false);

        // Release lock and close
        flock($fp, LOCK_UN);
        fclose($fp);

        // All done with this file
        $logger->log("Core::Modules::TwitterStreamingSearchClient finshed processing " . $queueFile, \PEAR_LOG_DEBUG);
        unlink($queueFile);

    }

    /**
     * @param \Swiftriver\Core\ObjectModel\Content $item
     * @return \Swiftriver\Core\ObjectModel\DuplicationIdentificationFieldCollection
     */
    private function ParseTweetToSanitizedTweetDiffCollection($item) {
        //Get the original text
        $tweetText = $item->text[0]->title;

        //Break the text down into words
        $tweetTextParts = explode(" ", $tweetText);

        //Set up the sanitized return string
        $sanitizedText = "";

        //loop through all the words
        foreach($tweetTextParts as $part) {
            //to lowwer the word
            $part = strtolower($part);

            //If the word contains none standard chars, continue
            if(preg_match("/[^\w\d\.\(\)\!']/si", $part))
                continue;

            //if the owrd is just rt then continue
            if($part == "rt")
                continue;

            //Add the word to the sanitized
            $sanitizedText .= $part . " ";
        }

        //Create a new Diff
        $dif = new \Swiftriver\Core\ObjectModel\DuplicationIdentificationField(
                "Sanitized Tweet",
                utf8_encode($sanitizedText)
        );

        //Create the new diff collection
        $difCollection = new \Swiftriver\Core\ObjectModel\DuplicationIdentificationFieldCollection(
                "Sanitized Tweet",
                array($dif)
        );

        //Return the diff collection
        return $difCollection;
    }

}
?>