<?php

class repository_emptyfile extends repository {
    public function get_listing($path = '', $page = '')
    {
        global $CFG;

        $fs = get_file_storage();
        $filerecord = array(
            'filename' => 'emptyfile',
            'component' => 'mod_resource',
            'filearea' => 'content',
            'contextid' => 1,
            'itemid' => 0,
            'filepath' => '/'
        );

        $fs->create_file_from_string($filerecord, "");

        if (empty($list['list'])) {
            $list['list'] = array();
        }

//        $list['list'][] = array(
//            'title'=>"emptyfile",
//            'source'=>$emptyfile,
//            'thumbnail'=>$emptyfile,
//            'thumbnail_height'=>84,
//            'thumbnail_width'=>84
//        );

        return $list;
    }
}