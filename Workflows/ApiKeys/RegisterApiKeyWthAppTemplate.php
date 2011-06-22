<?php
namespace Swiftriver\Core\Workflows\ApiKeys;
/**
 * @author mg[at]swiftly[dot]org
 */
class RegisterApiKeyWithAppTemplate extends ApiKeysWorkflowBase
{
    /**
     * Registeres a new API Key with the core
     * TODO: This function need some work, really there should be a check against a super key at least.
     * 
     * @param string $json
     * @return string $json
     */
    public function RunWorkflow($key, $appTemplate)
    {
    	//This function registers the apiKey for us so nothing to do here
        parent::RegisterKey($key, $appTemplate);
    	
        //Setup the logger
        $logger = \Swiftriver\Core\Setup::GetLogger();

        $logger->log("Core::Workflows::ChannelServices::AddChannel::RunWorkflow [Method invoked]", \PEAR_LOG_INFO);

        $logger->log("Core::Workflows::ChannelServices::AddChannel::RunWorkflow [Method finished]", \PEAR_LOG_INFO);
        
        return parent::FormatMessage("OK");
    }
}