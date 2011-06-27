<?php
namespace Swiftriver\Core;
/**
 * @author mg[at]swiftly[dot]org
 */
use Swiftriver\Core\DAL\Repositories;

class Setup
{
	/**
	 * Static variable holding the current API key
	 * 
	 * @var string
	 */
	public static $requestKey; 
	
	/**
	 * Static variable used to hold the modules directory
	 * 
	 * @var string
	 */
	private static $modulesDirectory;
	
	/**
	 * Static variable used to hold the caching directory
	 * 
	 * @var string
	 */
	private static $cachingDirectory;
	
    /**
     * Static variable for the core configuration handler
     *
     * @var Configuration\ConfigurationHandlers\CoreConfigurationHandler
     */
    private static $configuration;
    
    /**
     * Static variable for the DAL Config handler
     * 
     * @var Configuration\ConfigurationHandlers\DALConfigurationHandler 
     */
    private static $dalConfiguration;

    /**
     * Static variable for the Pre Processing Config handler
     *
     * @var Configuration\ConfigurationHandlers\PreProcessingStepsConfigurationHandler
     */
    private static $preProcessingStepsConfiguration;

    /**
     * Static variable for the Event Distribution Config handler
     *
     * @var Configuration\ConfigurationHandlers\EventDistributionConfigurationHandler
     */
    private static $eventDistributionConfiguration;

    /**
     * Static variable for the Dynamic module Config handler
     *
     * @var Configuration\ConfigurationHandlers\DynamicModuleConfigurationHandler
     */
    private static $dynamicModuleConfiguration;
    
    public static function InitWithAPIKey($apiKey, $appTemplate = null)
    {
    	self::$requestKey = $apiKey;
    	
    	//TODO: Add the checking and setup of the DAL, Config and Logging systems that relies on the apiKey 
    	
    	$filenameAddition = ($apiKey == "swiftriver") ? "" : $apiKey . "_";
    	
    	$configDirectory = dirname(__FILE__) . "/Configuration/ConfigurationFiles/";
    	
    	
    	//If there is no cofig for this apiKey then copy over the base templates
    	if(!file_exists($configDirectory . $filenameAddition . "DALConfiguration.xml"))
    	{
			$templatesDirectory = ($appTemplate == null)
				? $configDirectory . "ConfigurationFileTemplates/"
				: $configDirectory . "ConfigurationFileTemplates/" . $appTemplate . "/"; 
				
    		
			$files = array
    		(
    			$templatesDirectory . "CoreConfiguration.xml" 				=> $configDirectory . $filenameAddition . "CoreConfiguration.xml",
    			$templatesDirectory . "DALConfiguration.xml" 				=> $configDirectory . $filenameAddition . "DALConfiguration.xml",
    			$templatesDirectory . "DynamicModuleConfiguration.xml" 		=> $configDirectory . $filenameAddition . "DynamicModuleConfiguration.xml",
    			$templatesDirectory . "EventDistributionConfiguration.xml" 	=> $configDirectory . $filenameAddition . "EventDistributionConfiguration.xml",
    			$templatesDirectory . "PreProcessingStepsConfiguration.xml" => $configDirectory . $filenameAddition . "PreProcessingStepsConfiguration.xml"
    		);
    		
    		foreach($files as $source => $destination)
    		{
    			copy($source, $destination);
    			chmod($destination, 0777);
    		}
    	}
    	
    	//Include the DAL Data Context Setup file
    	$relativeDir = Setup::DALConfiguration()->DataContextDirectory;
    	if(isset($relativeDir) && $relativeDir != "") 
    	{
    		$directory = Setup::ModulesDirectory().$relativeDir;
    		if(file_exists($directory)) 
    		{
    			//include the setup file - if there is one
    			$setupfile = $directory."/Setup.php";
    			if(file_exists($setupfile))
    				include_once($setupfile);
    		}
    	}
    	
    	//Use the repository to check for and if required to create the data context for this apikey
    	$repository = new Repositories\APIKeyRepository();
    	if(!$repository->IsRegisterdCoreAPIKey($apiKey))
    		$repository->AddRegisteredAPIKey($apiKey, $appTemplate);
    }
    

