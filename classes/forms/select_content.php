<?php
// This file is part of Moodle - http://moodle.org/
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

/**
 * Content select form
 *
 * @package    aiplacement_contentgenerator
 * @copyright  2025 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
//namespace aiplacement_contentgenerator;

defined('MOODLE_INTERNAL') || die();

use aiplacement_contentgenerator\helper;

require_once("$CFG->libdir/formslib.php");

class generate_form extends moodleform {

    public function definition() {
       
        $mform = $this->_form;

        // ToDo add Elements for mods with content
        $courseid = isset($this->_customdata['courseid']) ? $this->_customdata['courseid'] : 0;
        $helper = new helper();
        $mods = $helper->get_mods_with_content($courseid);
        $checkboxes = [];
        foreach ($mods as $mod) {
            // PDF-Datei-URL ermitteln
            $fileurl = \moodle_url::make_pluginfile_url(
                $mod->contextid,
                $mod->name,
                'content',
                0,
                '/',
                $mod->filename
            );

            $attrs = [
                'data-fileid' => $mod->fileid,
                'data-mimetype' => $mod->filemimetype,
            ];
            // if file is pdf
            if ($mod->filemimetype == 'application/pdf') {
                $attrs['data-url'] = $fileurl->out(false);
            }

            $checkboxes[] = $mform->createElement('checkbox', $mod->name.'_fileid_'.$mod->fileid, '', $mod->title, $attrs);
        }
        $mform->addGroup($checkboxes, 'modgroup', get_string('modselection', 'aiplacement_contentgenerator'), '<br>', false);

        $mform->addElement('textarea', 'additional_instructions', get_string('additional_instructions', 'aiplacement_contentgenerator'),'wrap="virtual" rows="20" cols="50"');
        $mform->setType('additional_instructions', PARAM_TEXT);
        $mform->setDefault('additional_instructions', get_string('additional_instructions_default', 'aiplacement_contentgenerator'));

        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('hidden', 'pdfimages');
        $mform->setType('pdfimages', PARAM_RAW);

        $this->add_action_buttons(true, get_string('generatecontent', 'aiplacement_contentgenerator'));
    }

    public function validation($data, $files) {
        $errors = [];
        // Check if at least one checkbox is checked
        $checkboxchecked = false;
        foreach ($data as $key => $value) {
            if (strpos($key, 'mod_') === 0 && $value) {
                $checkboxchecked = true;
                break;
            }
        }
        if (!$checkboxchecked) {
            $errors['modgroup'] = get_string('error_nocontentselected', 'aiplacement_contentgenerator');
        }

        return $errors;
    }

}