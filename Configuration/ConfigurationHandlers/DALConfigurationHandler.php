<?php
namespace Swiftriver\Core\Configuration\ConfigurationHandlers;
/**
 * Configuration access to the switchable IDataContext type
 * @author mg[at]swiftly[dot]org
 */
class DALConfigurationHandler extends BaseConfigurationHandler
{
	private $ConfigurationFilePath;
	
    /**
     * The PHP5.3 namespace quialified class name of the implemetor
     * of the Swiftriver\DAL\DataContectInterfaces\IDataContext
     * @var Type
     */
    public $DataContextType;

    /**
     * The directory in which the $this->DataContectType class is location
     * relative to the Modules Directory
     * @var string
     */
    public $DataContextDirectory;

    /**
     * Constructor for the DALConfigurationHandler
     * @param string $configurationFilePath
     */
    public function __construct($configurationFilePath) 
    {
    	$this->ConfigurationFilePath = $configurationFilePath;
    	
        //Use the base class to read in the configuration
        $xml = parent::SaveOpenConfigurationFile($configurationFilePath, "properties");

        //loop through the configuration properties
        foreach($xml->properties->property as $property) 
        {
            //Switch on the property name
            switch((string) $property["name"])
            {
                case "DataContextType" :
                    $this->DataContextType = (string) $property["value"];
                    break;
                case "DataContextPath" :
                    $this->DataContextDirectory = (string) $property["value"];
                    break;
            }
        }
    }
    
    public function Save()
    {
        $root = new \SimpleXMLElement("<configuration></configuration>");

        $collection = $root->addChild("properties");

        $dataContextType = $collection->addChild("property");
        $dataContextType->addAttribute("name", "DataContextType");
        $dataContextType->addAttribute("displayName", "Enter the fully qualifed type of the API key data content - rememebr the namespace starting with '\'");
        $dataContextType->addAttribute("type", "string");
        $dataContextType->addAttribute("value", $this->DataContextType);

		$dataContextDirectory = $collection->addChild('property');
		$dataContextDirectory->addAttribute('name', 'DataContextPath');
		$dataContextDirectory->addAttribute('displayName', 'Enter the fully path relative to the modules directory where the APIKey Data Content Files are contained');
		$dataContextDirectory->addAttribute('type', "string");
		$dataContextDirectory->addAttribute("value", $this->DataContextDirectory);

		$root->asXML($this->ConfigurationFilePath);
    }
}
?>
