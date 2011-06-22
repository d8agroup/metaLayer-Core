<?php
namespace Swiftriver\Core\Workflows\ContentServices;
/**
 * @author mg[at]swiftly[dot]org
 */
class ProcessContent extends ContentServicesBase
{
    public function RunWorkflow($content, $preProcessContent = true)
    {
        parent::RegisterKey($key);
    	
        $logger = \Swiftriver\Core\Setup::GetLogger();

        $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [Method invoked]", \PEAR_LOG_INFO);

        $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [START: Running core processing]", \PEAR_LOG_DEBUG);

        if($preProcessContent)
        {
            try
            {
                $preProcessor = new \Swiftriver\Core\PreProcessing\PreProcessor();
                $content = $preProcessor->PreProcessContent($content);
            }
            catch (\Exception $e)
            {
                //get the exception message
                $message = $e->getMessage();
                $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [An exception was thrown]", \PEAR_LOG_DEBUG);
                $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [$message]", \PEAR_LOG_ERR);
                $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [Method finished]", \PEAR_LOG_INFO);
                return parent::FormatErrorMessage("An exception was thrown: $message");
            }
        }

        $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [END: Running core processing]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [START: Save content to the data store]", \PEAR_LOG_DEBUG);

        try
        {
            $contentRepository = new \Swiftriver\Core\DAL\Repositories\ContentRepository();
            $contentRepository->SaveContent($content);

            // Raise the event handler that handles the post processing of content

            $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [Raise the Ushahidi Push Event Handler]", \PEAR_LOG_DEBUG);

            $event = new \Swiftriver\Core\EventDistribution\GenericEvent(
                \Swiftriver\Core\EventDistribution\EventEnumeration::$ContentPostProcessing,
                $content);

            $eventDistributor = new \Swiftriver\Core\EventDistribution\EventDistributor();

            $eventDistributor->RaiseAndDistributeEvent($event);

            $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [End Ushahidi Push event]", \PEAR_LOG_DEBUG);

        }
        catch (\Exception $e)
        {
            //get the exception message
            $message = $e->getMessage();
            $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [An exception was thrown]", \PEAR_LOG_DEBUG);
            $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [$message]", \PEAR_LOG_ERR);
            $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [Method finished]", \PEAR_LOG_INFO);
            return parent::FormatErrorMessage("An exception was thrown: $message");
        }

        $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [END: Save content to the data store]", \PEAR_LOG_DEBUG);

        $logger->log("Core::Workflows::ContentServices::ProcessContent::RunWorkflow [Method finished]", \PEAR_LOG_INFO);

    }
}
