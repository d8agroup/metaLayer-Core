<?php
namespace Swiftriver\EventHandlers;
/**
 * Interface for all Event Handlers
 * @author mg[at]swiftly[dot]org
 */
class TagClusteringEventHandler implements \Swiftriver\Core\EventDistribution\IEventHandler
{
    /**
     * This method should return the name of the event handler
     * that you implement. This name should be unique across all
     * event handlers and should be no more that 50 chars long
     *
     * @return string
     */
    public function Name() { return "Tag Clustering Reactor Turbine"; }

    /**
     * This method should return a description describing what
     * exactly it is that your Event Handler does
     *
     * @return string
     */
    public function Description() { return "When you activate this turbine, tag based clustering scores will be shows."; }

    /**
     * This method returns an array of the required paramters that
     * are nessesary to configure this event handler.
     *
     * @return \Swiftriver\Core\ObjectModel\ConfigurationElement[]
     */
    public function ReturnRequiredParameters() { return array(); }

    /**
     * This method should return the names of the events
     * that your EventHandler wishes to subscribe to. All
     * the strings returned should be accessed throught the
     * \Swiftriver\Core\EventDistribution\EventEnumeration
     * static enumerator by calling EventEnumeration::[event]
     *
     * @return string[]
     */
    public function ReturnEventNamesToHandle()
    {
        return array ( \Swiftriver\Core\EventDistribution\EventEnumeration::$BeforeContentSentToClient );
    }

    /**
     * Given a GenericEvent object, this method should do
     * something amazing with the data contained in the
     * event arguments.
     *
     * @param GenericEvent $event
     * @param \Swiftriver\Core\Configuration\ConfigurationHandlers\CoreConfigurationHandler $configuration
     * @param \Log $logger
     * @return GenericEvent $event
     */
    public function HandleEvent($event, $configuration, $logger)
    {
        $contentItems = $event->arguments;

        $contentIds = array();

        foreach($contentItems as $item)
            $contentIds[] = $item->id;

        $getSourceNamesSql = "select distinct type from sc_sources";

        $repository = new \Swiftriver\Core\DAL\Repositories\GenericQueryRepository();

        $getSourceNamesResults = $repository->RunGenericQuery($getSourceNamesSql);

        if($getSourceNamesResults["errors"] != null)
        {
            //TODO: do something here
        }

        $results = array();

        foreach($getSourceNamesResults["results"] as $row)
        {
            $sourceSpecificSql = $this->ScoreBySourceTypeSql($row["type"], $contentIds);

            $sourceSpecificResults = $repository->RunGenericQuery($sourceSpecificSql);

            if($sourceSpecificResults["errors"] != null)
            {
                //TODO: do something here
            }

            $results[$row["type"]] = $sourceSpecificResults["results"];
        }

        for($i = 0; $i < \count($contentItems); $i++)
        {
            $tagClusteringInfo = array();

            foreach($results as $type => $dbresults)
            {
                foreach($dbresults as $row)
                {
                    if($row["contentId"] != $contentItems[$i]->id)
                        continue;

                    $tagClusteringInfo[$type] = $row["score"];
                }
            }

            $contentItems[$i]->extensions["tagClusteringScores"] = $tagClusteringInfo;
        }

        $event->arguments = $contentItems;

        return $event;
    }

    private function ScoreBySourceTypeSql($sourceType, $contentIds)
    {
        $idString = $this->IdsToString($contentIds);

        return
            "select
                c.id as 'contentId',
                a.sourceType,
                SUM(a.tagCount) /
                    (
                        select
                            count(*)
                        from
                            sc_content_tags ct join sc_content c on ct.contentId = c.id
                                    join sc_sources s on c.sourceId = s.id
                        where
                            s.type = '$sourceType'
                    ) as 'score'
            from
                (
                    select
                        s.type 'sourceType',
                        t.id as 'tagId',
                        t.text as 'tagText',
                        count(t.id) as 'tagCount'
                    from
                        sc_tags t
                            join sc_content_tags ct on t.id = ct.tagId
                                join sc_content c on c.id = ct.contentId
                                    join sc_sources s on s.id = c.sourceId
                    where
                        s.type = '$sourceType'
                    group by
                        s.type,
                        t.id
                ) a
                join sc_content_tags ct on a.tagId = ct.tagId
                    join sc_content c on ct.contentId = c.id
            where
                a.tagCount > 1
                and
                c.id in $idString
            group by
                c.id, a.sourceType";
    }

    private function IdsToString($ids)
    {
        $string = "(";
        foreach($ids as $id)
            $string .= "'$id',";
        return \rtrim($string, ',') . ")";
    }
}
?>
