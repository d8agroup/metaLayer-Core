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
        $this->queueDir = \Swiftriver\Core\Setup::Configuration()->CachingDirectory;
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
        $filename = \Swiftriver\Core\Setup::Configuration()->CachingDirectory . "/TwitterStreamingController.go";
        if(!\file_exists($filename))
            parent::disconnect();

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

        // Loop over each line (1 line per status)
        $statusCounter = 0;
        while ($rawStatus = fgets($fp, 4096))
        {
            $statusCounter ++;

            /** **************** NOTE ********************
            * This is the part where you would normally do your processing. If you're extracting/trending information
            * about the tweets it should happen here, where it doesn't matter so much if things are slow (you will
            * catch up on the next loop).
            */
            $logger->log($rawStatus, \PEAR_LOG_INFO);
            /*
            $data = json_decode($rawStatus, true);
            if (is_array($data) && isset($data['user']['screen_name']))
                $logger->log("Core::Modules::SiSPS::Parsers::TwitterParser::Stream " . $data['user']['screen_name'] . ': ' . urldecode($data['text']), \PEAR_LOG_INFO);
            */
        }

        // Release lock and close
        flock($fp, LOCK_UN);
        fclose($fp);

        // All done with this file
        $logger->log("Core::Modules::TwitterStreamingSearchClient finshed processing " . $queueFile, \PEAR_LOG_DEBUG);
        unlink($queueFile);

    }

}
?>