    /**
     * Get the shared instance for the logger
     * @return \Log
     */
    public static function GetLogger()
    {
    	$requestKey = self::$requestKey;
    	$logfileName = ($requestKey != 'swiftriver')
    		? $requestKey . ".log"
    		: "log.log";
    	
        $log = new \Log("this message is ignored, however not supplying one throws an error :o/");

        $logger = $log->singleton('file', Setup::CachingDirectory()."/".$logfileName , '   ');
        
        $logLevelMask = self::Configuration()->LogLevelMask;
        
        switch($logLevelMask)
        {
        	case "debug": $mask = \Log::UPTO(\PEAR_LOG_DEBUG); break;
        	case "notice": $mask = \Log::UPTO(\PEAR_LOG_NOTICE); break;
        	case "info": $mask = \Log::UPTO(\PEAR_LOG_INFO); break;
        	default: $mask = \Log::UPTO(\PEAR_LOG_ERR); break;
        }
        
        $logger->setMask($mask);

        return $logger;
    }
    
    /**
     * Static access to the modules directory path
     * 
     * @return string
     */
    public static function ModulesDirectory()
    {
    	if(isset(self::$modulesDirectory))
    		return self::$modulesDirectory;
    		
    	self::$modulesDirectory = dirname(__FILE__) . "/Modules";
    	return self::$modulesDirectory;
    } 
    
    /**
     * Static access to the cache directory path
     * 
     * @return string
     */
    public static function CachingDirectory()
    {
    	if(isset(self::$cachingDirectory))
    		return self::$cachingDirectory;
    		
    	self::$cachingDirectory = dirname(__FILE__) . "/Cache";
    	return self::$cachingDirectory;
    }

    /**
     * Static access to the Core Config handler
     *
     * @return Configuration\ConfigurationHandlers\CoreConfigurationHandler
     */
    public static function Configuration()
    {
        if(isset(self::$configuration))
            return self::$configuration;
            
        $requestKey = self::$requestKey;
        $configFileName = ($requestKey != 'swiftriver')
    		? dirname(__FILE__)."/Configuration/ConfigurationFiles/" . $requestKey . "_CoreConfiguration.xml"
    		: dirname(__FILE__)."/Configuration/ConfigurationFiles/CoreConfiguration.xml";

        self::$configuration = new Configuration\ConfigurationHandlers\CoreConfigurationHandler($configFileName);

        return self::$configuration;
    }

    /**
     * Static access to the DAL Config handler
     *
     * @return Configuration\ConfigurationHandlers\DALConfigurationHandler
     */
    public static function DALConfiguration()
    {
        if(isset(self::$dalConfiguration))
            return self::$dalConfiguration;

        $requestKey = self::$requestKey;
        $configFileName = ($requestKey != 'swiftriver')
    		? dirname(__FILE__)."/Configuration/ConfigurationFiles/" .$requestKey . "_DALConfiguration.xml"
    		: dirname(__FILE__)."/Configuration/ConfigurationFiles/DALConfiguration.xml";

        self::$dalConfiguration = new Configuration\ConfigurationHandlers\DALConfigurationHandler($configFileName);

        return self::$dalConfiguration;
    }

    /**
     * Static access to the Pre Processing Steps config handler
     *
     * @return Configuration\ConfigurationHandlers\PreProcessingStepsConfigurationHandler
     */
    public static function PreProcessingStepsConfiguration()
    {
        if(isset(self::$preProcessingStepsConfiguration))
            return self::$preProcessingStepsConfiguration;

        $requestKey = self::$requestKey;
        $configFileName = ($requestKey != 'swiftriver')
    		? dirname(__FILE__)."/Configuration/ConfigurationFiles/" . $requestKey . "_PreProcessingStepsConfiguration.xml"
    		: dirname(__FILE__)."/Configuration/ConfigurationFiles/PreProcessingStepsConfiguration.xml";

        self::$preProcessingStepsConfiguration = new Configuration\ConfigurationHandlers\PreProcessingStepsConfigurationHandler($configFileName);

        return self::$preProcessingStepsConfiguration;
    }

