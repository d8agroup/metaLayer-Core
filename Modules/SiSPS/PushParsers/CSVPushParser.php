<?php
/**
* @author am[at]swiftly[dot]org
*/
namespace Swiftriver\Core\Modules\SiSPS\PushParsers;
class CSVPushParser implements IPushParser
{
    /**
     * Implementation of IPushParser::PushAndParse
     * @param $raw_content
     * @param $post_content
     * @param $get_content
     * @param $file_content
     * @return \Swiftriver\Core\ObjectModel\Content[] contentItems
     */
    public function PushAndParse($raw_content = null, $post_content = null, $get_content = null, $file_content = null)
    {
        $logger = \Swiftriver\Core\Setup::GetLogger();

        $logger->log("Core::Modules::SiSPS::PushParsers::CSVParser::PushAndParse [Method invoked]", \PEAR_LOG_INFO);
        $logger->log("Core::Modules::SiSPS::PushParsers::CSVParser::PushAndParse [START: Extracting required parameters]", \PEAR_LOG_INFO);

        $contentItems = array();

        $settings = $this->GetSettings();

        $file_name = "file_upload_".$settings["file_upload_id"];

        if(!isset($file_content[$file_name])) {
            $logger->log("Core::Modules::SiSPS::PushParsers::CSVParser::PushAndParse [No file uploaded with file_upload file name]", \PEAR_LOG_ERR);

            return $contentItems;
        }

        $logger->log("Core::Modules::SiSPS::PushParsers::CSVParser::PushAndParse [END: Extracting required parameters]", \PEAR_LOG_INFO);

        $file_handle = fopen($file_content[$file_name]["tmp_name"], "r");

        if($file_handle) {
            while (($data = fgetcsv($file_handle, 0, ",")) !== FALSE) {
                $title = $data[0];
                $text = $data[1];
                $link = $data[2];
                $date = $data[3];

                if(strrpos($date, "-")) {
                    // Convert date to timestamp
                    $date = explode("-", $date);
                    $date = strptime($date[0]."-".$date[1]."-".$date[2], "%Y-%m-%d");
                    $date = mktime(0, 0, 0, $date['tm_month'], $date['tm_day'], $date['tm_year']);
                }

                $source_name = $this->ReturnType();
                $source = \Swiftriver\Core\ObjectModel\ObjectFactories\SourceFactory::CreateSourceFromIdentifier($source_name, $settings["trusted"]);
                $source->parent = $this->ReturnType();
                $source->name = $source_name;
                $source->link = $link;
                $source->type = $this->ReturnType();
                $source->subType = $this->ReturnType();

                //Create a new Content item
                $item = \Swiftriver\Core\ObjectModel\ObjectFactories\ContentFactory::CreateContent($source);

                //Fill the Content Item
                $item->text[] = new \Swiftriver\Core\ObjectModel\LanguageSpecificText(
                        null, //here we set null as we dont know the language yet
                        $title,
                        array($text));
                $item->link = $link;
                $item->date = $date;

                //Add the item to the Content array
                $contentItems[] = $item;
            }
        }
        else {
            $logger->log("Core::Modules::SiSPS::PushParsers::CSVParser::PushAndParse [Method finished]", \PEAR_LOG_INFO);

            return $contentItems;
        }

        fclose($file_handle);


        //return the content array
        return $contentItems;
    }

    /**
     * This method returns a string describing the implementation details
     * of this parser
     *
     * @return string - implementation details
     */
    public function GetDescription() {
        return "This plugin allows you to upload CSV files and store them on SwiftRiver";
    }

    /**
     * This method return an array of fields needed to implement the push mechanism that
     * may be rendered by the UI framework (such as Sweeper / SwiftMeme and others).
     *
     * Returns a null if no field is required
     *
     * @return array[] of fields
     */
    public function GetSettings() {
        return array("trusted" => true,
                     "file_upload" => true,
                     "upload_path" => "api/plugins/contentpush/contentpush.php?origin=".$this->ReturnType(),
                     "file_upload_id" => $this->ReturnType());
    }

    /**
     * This method returns a string describing the type of sources
     * it can parse. For example, the FeedsParser returns "Feeds".
     *
     * @return string type of sources parsed
     */
    public function ReturnType()
    {
        return "CSV";
    }
}
?>