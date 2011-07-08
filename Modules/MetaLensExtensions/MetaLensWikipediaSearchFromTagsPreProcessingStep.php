<?php
namespace Swiftriver\PreProcessingSteps;
use Swiftriver\Core\Modules\SiSW;

use Swiftriver\Core\Modules\SiSPS\Parsers;
use Swiftriver\Core\ObjectModel;

class MetaLensWikipediaSearchFromTagsPreProcessingStep implements \Swiftriver\Core\PreProcessing\IPreProcessingStep 
{
    /**
     * Takes any tags extracted from the content items and uses these to run a Wikipedia 
     * Search that adds any article links to the extension array of the content item under the
     * key 'relatedWikipediaArticles'
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
    		//Set up the array of wikipedia links
    		$wikipediaLinks = array();
    		
    		//Loop through the content tags (this needs to be done one at a time)
    		foreach($item->tags as $tag)
    		{
    			//Get the tag text
    			$tagText = $tag->text;
    			
    			//Construct the wikipedia search url
    			$wikipediaUrl = 'http://en.wikipedia.org/w/api.php?action=opensearch&search=' . $tagText . '&format=json';

    			try
    			{
    				ini_set( 'user_agent', 'metaLayer' );
    				
    				$json = file_get_contents($wikipediaUrl);
    				
    				//Decode the json returned from the service
    				$objects = json_decode($json);
    				
    				//If there are objects in the return array
    				if(is_array($objects))
    				{
    					//Loop through them
    					foreach($objects as $articles)
    					{
    						if(is_array($articles))
    						{
    							//Loop through them
							for($x = 0; $x < 10 && $x < count($articles); $x++)
    							{
								$articleName = $articles[$x];
								
    								//Clean the string ready to be added to the article url
    								$cleanArticleName = str_replace(' ', '_', $articleName);
    								
    								//Construct the article url
    								$wikipediaLink = 'http://en.wikipedia.org/wiki/' . $cleanArticleName;
    								
    								//Create a new entry in the wikipediaLinks array
    								$wikipediaLinks[] = array
    								(
    									'name' => $articleName,
    									'link' => $wikipediaLink
    								);
    							}
    						}
    					}
    				}
    			}
    			catch (\Exception $e)
    			{
    				//Log thr error
    				$logger->log('Swiftriver::PreProcessingSteps::MetaLensWikipediaSearchFromTagsPreProcessingStep: ' . $e, \PEAR_LOG_ERR);	
    			}
    		}

		shuffle($wikipediaLinks);

                $item->extensions['relatedWikipediaArticles'] = array();
    		
    		//If we collected wikipedia links then add them to the content item
    		if(count($wikipediaLinks) > 0)
		    for($x=0; $x < 10 && $x < count($wikipediaLinks); $x++)
    			$item->extensions['relatedWikipediaArticles'][] = $wikipediaLinks[$x];
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
    	return "MetaLensWikipediaSearchFromTagsPreProcessingStep";	
    }

    /**
     * The description of this step
     *
     * @return string
     */
    public function Description()
    {
    	return "Takes the tags of the content items and performs a wikipedia article search for matching articles";
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
