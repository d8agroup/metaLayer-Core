<?php
namespace Swiftriver\PreProcessingSteps;
use Swiftriver\Core\Modules\SiSW;
use Swiftriver\Core\Modules\SiSPS\Parsers;
use Swiftriver\Core\ObjectModel;

class MetaLensTagFilteringPreProcessingStep implements \Swiftriver\Core\PreProcessing\IPreProcessingStep 
{
    /**
     * Takes the tags associated with content item and applies some checking to make sure that they are 
     * usable.
     * 
     * @param \Swiftriver\Core\ObjectModel\Content[] $contentItems
     * @param \Swiftriver\Core\Configuration\ConfigurationHandlers\CoreConfigurationHandler $configuration
     * @param \Log $logger
     * @return \Swiftriver\Core\ObjectModel\Content[]
     */
    public function Process($contentItems, $configuration, $logger)
    {
    	//Loop through the content items
    	foreach($contentItems as $item)
    	{
    		if(count($item->tags) == 0)
    			continue;
    			
    		$newTags = array();
    		
    		foreach($item->tags as $tag)
    		{
    			$text = $tag->text;
    			
    			//If the tag contains none word chars
    			if(preg_match('/\W/i', $text))
    				continue;
    			
    			//If the tag lenght is les then three
    			if(strlen($text) < 3)
    				continue;
    				
    			$newTags[] = $tag;
    		}
    		
    		$item->tags = $newTags;
    	}
    	
    	//return the array of content
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
    	return "MetaLensTagFilteringPreProcessingStep";	
    }

    /**
     * The description of this step
     *
     * @return string
     */
    public function Description()
    {
    	return "Reduces the tag set associated with a content item based on some rules";
    }

    /**
     * This method returns an array of the required paramters that
     * are nessesary to run this step.
     *
     * @return \Swiftriver\Core\ObjectModel\ConfigurationElement[]
     */
    public function ReturnRequiredParameters()
    {
    	return array();
    }
}
?>
