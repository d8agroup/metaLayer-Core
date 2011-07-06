<?php
namespace Swiftriver\PreProcessingSteps;
use Swiftriver\Core\Modules\SiSW;
use Swiftriver\Core\Modules\SiSPS\Parsers;
use Swiftriver\Core\ObjectModel;

class MetaLensGoogleProductsSearchFromTagsPreProcessingStep implements \Swiftriver\Core\PreProcessing\IPreProcessingStep 
{
    /**
     * Takes any tags extracted from the content items and uses these to run a Google Products 
     * Search that adds any product links to the extension array of the content item under the
     * key 'relatedGoogleProducts'
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
    		$googleProducts = array();
    		
    		//Loop through the content tags (this needs to be done one at a time)
    		foreach($item->tags as $tag)
    		{
    			//Get the tag text
    			$tagText = $tag->text;
    			
    			//Construct the wikipedia search url
    			$googleProductsUrl = 'https://www.googleapis.com/shopping/search/v1/public/products?key=AIzaSyAeJ3W-LP9x1owHnJifO_nHN-2gwNdqrP8&country=US&q=' . $tagText . '&alt=json';

    			try
    			{
    				ini_set( 'user_agent', 'metaLayer' );
    				
    				$json = file_get_contents($googleProductsUrl);
    				
    				//Decode the json returned from the service
    				$objects = json_decode($json);
    				
    				foreach($objects->items as $jsonItem)
    				{
    					$product = $jsonItem->product;
    					
    					$title = $product->title;
    					
    					$link = $product->link;
    					
    					$price = $product->inventories[0]->price;
    					
    					$image = $product->images[0]->link;
    					
    					$googleProducts[] = array
    					(
    						'title' => $title,
    						'price' => $price,
    						'link' => $link,
    						'image' => $image
    					);
    				}
    				
    				
    				/*
    				//If there are objects in the return array
    				if(is_array($objects->items))
    				{
    					//TODO: Here we are limiting the amount of products added
    					$limit = 3;
    					
    					for($x = 0; ($x < $limit && $x < count($objects)); $x++)
    					{
    						$googleProducts[] = $objects->items[$x];
    					}
    				}
    				*/
    			}
    			catch (\Exception $e)
    			{
    				//Log thr error
    				$logger->log('Swiftriver::PreProcessingSteps::MetaLensWikipediaSearchFromTagsPreProcessingStep: ' . $e, \PEAR_LOG_ERR);	
    			}
    		}
    		
    		//If we collected wikipedia links then add them to the content item
    		if(count($googleProducts) > 0)
    			$item->extensions['relatedGoogleProducts'] = $googleProducts;
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
    	return "MetaLensGoogleProductsSearchFromTagsPreProcessingStep";	
    }

    /**
     * The description of this step
     *
     * @return string
     */
    public function Description()
    {
    	return "Takes the tags of the content items and performs a google products search for matching products";
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
