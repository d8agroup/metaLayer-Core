<?php
namespace Swiftriver\PreProcessingSteps;
use Swiftriver\Core\ObjectModel;

class SiLCCPreProcessingStep implements \Swiftriver\Core\PreProcessing\IPreProcessingStep 
{
    /**
     * Interface method that all PrePorcessing Steps must implement
     * 
     * @param \Swiftriver\Core\ObjectModel\Content[] $contentItems
     * @param \Swiftriver\Core\Configuration\ConfigurationHandlers\CoreConfigurationHandler $configuration
     * @param \Log $logger
     * @return \Swiftriver\Core\ObjectModel\Content[]
     */
    public function Process($contentItems, $configuration, $logger)
    {
    	try
    	{
    		$logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [Method started]", \PEAR_LOG_DEBUG);
    		
    		$logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [START: Collecting Config]", \PEAR_LOG_DEBUG);
    		
            $config = \Swiftriver\Core\Setup::DynamicModuleConfiguration()->Configuration;

            if(!key_exists($this->Name(), $config)) 
            {
                $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [The SiLCC Pre Processing Step was called but no configuration exists for this module]", \PEAR_LOG_ERR);
                $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [Method finished]", \PEAR_LOG_DEBUG);
                return $contentItems;
            }

            $config = $config[$this->Name()];

            foreach($this->ReturnRequiredParameters() as $requiredParam) 
            {
                if(!key_exists($requiredParam->name, $config)) 
                {
                    $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [The SiLCC Pre Processing Step was called but all the required configuration properties could not be loaded]", \PEAR_LOG_ERR);
                    $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [Method finished]", \PEAR_LOG_DEBUG);
                    return $contentItems;
                }
            }

            $apiKey = (string) $config["API Key"]->value;

            $serviceUrl = (string) $config["Service Url"]->value;

            $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [END: Collecting Config]", \PEAR_LOG_DEBUG);

            $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [START: Looping through content]", \PEAR_LOG_DEBUG);
            
            foreach($contentItems as $content)
            {
	            try
	            {
	            	if (count($content->text) == 0)
	            		continue;
	            		
					$text = "";
					$text .= ($content->text[0]->title != null)
						? " " . $content->text[0]->title
						: "";
					foreach($content->text[0]->text as $t)
						$text .= ($t != null) ? " " . $t : "";

					$postData = array
					(
						"key" => $apiKey,
						"text" => $text
					);
					
					$service = new \Swiftriver\Core\Modules\SiSW\ServiceWrapper($serviceUrl);
					
					$json = $service->MakePOSTRequest($postData, 10000);
					
					$logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [JSON: " . $json . "]", \PEAR_LOG_DEBUG);
					
					foreach(json_decode($json) as $tag)
					{
						$t = new ObjectModel\Tag($tag);
						$content->tags[] = $t;		
					}
	            }
	            catch(\Exception $e)
	            {
		            $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [An exception was thrown]", \PEAR_LOG_ERR);
		            $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [$e]", \PEAR_LOG_ERR);
		            return $contentItems;
	            }
            }
            
            $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [END: Looping through content]", \PEAR_LOG_DEBUG);
    	}
    	catch (\Exception $e)
    	{
            $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [An exception was thrown]", \PEAR_LOG_ERR);
            $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [$e]", \PEAR_LOG_ERR);
            $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [Method finished]", \PEAR_LOG_DEBUG);
            return $contentItems;
        }
        
        $logger->log("Swiftriver::PreProcessingSteps::SiLCCPreProcessingStep::Process [Method finished]", \PEAR_LOG_DEBUG);
        
        return $contentItems;
    }

    /**
     * The short name for this pre processing step, should be no longer
     * than 50 chars
     *
     * @return string
     */
    public function Name()
    {
    	return "SiLCC Pre Processing Step";
    }

    /**
     * The description of this step
     *
     * @return string
     */
    public function Description()
    {
    	return "V2 of the SiLCC Pre Processing Step";
    }

    /**
     * This method returns an array of the required paramters that
     * are nessesary to run this step.
     *
     * @return \Swiftriver\Core\ObjectModel\ConfigurationElement[]
     */
    public function ReturnRequiredParameters()
    {
    	return array
    	(
            new \Swiftriver\Core\ObjectModel\ConfigurationElement(
                    "Service Url",
                    "string",
                    "The Url of the cloud or locally hosted instsnce of the SiLCC service"
            ),
            new \Swiftriver\Core\ObjectModel\ConfigurationElement(
                    "API Key",
                    "string",
                    "The api key you will need to communicate with the SiLCC service"
            ),
    	);
    }
}
?>
