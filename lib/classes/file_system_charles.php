<?php
/**
 * Charles File System.
 *
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();
require_once("$CFG->libdir/filestorage/file_system.php");

//THIS IS A PROOF OF CONCEPT DUE TO MY LIMITED KNOWLEDGE OF MOODLE
//THERE IS A LOT TO IMPROVE/CLEAN UP


/**
 * File system class used for low level access to real files via ftp (aieee! don't do it!).
 *
 * @package   core_files
 * @category  files
 * @copyright 2017 Damyon Wiese
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_file_system_charles extends file_system
{
    private $logfilepath;

    public function get_file_id_in_db($contextid, $component, $filearea, $filepath)
    {
        global $DB;

        $where = "contextid = :contextid
                AND component = :component
                AND filearea = :filearea
                AND itemid = :itemid
                AND filename = :filepath";

        $params['contextid'] = $contextid;
        $params['component'] = $component;
        $params['filearea'] = $filearea;
        $params['itemid'] = 0; //Assuming files always have itemid = 0
        $params['filepath'] = $filepath;

        $filerecords = $DB->get_recordset_select('files', $where, $params);

        $filerow = $filerecords->current();

        $logline = "[" . date("Y-m-d H:i:s") . "] FILE ACCESS : " . $filerow->id . " - " . $filerow->filename . "\n";

        file_put_contents($this->logfilepath, $logline, FILE_APPEND);
    }

    public function get_detail_from_uri()
    {
        if ($_SERVER["SCRIPT_NAME"] === "/pluginfile.php") {
            $data = substr($_SERVER["PATH_INFO"], 1);

            //Keeping $itemid in list because in the path it's $ressourceid and not relevant to
            //purpose of this FS
            list($contextid, $component, $filearea, $itemid, $filepath) = explode("/", $data);
            $this->get_file_id_in_db($contextid, $component, $filearea, $filepath);
        }
    }

    public function setup_instance()
    {
        global $CFG;

        $this->logfilepath = $CFG->dataroot . '/temp/customfs.log';
        $this->get_detail_from_uri();

    }

    protected function get_local_path_from_hash($contenthash, $fetchifnotfound = false)
    {
        $this->setup_instance();
        $this->cachedir = make_request_directory();
        $contenthash = sha1("");
        $localurl = $this->cachedir . DIRECTORY_SEPARATOR . $contenthash;

        return $localurl;
    }

    protected function get_remote_path_from_hash($contenthash)
    {
        $this->cachedir = make_request_directory();
        $contenthash = sha1("");
        $localurl = $this->cachedir . DIRECTORY_SEPARATOR . $contenthash;

        return $localurl;
    }

    public function copy_content_from_storedfile(stored_file $file, $target)
    {
        return true;
    }

    public function remove_file($contenthash)
    {
        return true;
    }

    public function add_file_from_path($pathname, $contenthash = null)
    {
        //I'm pretty sure the point is to generate empty file to let's just
        //call $this->add_file_from_string("");

        return $this->add_file_from_string("");
    }

    public function add_file_from_string($content)
    {
        $contenthash = sha1($content);
        $filesize = strlen($content);

        if ($content === '') {
            $logline = "[" . date("Y-m-d H:i:s") . "] FILE CREATION timecreated : " . time() . "\n";
      //      file_put_contents($this->logfilepath, $logline, FILE_APPEND);
            return [$contenthash, $filesize, false];
        }

        $newfile = false;
        $remotepath = $this->get_remote_path_from_hash($contenthash);
        if (!file_exists($remotepath)) {
            $newfile = true;
            // Store the file remotely first - it's likely to be used again.
            $localpath = $this->get_local_path_from_hash($contenthash, false);
            file_put_contents($localpath, $content);

            copy($localpath, $remotepath);
        }

        return [$contenthash, $filesize, $newfile];
    }
}