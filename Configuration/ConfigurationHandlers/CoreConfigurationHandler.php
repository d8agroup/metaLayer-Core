<?php
namespace Swiftriver\Core\Configuration\ConfigurationHandlers;

/**
 * The configuration handler for all the core configuration
 * @author mg[at]swiftly[dot]org
 */
class CoreConfigurationHandler extends BaseConfigurationHandler
{
    public $ConfigurationFilePath;

    /**
     * The name of the configuration section
     * @var string
     */
    public $Name;

    /**
     * The base language code
     * @link http://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     * @var ISO639-1_Language_Code
     */
    public $BaseLanguageCode;

    /**
     * The level of mask to ba added to the log filter
     * anythign under this level will not be logged
     * 
     * @var string
     */
    public $LogLevelMask = 'error';
    
    /**
     * Where a proxy server is required, it can be stored in
     * this variable.
     * @var string
     */
    public $ProxyServer;

    /**
     * Where you are using a proxy server and it requires auth
     * then use this variable to hold the user name
     *
     * @var string
     */
    public $ProxyServerUserName;

    /**
     * Where you are using a proxy server and it requires auth
     * then use this variable to hold the Password
     *
     * @var string
     */
    public $ProxyServerPassword;

    /**
     * The constructor for the CoreConfigurationHandler
     * @param string $configurationFilePath
     */
    public function __construct($configurationFilePath) 
    {
        $this->ConfigurationFilePath = $configurationFilePath;

        //use the base calss to open the config file
        $xml = parent::SaveOpenConfigurationFile($configurationFilePath, "properties");

        //extract the name element and store it
        $this->Name = (string) $xml["name"];

        //loop around the properties
        foreach($xml->properties->property as $property) 
        {
            //swiftch on the name of the property
            switch((string) $property["name"])
            {
                case "BaseLanguageCode" :
                    $this->BaseLanguageCode = (string) $property["value"];
                    break;
                case "ProxyServer" :
                    $this->ProxyServer = (string) $property["value"];
                    break;
                case "ProxyServerUserName" :
                    $this->ProxyServerUserName = (string) $property["value"];
                    break;
                case "ProxyServerPassword" :
                    $this->ProxyServerPassword = (string) $property["value"];
                    break;
                case "LogLevelMask" :
                    $this->LogLevelMask = (string) $property['value'];
                    break;
            }
        }
    }

    public function Save()
    {
        $root = new \SimpleXMLElement("<configuration></configuration>");

        $collection = $root->addChild("properties");

        $baseLanguageElement = $collection->addChild("property");
        $baseLanguageElement->addAttribute("name", "BaseLanguageCode");
        $baseLanguageElement->addAttribute("displayName", "Enter the two letter ISO 639-1 language code used as the base reference for all other languages");
        $baseLanguageElement->addAttribute("type", "string");
        $baseLanguageElement->addAttribute("value", $this->BaseLanguageCode);

		$logLevelMaskElement = $collection->addChild('property');
		$logLevelMaskElement->addAttribute('name', 'LogLevelMask');
		$logLevelMaskElement->addAttribute('displayName', 'Set this to error to hide all but error message in the log file');
		$logLevelMaskElement->addAttribute('type', "string");
		$logLevelMaskElement->addAttribute("value", $this->LogLevelMask);
        
        if($this->ProxyServer != "" && $this->ProxyServer != null)
        {
            $proxyServerElement = $collection->addChild("property");
            $proxyServerElement->addAttribute("name", "ProxyServer");
            $proxyServerElement->addAttribute("displayName", "Set the url of a proxi server is required");
            $proxyServerElement->addAttribute("type", "string");
            $proxyServerElement->addAttribute("value", $this->ProxyServer);
        }

        if($this->ProxyServerUserName != "" && $this->ProxyServerUserName != null)
        {
            $proxyUsernameElement = $collection->addChild("property");
            $proxyUsernameElement->addAttribute("name", "ProxyServerUserName");
            $proxyUsernameElement->addAttribute("displayName", "Set the username for the proxi server is required");
            $proxyUsernameElement->addAttribute("type", "string");
            $proxyUsernameElement->addAttribute("value", $this->ProxyServerUserName);
        }

        if($this->ProxyServerPassword != "" && $this->ProxyServerPassword != null)
        {
            $proxyPasswordElement = $collection->addChild("property");
            $proxyPasswordElement->addAttribute("name", "ProxyServerPassword");
            $proxyPasswordElement->addAttribute("displayName", "Set the password for the proxi server is required");
            $proxyPasswordElement->addAttribute("type", "string");
            $proxyPasswordElement->addAttribute("value", $this->ProxyServerPassword);
        }
        
        $root->asXML($this->ConfigurationFilePath);
    }
}
?>
