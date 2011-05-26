<?php
namespace Swiftriver\Core\Modules\SiSPS\PushParsers;
class QuiverPushParser implements IPushParser
{
    /**
     * Implementation of IPushParser::PushAndParse
     * @param $raw_content
     * @param $post_content
     * @param $get_content
     * @return \Swiftriver\Core\ObjectModel\Content[] contentItems
     */
    public function PushAndParse($raw_content = null, $post_content = null, $get_content = null, $file_content = null)
    {
        $logger = \Swiftriver\Core\Setup::GetLogger();

        $logger->log("Core::Modules::SiSPS::PushParsers::QuiverParser::PushAndParse [Method invoked]", \PEAR_LOG_DEBUG);
        $logger->log("Core::Modules::SiSPS::PushParsers::QuiverParser::PushAndParse [START: Extracting required parameters]", \PEAR_LOG_DEBUG);

        $settings = $this->GetSettings();

        $source_name = $this->ReturnType();
        $source = \Swiftriver\Core\ObjectModel\ObjectFactories\SourceFactory::CreateSourceFromIdentifier($source_name, $settings["trusted"]);
        $source->parent = $this->ReturnType();
        $source->name = $source_name;
        $source->link = $get_content["u"];
        $source->type = $this->ReturnType();
        $source->subType = $this->ReturnType();

        //Create a new Content item
        $item = \Swiftriver\Core\ObjectModel\ObjectFactories\ContentFactory::CreateContent($source);

        //Fill the Content Item
        $item->text[] = new \Swiftriver\Core\ObjectModel\LanguageSpecificText(
                null, //here we set null as we dont know the language yet
                $get_content["s"],
                array($get_content["s"]));
        $item->link = $get_content["u"];
        $item->date = time();

        //Add the item to the Content array
        $contentItems[] = $item;


        //return the content array
        return $contentItems;
    }

    public function GetSettings() {
        return array("trusted" => true,
                     "file_upload" => false);
    }

    private function get_quiver_link() {
        $pageURL = 'http';

        if(isset($_SERVER["HTTPS"])) {
            if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
        }

        $pageURL .= "://";

        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
        }

        $pos = strrpos($pageURL, "/web/");

        if ($pos === false) {
            return rtrim($pageURL, "/")."/api/plugins/quiver/quiver.php";
        }

        $pageURL = rtrim(substr($pageURL, 0, $pos), "/")."/core/api/plugins/quiver/quiver.php";

        return $pageURL;
    }

    /**
     * This method returns a string describing the implementation details
     * of this parser
     *
     * @return string - implementation details
     */
    public function GetDescription() {
        $description = "<a href=\"javascript:var%20d=document,w=window,e=w.getSelection,k=d.getSelection,x=d.selection,s=(e?e():(k)?k():(x?x.createRange().text:0)),f='".$this->get_quiver_link()."?origin=Quiver',l=d.location,e=encodeURIComponent,u=f+'&u='+e(l.href)+'&t='+e(d.title)+'&s='+e(s)+'&v=4';a=function(){if(!w.open(u,'t','toolbar=0,resizable=1,scrollbars=1,status=1,width=720,height=570'))l.href=u;};if%20(/Firefox/.test(navigator.userAgent))%20setTimeout(a,%200);%20else%20a();void(0)\">Quiver</a>";

        return "Drag and drop this link to your bookmarks toolbar: ".$description;
    }

    /**
     * This method return an array of fields needed to implement the push mechanism that
     * may be rendered by the UI framework (such as Sweeper / SwiftMeme and others).
     *
     * Returns a null if no field is required
     *
     * @return array[] of fields
     */
    public function GetFields() {
        return null;
    }

    /**
     * This method returns a string describing the type of sources
     * it can parse. For example, the FeedsParser returns "Feeds".
     *
     * @return string type of sources parsed
     */
    public function ReturnType()
    {
        return "Quiver";
    }
}
?>