<?php
namespace Swiftriver\PreProcessingSteps;
use Swiftriver\Core\Modules\SiSPS\Parsers;
use Swiftriver\Core\ObjectModel;

class MetaLensTwitterSearchFromTagsPreProcessingStep implements \Swiftriver\Core\PreProcessing\IPreProcessingStep 
{
    /**
     * Takes any tags extracted from the content items and uses these to run a Twitter
     * Search that adds any tweets to the extension array of the content item under the
     * key 'relatedTweets'
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
	    	//Array to hold the tags
	    	$allTags = array();
	    	
	    	//Add all th tags from all the content items
    		foreach($item->tags as $tag)
    			$allTags[] = $tag->text;
    			
	    	//Get the twitter search string by OR adding all the tags together
	    	$twitterSearchString = implode(" OR ", $allTags);
	    	
	    	//Create a new channel object to use to call the parser
	    	$channel = new ObjectModel\Channel();
	    	
	    	//set the subtype of the channel to fire off the twitter search
	    	$channel->subType = "Search";
	    	
	    	//Add the twitter search string as a paramter to the channel
	    	$channel->parameters = array
	    	(
	    		"SearchKeyword" => $twitterSearchString
	    	);
	    	
	    	//Instanciate a new Twitter parser
	    	$twitterParser = new Parsers\TwitterParser();
	    	
	    	//Call the GetAndParse Function to get back any tweets
	    	$tweets = $twitterParser->GetAndParse($channel);
	    	
	    	//if there are tweets add them to the extension field of the content item
	    	if(count($tweets) > 0)
	    	{	
			$item->extensions['relatedTweets'] = array();

			foreach($tweets as $t)
			{
				$item->extensions['relatedTweets'][] = array
				(
					'text' => $t->text[0]->text[0],
					'link' => $t->link,
					'image' => $t->source->applicationProfileImages['twitter'],
					'userlink' => $t->source->link,
				);
			}
		}
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
    	return "MetaLensTwitterSearchFromTagsPreProcessingStep";	
    }

    /**
     * The description of this step
     *
     * @return string
     */
    public function Description()
    {
    	return "Takes the tags of the content items and performs a twitter search for tweets matching them";
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
