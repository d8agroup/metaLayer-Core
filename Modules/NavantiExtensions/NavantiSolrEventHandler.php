<?php
namespace Swiftriver\EventHandlers;
use Swiftriver\Core\DAL\Repositories;

class NavantiSolrEventHandler implements \Swiftriver\Core\EventDistribution\IEventHandler 
{
    /**
     * This method should return the name of the event handler
     * that you implement. This name should be unique across all
     * event handlers and should be no more that 50 chars long
     *
     * @return string
     */
    public function Name() 
    {
        return "NavantiSolrEventHandler";
    }

    /**
     * This method should return a description describing what
     * exactly it is that your Event Handler does
     *
     * @return string
     */
    public function Description() 
    {
        return "Pushes the content to Solr.";
    }

    /**
     * This method returns an array of the required paramters that
     * are nessesary to configure this event handler.
     *
     * @return \Swiftriver\Core\ObjectModel\ConfigurationElement[]
     */
    public function ReturnRequiredParameters()
    {
        return array
        (
            new \Swiftriver\Core\ObjectModel\ConfigurationElement
            (
                "Solr Url",
                "string",
                "The url of the solr instance".
                "The url of the solr instance"
            ),
        );
    }

    /**
     * This method should return the names of the events
     * that your EventHandler wishes to subscribe to.
     *
     * @return string[]
     */
    public function ReturnEventNamesToHandle() 
    {
        return array
        (
            \Swiftriver\Core\EventDistribution\EventEnumeration::$ContentPostProcessing,
        );
    }

    /**
     * Given a GenericEvent object, this method should do
     * something amazing with the data contained in the
     * event arguments.
     *
     * @param GenericEvent $event
     * @param \Swiftriver\Core\Configuration\ConfigurationHandlers\CoreConfigurationHandler $configuration
     * @param \Log $logger
     */
    public function HandleEvent($event, $configuration, $logger) 
    {
        $logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [Method invoked]", \PEAR_LOG_DEBUG);

        //Get the $event->arguments as a content item
        $content = $event->arguments;

        //get the module configuraiton
        $config = \Swiftriver\Core\Setup::DynamicModuleConfiguration()->Configuration;

        if(!key_exists($this->Name(), $config)) 
        {
            $logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [The NavantiSolr Event Handler was called but no configuration exists for this module]", \PEAR_LOG_ERR);
            $logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [Method finished]", \PEAR_LOG_DEBUG);
            return;
        }

        $config = $config[$this->Name()];

        foreach($this->ReturnRequiredParameters() as $requiredParam) 
        {
            if(!key_exists($requiredParam->name, $config)) 
            {            	
                $logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [The NavantiSolr Event Handler was called but all the required configuration properties could not be loaded]", \PEAR_LOG_ERR);
                $logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [Method finished]", \PEAR_LOG_DEBUG);
                return;
            }
        }

        //extract the Url for Solr
        $uri = (string) $config["Solr Url"]->value;
        
        $postProcessingEventName = \Swiftriver\Core\EventDistribution\EventEnumeration::$ContentPostProcessing;
        
        switch($event->name)
        {
        	case $postProcessingEventName:
        		
        		$contentArray = array();
        		
        		foreach($content as $i)
        		{
        			$c = array();
        			$c['id'] = $i->id;
        			$c['language'] = $i->text[0]->languageCode;
        			$c['title'] = $i->text[0]->title;
        			$c['text'] = "";
        			
        			$postData = array
					(
						"key" => $c['text']
					);
					
					$service = new \Swiftriver\Core\Modules\SiSW\ServiceWrapper('http://50.57.105.108/getsentiment');
					
					$json = $service->MakePOSTRequest($postData, 10000);
					
					$object = json_decode($json);
					
        			if ($object->status == "success")
        				$c['sentiment'] == $object->sentiment;
        			
        			foreach($i->text[0]->text as $t) 
        				$c['text'] .= $t . " ";
        			$c['link'] = $i->link;
        			$c['date'] = gmdate('Y-m-d\TH:i:s\Z', $i->date);

        			if(count($i->tags) > 0)
        			{
        				$c['tags'] = array();
        				foreach($i->tags as $tag)
        					$c['tags'][] = $tag->text;
        			}
        			
        			if(count($i->gisData) > 0)
        			{
        				$c['rawlocations'] = array();
        				foreach($i->gisData as $g)
        					if($g->longitude != 0 || $g->latitude != 0)
        						$c['rawlocations'][] = $g->latitude . "," . $g->longitude;
        			}
        			
        			$c['sid'] = $i->source->id;
        			$c['sname'] = $i->source->name;
        			$c['slink'] = $i->source->link;
        			
        			if(count($i->source->applicationIds) > 0)
        			{
        				$c['sappids'] = array();
        				foreach($i->source->applicationIds as $key => $id)
        					$c['sappids'][] = $key . "|" . $id;
        			}
        			
        			if(count($i->source->applicationProfileImages) > 0)
        			{
        				$c['sappimages'] = array();
        				foreach($i->source->applicationProfileImages as $key => $img)
        					$c['sappimages'][] = $key . "|" . $img;
        			}
        			
        			/*
        			if(count($i->source->gisData) > 0)
        			{
	        			$c['slocations'] = array();
	        			foreach($i->source->gisData as $g)
	        				$c['slocations'][] = $g->longitude . "," . $g->latitude;
                	}
                	*/
        			
        			$c['cid'] = $i->source->parent;
        			$c['ctype'] = $i->source->type;
        			$c['csubtype'] = $i->source->subType;
        				
					$newc = array();
					foreach($c as $k => $v)
						if($v != null)
							$newc[$k] = $v;
        			
        			$contentArray[] = $newc;
        		}
        		
        		$json = json_encode($contentArray);
        		
        		$uri = $uri . "/update/json";
        		
        		$logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [URI: $uri]", \PEAR_LOG_DEBUG);
        		
        		$logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [JSON: $json]", \PEAR_LOG_DEBUG);
        		
        		$serviceWrapper = new \Swiftriver\Core\Modules\SiSW\ServiceWrapper($uri);
        		
        		$logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [START: Pushing content item to Solr]", \PEAR_LOG_DEBUG);
        		
        		$return = $serviceWrapper->MakeJSONPOSTRequest($json, 5000);
        		
        		if ($return == null || $return == 0)
        		{
        			foreach($content as $c)
        				$c->state = "FAILEDTOSENDTOSOLR";
        			
        			$repository = new Repositories\ContentRepository();
        			$repository->SaveContent($content);
        		}
        		
        		$logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [SERVICE RETURN: $return]", \PEAR_LOG_DEBUG);
        		
        		$logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [END: Pushing content item to Solr]", \PEAR_LOG_DEBUG);
        		
        		$logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [START: Sending Commit message to Solr]", \PEAR_LOG_DEBUG);
        		
        		$serviceWrapper = new \Swiftriver\Core\Modules\SiSW\ServiceWrapper($uri . "?commit=true");
        		
        		$serviceWrapper->MakeGETRequest();
        		
        		$logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [END: Sending Commit message to Solr]", \PEAR_LOG_DEBUG);
        		
        		break;
        	default:
        		$logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [NO MATCHING EVENT NAME]", \PEAR_LOG_ERR);
        		$logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [Method finished]", \PEAR_LOG_DEBUG);
        		return;
        		
        }

        $logger->log("Swiftriver::EventHandlers::NavantiSolrEventHandler::HandleEvent [Method finished]", \PEAR_LOG_DEBUG);
    }
}
?>