    /**
     * Static access to the Event distribution config handler
     *
     * @return Configuration\ConfigurationHandlers\EventDistributionConfigurationHandler
     */
    public static function EventDistributionConfiguration()
    {
        if(isset(self::$eventDistributionConfiguration))
            return self::$eventDistributionConfiguration;

        $requestKey = self::$requestKey;
        $configFileName = (self::$requestKey != 'swiftriver')
    		? dirname(__FILE__)."/Configuration/ConfigurationFiles/" . $requestKey . "_EventDistributionConfiguration.xml"
    		: dirname(__FILE__)."/Configuration/ConfigurationFiles/EventDistributionConfiguration.xml";

        self::$eventDistributionConfiguration = new Configuration\ConfigurationHandlers\EventDistributionConfigurationHandler($configFileName);

        return self::$eventDistributionConfiguration;
    }

    /**
     * Static access to the dynamic module config handler
     *
     * @return Configuration\ConfigurationHandlers\DynamicModuleConfigurationHandler
     */
    public static function DynamicModuleConfiguration()
    {
        if(isset(self::$dynamicModuleConfiguration))
            return self::$dynamicModuleConfiguration;

        $requestKey = self::$requestKey;
        $configFileName = ($requestKey != 'swiftriver')
    		? dirname(__FILE__)."/Configuration/ConfigurationFiles/" .$requestKey . "_DynamicModuleConfiguration.xml"
    		: dirname(__FILE__)."/Configuration/ConfigurationFiles/DynamicModuleConfiguration.xml";

        self::$dynamicModuleConfiguration = new Configuration\ConfigurationHandlers\DynamicModuleConfigurationHandler($configFileName);

        return self::$dynamicModuleConfiguration;
    }
}

//include the Loging Framework
include_once("Log.php");

//Include the config framework
include_once(dirname(__FILE__)."/Configuration/ConfigurationHandlers/BaseConfigurationHandler.php");
$dirItterator = new \RecursiveDirectoryIterator(dirname(__FILE__)."/Configuration/ConfigurationHandlers/");
$iterator = new \RecursiveIteratorIterator($dirItterator, \RecursiveIteratorIterator::SELF_FIRST);
foreach($iterator as $file) {
    if($file->isFile()) {
        $filePath = $file->getPathname();
        if(strpos($filePath, ".php")) {
            include_once($filePath);
        }
    }
}


//Include some specific files
include_once(dirname(__FILE__)."/Workflows/WorkflowBase.php");
include_once(dirname(__FILE__)."/Workflows/ContentServices/ContentServicesBase.php");
include_once(dirname(__FILE__)."/Workflows/EventHandlers/EventHandlersBase.php");
include_once(dirname(__FILE__)."/Workflows/ChannelServices/ChannelServicesBase.php");
include_once(dirname(__FILE__)."/Workflows/SourceServices/SourceServicesBase.php");
include_once(dirname(__FILE__)."/Workflows/PreProcessingSteps/PreProcessingStepsBase.php");
include_once(dirname(__FILE__)."/Workflows/Analytics/AnalyticsWorkflowBase.php");
include_once(dirname(__FILE__)."/Workflows/ApiKeys/ApiKeysWorkflowBase.php");
include_once(dirname(__FILE__)."/Workflows/TwitterStreamingServices/TwitterStreamingServicesBase.php");
include_once(Setup::ModulesDirectory()."/SiSPS/Parsers/IParser.php");
include_once(Setup::ModulesDirectory()."/SiSPS/PushParsers/IPushParser.php");

//include everything else
$directories = array(
    dirname(__FILE__)."/Analytics/",
    dirname(__FILE__)."/ObjectModel/",
    dirname(__FILE__)."/DAL/",
    dirname(__FILE__)."/StateTransition/",
    dirname(__FILE__)."/PreProcessing/",
    dirname(__FILE__)."/Workflows/",
    dirname(__FILE__)."/EventDistribution/",
    Setup::ModulesDirectory()."/SiSW/",
    Setup::ModulesDirectory()."/SiSPS/",
);
foreach($directories as $dir) {
    $dirItterator = new \RecursiveDirectoryIterator($dir);
    $iterator = new \RecursiveIteratorIterator($dirItterator, \RecursiveIteratorIterator::SELF_FIRST);
    foreach($iterator as $file) {
        if($file->isFile()) {
            $filePath = $file->getPathname();
            if(strpos($filePath, ".php")) {
                include_once($filePath);
            }
        }
    }
}
?>
