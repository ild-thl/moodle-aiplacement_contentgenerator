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
use aiplacement_contentgenerator\helper;
use core_availability\result;

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
/*
      $cmd = '"C:/laragon/bin/ffmpeg/ffmpeg.exe" -y -framerate 1 -i "C:/laragon/moodle405kiadata/temp/aiplacement_slides/images_6938864b5c007/slide_%d.png" -i "C:/laragon/moodle405kiadata/temp/aiplacement_slides/audio_6938864b5c007/audio_%d.mp3" -c:v libx264 -r 30 -pix_fmt yuv420p -c:a aac -shortest "C:/laragon/moodle405kiadata/temp/aiplacement_slides/video_6938864b5c007/video_6938864b5c007.mp4"';
      $output = [];
        $returnvar = 0;
        exec($cmd, $output, $returnvar);
        mtrace('FFMPEG output:');
        foreach ($output as $line) {
            mtrace($line);
        }
        mtrace('FFMPEG return var: '.$returnvar);
        return;
//*/
        $data = $this->get_custom_data();
        $coursecontent = '';
        $results = [];
        $success = true;
        $imagesdir = '';
        $audiodir = '';
        $contentid = 0;
        $context = \context_course::instance($data->courseid);
        $helper = new helper();

        // Process each PDF image (extract content as text)
        // this may take a while...
        mtrace('Start processung PDFs');
        $processedpages = 0;
        $result = $helper->process_pdfimages($data->pdfimages);
        if ($result['success'] === true) {
          $coursecontent .= $result['extractedcontent'];
        } 
        $processedpages = $result['pagesprocessed'];
        $results[] = $result['result'];
        mtrace($result['result']);

        // Add other mod content here
        mtrace('Start adding content from other selected course activities.');
        $j = 0;
        foreach ($data->sourcetexts as $text) {
          $j++;
          $coursecontent .= 'Page '.$processedpages+$j.':\n'.$text."\n\n";
        }
        $results[] = 'Added texts from '.$j.' selected course activities.';
        mtrace('Added texts from '.$j.' selected course activities.');

        // Check if any content was generated
        if (empty(trim($coursecontent))) {
          $results[] = 'No content generated from PDFs or source texts.';
          mtrace('No content generated from PDFs or source texts.');
          $success = false;
        }
        
        // Refine $coursecontent with additional instructions
        if ($success) {
          mtrace('Start refining extracted course content with AI.');
          if (isset($data->additionalinstructions) && 
            !empty(trim($data->additionalinstructions))) {
            $result = $helper->refine_content($coursecontent, $context, $data->additionalinstructions);
          }
          else {
            $result = $helper->refine_content($coursecontent, $context);
          }
          if ($result['success'] === true) {
            $coursecontent = $result['extractedcontent'];
          }
          else {
            $success = false;
          }
          $results[] = $result['result'];
          mtrace($result['result']);
        }
        
        //  Build Marp slides from $coursecontent
        if ($success) {
          mtrace('Start generating Marp slides from course content with AI.');
          $numberofslides = $processedpages+$j;
          $result = $helper->build_marp_slides($coursecontent, $context, $numberofslides);
          if ($result['success'] === true) {
            $marp_slides = $result['marp_slides'];
          }
          else {
            $success = false;
          }
          $results[] = $result['result'];
          mtrace($result['result']);
        }

        // Todo: validate Marp content syntax and refine it again if necessary

        // render Marp slides to Images
        // use local marp installation
        if ($success) {
          mtrace('Start rendering Marp slides to images.');
          $result = $helper->render_images_from_marp_slides($marp_slides);
          if ($result['success'] === true) {
              $imagesdir = $result['imagesdir'];
              $contentid = $result['contentid'];
          }
          else {
              $success = false;
          }
          $results[] = $result['result'];
          mtrace($result['result']);
        }

        // generate speaker text for each Marp slide
        // use action: generate_text
        if ($success) {
          mtrace('Start generating speaker text for each slide with AI.');
          $result = $helper->generate_speaker_text($marp_slides, $context);
          if ($result['success'] === true) {
            $speakertext = $result['speaker_text'];
          }
          else {
            $success = false;
          }
          $results[] = $result['result'];
          mtrace($result['result']);
        }

        // generate audio from speaker text
        if ($success) {
          mtrace('Start generating audio from speaker text with AI.');
          $result = $helper->generate_audio($speakertext, $contentid, $context);
          if ($result['success'] === true) {
            $audiodir = $result['audiodir'];
          }
          else {
            $success = false;
          }
          $results[] = $result['result'];
          mtrace($result['result']);
        }


        // Todo: create video from slide images and audio
        if ($success) {
          mtrace('Start generating video from slide images and audio.');
          $result = $helper->generate_video_from_images_and_audio($imagesdir, $audiodir, $contentid);
          if ($result['success'] === true) {
            $videofilepath = $result['videofilepath'];
          }
          else {
            $success = false;
          }
          $results[] = $result['result'];
          mtrace($result['result']);
        }

        // save generated video in course files area
        if ($success) {
          mtrace('Start saving generated video to course files area.');
          $result = $helper->save_video_to_course_files(
            $videofilepath,
            $data->courseid
          );
          if ($result['success'] === false) {
            $success = false;
          }
          $results[] = $result['result'];
          mtrace($result['result']);
        }

        // delete temp files and folders (audio, images, video, marp)
        mtrace('Start cleaning up temporary files.');
        $result = $helper->cleanup_temporary_files($contentid);
        $results[] = $result['result'];
        mtrace($result['result']);

        // Send E-Mail to inform user about completed processing
        $courseid = $data->courseid;
        $recipient = \core_user::get_user($this->get_userid());
        $sender    = \core_user::get_support_user();
        $report = implode("\n", $results);
        $report .= "\n\nAdditional Instructions:\n".$data->additionalinstructions;
        $report .= "\n\nGenerated content:\n".$speakertext;

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
