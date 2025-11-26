<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace aiplacement_contentgenerator;

defined('MOODLE_INTERNAL') || die;

use stdClass;

/**
 * Class for providing helper functions
 *
 * @package    aiplacement_contentgenerator
 * @copyright  2025 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {
    
    public function get_mods_with_content($courseid) {
        global $DB, $OUTPUT, $CFG;
        require_once("$CFG->libdir/filelib.php");
        
        $mods = array();

        // mod label
        if ($labels = $DB->get_records('label', array('course' => $courseid))) {
            $icon = $OUTPUT->pix_icon('icon', '', 'mod_label');
            foreach ($labels as $label) {
                $title = \html_writer::div($icon . ' ' . $label->name);

                $mod = new stdClass();
                $mod->name = 'mod_label';
                $mod->id = $label->id;
                $mod->title = $title;
                $mod->fileid = $mod->id;
                $mod->filemimetype = 'text/html';

                $mods[] = $mod;
            }
        }

        // mod page
        if ($pages = $DB->get_records('page', array('course' => $courseid))) {
            $icon = $OUTPUT->pix_icon('icon', '', 'mod_page');
            foreach ($pages as $page) {
                $title = \html_writer::div($icon . ' ' . $page->name);

                $mod = new stdClass();
                $mod->name = 'mod_page';
                $mod->id = $page->id;
                $mod->title = $title;
                $mod->fileid = $mod->id;
                $mod->filemimetype = 'text/html';

                $mods[] = $mod;
            }
        }

        // mod resource
        if ($resources = $DB->get_records('resource', array('course' => $courseid))) {
            $icon = $OUTPUT->pix_icon('icon', '', 'mod_resource');
            foreach ($resources as $resource) {
                // check if resource has a file with allowed mimetype
                if ($this->check_resource_files($resource) === false) {
                    continue;
                }
                $title = \html_writer::div($icon . ' ' . $resource->name);
                $fileid = 0;
                $contextid = 0;
                $filename = '';
                $filemimetype = '';
                $mimetype_description = '';
                if ($files = $this->get_resource_files($resource->id)) {
                    foreach ($files as $file) {
                        if ($file->get_sortorder() == 1) {
                            $fileid = $file->get_id();
                            $contextid = $file->get_contextid();
                            $filename = $file->get_filename();
                            $filemimetype = $file->get_mimetype();
                            $mimetype_description = get_mimetype_description($file);
                            break;
                        }
                    }
                }

                $mod = new stdClass();
                $mod->contextid = $contextid;
                $mod->name = 'mod_resource';
                $mod->id = $resource->id;
                $mod->title = $title;
                $mod->fileid = $fileid;
                $mod->filename = $filename;
                $mod->filemimetype = $filemimetype;
                $mod->mimetype_description = $mimetype_description;

                $mods[] = $mod;
            }
        }

        // mod folder
        if ($folders = $DB->get_records('folder', array('course' => $courseid))) {
            $icon = $OUTPUT->pix_icon('icon', '', 'mod_folder');
            foreach ($folders as $folder) {
                if ($cm = get_coursemodule_from_instance('folder', $folder->id, 0, false, MUST_EXIST)) {
                    $context = \context_module::instance($cm->id);
                    $fs = get_file_storage();
                    $files = $fs->get_area_files(
                        $context->id, 
                        'mod_folder', 
                        'content', 
                        0, 
                        'sortorder', 
                        false);
                    foreach ($files as $file) {
                        if ($this->is_allowed_text_mimetype($file->get_mimetype()) === false) {
                            continue;
                        }
                        $title = \html_writer::div($icon . ' ' . $file->get_filename());
                        $mod = new stdClass();
                        $mod->contextid = $context->id;
                        $mod->name = 'mod_folder';
                        $mod->id = $file->get_id();
                        $mod->title = $title;
                        $mod->fileid = $file->get_id();
                        $mod->filename = $file->get_filename();
                        $mod->filemimetype = $file->get_mimetype();

                        $mods[] = $mod;
                    }
                }
            }
        }

        return $mods;
    }

    public function get_sourcetexts($mods) {
        global $DB;
        $sourcetexts = array();
        if (isset($mods['mod_label'])) {
            foreach ($mods['mod_label'] as $labelid) {
                if ($label = $DB->get_record('label', array('id' => $labelid))) {
                    $sourcetexts[] = $label->intro;
                }
            }
        }
        if (isset($mods['mod_page'])) {
            foreach ($mods['mod_page'] as $pageid) {
                if ($page = $DB->get_record('page', array('id' => $pageid))) {
                    $sourcetexts[] = $page->content;
                }
            }
        }
        if (isset($mods['mod_folder'])) {
            foreach ($mods['mod_folder'] as $fileid) {
                if ($content = $this->get_text_from_file($fileid)) {
                    $sourcetexts[] = $content;
                }
            }
        }
        if (isset($mods['mod_resource'])) {
            foreach ($mods['mod_resource'] as $fileid) {
                if ($content = $this->get_text_from_file($fileid)) {
                    $sourcetexts[] = $content;
                }
                // if ($files = $this->get_resource_files($resourceid)) {
                //     foreach ($files as $file) {
                //         if ($file->get_mimetype() == 'application/pdf') {
                //             continue;
                //         }
                //         if ($sourcetext = $this->get_text_from_file($file->get_id())) {
                //             $sourcetexts[] = $sourcetext;
                //         }
                //     }
                // }
            }
        }
        return $sourcetexts;
    }

    /**
     * Helper method that returns all files of a resource
     * @param object $resourceid: The resource ID to get the files from
     * @return array: An array of file objects or false if no files found
     */
    public function get_resource_files($resourceid) {
        if ($cm = get_coursemodule_from_instance('resource', $resourceid, 0, false, MUST_EXIST)) {
            $context = \context_module::instance($cm->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files(
                $context->id, 
                'mod_resource', 
                'content', 
                0, 
                'sortorder', 
                false);
            return $files;
        }
        return false;
    }

    /**
     * Helper method that checks if a resource contains at least one file with an allowed mimetype
     * @param object $resource: The resource to check
     * @return bool: True if the resource contains at least one file with an allowed mimetype, false otherwise
     */
    public function check_resource_files($resource) {
        if ($files = $this->get_resource_files($resource->id)) {
            foreach ($files as $file) {
                if ($this->is_allowed_text_mimetype($file->get_mimetype())) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Helper method that checks if a mimetype is an allowed text mimetype
     * @param string $mimetype: The mimetype to check
     * @return bool: True if the mimetype is allowed, false otherwise
     */
    public function is_allowed_text_mimetype($mimetype) {
        $allowedmimetypes = [
            'text/plain',
            //'text/html',
            //'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            //'application/vnd.ms-powerpoint',
            //'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            //'application/vnd.oasis.opendocument.text',
            'application/pdf',
            //'application/rtf',
            //'text/markdown'
        ];
        return in_array($mimetype, $allowedmimetypes);
    }

    /**
     * Helper method for getting text from a file
     * @param int $fileid: The file ID to get the text from
     * @return string: The text from the file
     */
    public function get_text_from_file($fileid) {
        global $DB;
        if ($filerecord = $DB->get_record('files', array('id' => $fileid))) {
            
            // get single file
            $fs = get_file_storage();
            $file = $fs->get_file_by_id($filerecord->id);
            if (!$file) {
                return false;
            }
            $mimetype = $file->get_mimetype();
            if ($this->is_allowed_text_mimetype($mimetype) === false) {
                return false;
            }
            if ($mimetype === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
                return $this->get_text_from_word_document($file);
            }
            // We don't process pdfs here
            // if ($mimetype === 'application/pdf') {
            //     return $this->get_text_from_pdf_document($file);
            // }
            if ($mimetype === 'text/plain') {
                return $this->get_text_from_text_file($file);
            }
            // Todo: add markdown and rtf processing here and allow mimetypes above
        }   
        return false;
    }

    /**
     * Helper method for getting text from a text file
     * @param object $file: The file object to get the text from
     * @return string: The text from the text file or false on failure
     */
    public function get_text_from_text_file($file) {
        if ($file->get_mimetype() !== 'text/plain') {
            return false;
        }

        $content = $file->get_content();

        $encoding = mb_detect_encoding($content, [
            'UTF-8', 'ISO-8859-1', 'ISO-8859-15', 'Windows-1252', 'ASCII'
        ], true);

        if ($encoding && $encoding !== 'UTF-8') {
            $content = mb_convert_encoding($content, 'UTF-8', $encoding);
        }

        // Entferne nicht konvertierbare Zeichen
        $content = iconv('UTF-8', 'UTF-8//IGNORE', $content);
        // Entferne nicht druckbare Zeichen (außer Zeilenumbrüche und Tabs)
        $content = preg_replace('/[^\PC\s]/u', '', $content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        return $content;
    }

    /**
     * Helper method for getting text from a Word document
     * @param object $file: The file object to get the text from
     * @return string: The text from the Word document or false on failure
     */
    public function get_text_from_word_document($file) {
        $tempfile = $file->copy_content_to_temp();

        $zip = new \ZipArchive;
        if ($zip->open($tempfile) === true) {
            $xmlContent = $zip->getFromName('word/document.xml');
            $zip->close();

            // XML-Header entfernen
            $xmlContent = preg_replace('/<\?xml.*?\?>/','', $xmlContent);

            // Versuche, die Kodierung zu erkennen und ggf. zu konvertieren
            if (!mb_check_encoding($xmlContent, 'UTF-8')) {
                // Versuche explizit UTF-8 zu erzwingen
                $xmlContent = mb_convert_encoding($xmlContent, 'UTF-8', 'UTF-8, ISO-8859-1, ISO-8859-15, Windows-1252');
            }

            // Word-spezifische Tags durch Zeilenumbrüche ersetzen
            $xmlContent = str_replace(['</w:p>', '</w:br>', '</w:tab>'], ["\n", "\n", "\t"], $xmlContent);

            // Alle anderen XML-Tags entfernen
            $xmlContent = strip_tags($xmlContent);

            // Überflüssige Leerzeichen normalisieren
            $xmlContent = preg_replace('/[ \t]+/', ' ', $xmlContent);
            $xmlContent = preg_replace('/\s*\n\s*/', "\n", $xmlContent);

            $xmlContent = trim($xmlContent);

            if (file_exists($tempfile)) {
                unlink($tempfile);
            }

            // Entferne nicht druckbare Zeichen (außer Zeilenumbrüche und Tabs)
            $xmlContent = preg_replace('/[^\PC\s]/u', '', $xmlContent);

            return $xmlContent;
        }

        if (file_exists($tempfile)) {
            unlink($tempfile);
        }
        return false;

    }


}