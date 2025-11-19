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

namespace aiplacement_contentgenerator\task;

/**
 * Class generate_content
 *
 * @package    aiplacement_contentgenerator
 * @copyright  2025 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class generate_content extends \core\task\adhoc_task {
    
    public static function instance(
        mixed $pdfimages,
        mixed $sourcetexts,
        string $additionalinstructions,
        int $courseid
    ): self {
        $task = new self();
        $task->set_custom_data((object) [
            'pdfimages' => $pdfimages,
            'sourcetexts' => $sourcetexts,
            'additionalinstructions' => $additionalinstructions,
            'courseid' => $courseid,
        ]);

        return $task;
    }
    
    /**
     * Execute the task.
     */
    public function execute() {

        $data = $this->get_custom_data();
        $coursecontent = '';
        $results = [];

        // Process each PDF image
        foreach ($data->pdfimages as $fileid => $images) {
          mtrace('Processing PDF with fileid '.$fileid);
          $i = 0;
          foreach ($images as $image) {
            $i++;
            // $image is a base64 encoded image string that shows one page of the pdf
            $result = \aiplacement_contentgenerator\placement::process_pdf($image);
            mtrace('Page '.$i.' processed.');
            mtrace('Success: '.$result['success']);
            //mtrace('Content: '.$result['generatedcontent']);
            mtrace('Error: '.$result['error']);
            if ($result['success']) {
              $coursecontent .= $result['generatedcontent']."\n\n";
              $results[] = 'Page '.$i.' of PDF with fileid '.$fileid.' processed successfully.';}
            else {
              $results[] = 'Error processing page '.$i.' of PDF with fileid '.$fileid.': '.$result['error'];
            }
          }
        }

        // Add other mod content here
        $i = 0;
        foreach ($data->sourcetexts as $text) {
          $i++;
          mtrace('Adding content from mod '.$i.'.');
          $coursecontent .= $text."\n\n";
          $results[] = 'Added content from mod.';
        }

        // Todo: generate Marp slides from $coursecontent
        // e.g.: Erstelle mir 3 MARP Folien zu dem Thema ...
        // use action: generate_text

        // Todo: generate speaker text for each slide
        // use action: generate_text

        // Todo: generate audio from speaker text
        // use new action: generate_audio with text-to-speech (https://wiki.mylab.th-luebeck.dev/de/myLab_services/ai_platform)
        //$newcontent = \aiplacement_contentgenerator\placement::generate_video($coursecontent, $data->additionalinstructions);
        //$results[] = 'Video generation success: '.$newcontent['success'].' Error: '.$newcontent['error'];

        // Todo: create video from slides and audio
        // use php library ffmpeg-php???

        // Send E-Mail to inform user about completed processing
        $courseid = $data->courseid;
        $recipient = \core_user::get_user($this->get_userid());
        $sender    = \core_user::get_support_user();
        $report = implode("\n", $results);
        $report .= "\n\nAdditional Instructions:\n".$data->additionalinstructions;
        $report .= "\n\nGenerated Course Content:\n".$coursecontent;

        $subject = get_string('mail_content_generated_subject', 'aiplacement_contentgenerator');
        $message = get_string('mail_content_generated_message', 'aiplacement_contentgenerator', 
            array ('courselink' => new \moodle_url('/course/view.php', ['id' => $courseid])));
        $message .= "\n\n".$report;
        $messagehtml = get_string('mail_content_generated_messagehtml', 'aiplacement_contentgenerator', 
            array ('courselink' => new \moodle_url('/course/view.php', ['id' => $courseid])));
        $messagehtml .= "<br><br><pre>".nl2br(htmlspecialchars($report))."</pre>";

        email_to_user($recipient, $sender, $subject, $message, $messagehtml);
        
    }
}
