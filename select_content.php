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

/**
 * Generation form page
 *
 * @package    aiplacement_contentgenerator
 * @author     Jan Rieger, ISy, TH Lübeck
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use aiplacement_contentgenerator\helper;

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/classes/forms/select_content.php');
require_once($CFG->libdir.'/navigationlib.php');

$courseid = optional_param('courseid', 1, PARAM_INT);
$url = null;
if ($courseid !== 1) {
  $url = new moodle_url($CFG->wwwroot . "/ai/placement/contentgenerator/select_content.php", ['id' => $courseid]);
} else {
  $url = new moodle_url($CFG->wwwroot . "/ai/placement/contentgenerator/select_content.php");
}

// Then get the course record from this value, check that the user has permission to do this, and add the relevant crumbs to the nav trail
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);

require_login($course);
if (!has_capability('moodle/course:manageactivities', context_course::instance($course->id))) {
  throw new \moodle_exception("capability_error", "aiplacement_contentgenerator", "", get_string('error_capability', 'aiplacement_contentgenerator'));
}

$PAGE->navbar->add($course->shortname, new moodle_url('/course/view.php', ['id' => $course->id]));
if ($courseid !== 1) {
  $PAGE->navbar->add(get_string("pluginname", "aiplacement_contentgenerator", $url));
} else {
  $PAGE->navbar->add(
    get_string("pluginname", "aiplacement_contentgenerator"), 
    new moodle_url('/ai/placement/contentgenerator/select_content.php', ['id' => $course->id])
  );
  $PAGE->navbar->add(get_string("generatecontent", "aiplacement_contentgenerator", $url));
}

// Set up page
$pagetitle = get_string('pluginname', 'aiplacement_contentgenerator');
$PAGE->set_course($course);
$PAGE->set_url($url);
$PAGE->set_title($pagetitle);
$PAGE->set_pagelayout('standard');
$PAGE->requires->js_call_amd('aiplacement_contentgenerator/pdfprocessor', 'init');

$helper = new helper();

$mform = new generate_form(null, 
                           ['courseid' => $courseid],
                           'post',
                           '',
                           ['id' => 'generatepdfform']);
$fromform = $mform->get_data();

if ($mform->is_cancelled()) {

  redirect($CFG->wwwroot . "/course/view.php?id=" . $course->id);
  
} 
else if ($fromform) {
  // The form was submitted

  $PAGE->set_heading(get_string('generatecontent', 'aiplacement_contentgenerator'));
  echo $OUTPUT->header();
  // generate sourcetext from course mods if checked in form
  $mods = [];
  foreach ($fromform as $key => $value) {
    if (preg_match('/^mod_\w+_\d+$/', $key) && $value) {
        // activated checkbox
        $mod = explode('_', $key);
        $mods[$mod[0].'_'.$mod[1]][] = explode('_', $key)[2];
    }
  }
  // Todo: implement processing of selected mods to generate source text

  $pdfimages = [];
  if (!empty($fromform->pdfimages)) {
        $pdfimages = json_decode($fromform->pdfimages, true);
        $additionalinstructions = $fromform->additional_instructions;

        // instanciate ad hoc task to process the pdf images in background
        $generatecontenttask = \aiplacement_contentgenerator\task\generate_content::instance(
            $pdfimages,
            $additionalinstructions,
            $course->id
        );
        $generatecontenttask->set_userid($USER->id);
        
        \core\task\manager::queue_adhoc_task($generatecontenttask);

        echo $OUTPUT->notification(get_string('generation_started', 'aiplacement_contentgenerator'), 'notifysuccess');
        echo $OUTPUT->continue_button(new moodle_url('/course/view.php', ['id' => $course->id]));
  }
  
} 
else {
  
  // If the course id isn't set and data wasn't actually passed, redirect to course. Somebody went to this page directly, I guess
  if ($courseid === 1 && (!$fromform && !file_get_contents('php://input'))) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
  }
  if (!has_capability('qbank/kia_generator:generatequestions', context_course::instance($course->id))) {
    throw new \moodle_exception("capability_error", "aiplacement_contentgenerator", "", get_string('error_capability', 'aiplacement_contentgenerator'));
  }

  $PAGE->set_heading($pagetitle);
  echo $OUTPUT->header();
  $mform->set_data(['courseid' => $courseid]);
  $mform->display();

}

echo $OUTPUT->footer();
