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
        global $USER, $CFG;
        $data = $this->get_custom_data();
        $coursecontent = '';
        $results = [];
        $success = true;
        $context = \context_course::instance($data->courseid);


        // Process each PDF image (extract content as text)
        // this may take a while...
        foreach ($data->pdfimages as $fileid => $images) {
          mtrace('Processing PDF with fileid '.$fileid);
          $i = 0;
          foreach ($images as $image) {
            $i++;
            // $image is a base64 encoded image string that shows one page of the pdf
            $result = \aiplacement_contentgenerator\placement::process_pdf($image);
            mtrace('Page '.$i.' processed.');
            mtrace('Success: '.$result['success']);
            mtrace('Error: '.$result['error']);
            if ($result['success']) {
              $coursecontent .= 'Page '.$i.':\n'.$result['generatedcontent']."\n\n";
              $results[] = 'Page '.$i.' of PDF with fileid '.$fileid.' processed successfully.';
              mtrace('Page '.$i.' of PDF with fileid '.$fileid.' processed successfully.');
            }
            else {
              $results[] = 'Error processing page '.$i.' of PDF with fileid '.$fileid.': '.$result['error'];
              mtrace('Error processing page '.$i.' of PDF with fileid '.$fileid.': '.$result['error']);
            }
          }
        }

        // Add other mod content here
        $j = 0;
        foreach ($data->sourcetexts as $text) {
          $j++;
          mtrace('Adding content from mod '.$j.'.');
          $coursecontent .= 'Page '.$i+$j.':\n'.$text."\n\n";
          $results[] = 'Added content from mod.';
        }

        if (empty(trim($coursecontent))) {
          $results[] = 'No content generated from PDFs or source texts.';
          mtrace('No content generated from PDFs or source texts.');
          $success = false;
        }


        // Refine $coursecontent with additional instructions
        $prompt = '';
        // if no additional instructions are given remove placeholder text for images 
        $logo_url = $CFG->wwwroot.'/ai/placement/contentgenerator/pix/logo.png';
        $logo_instruction = 'If there are any placeholder texts for images, that should show the TH Luebeck logo, please use the following url to embed the logo image: '.$logo_url;
        $no_footer_instruction = 'Ensure that there are no footer texts or page numbers included in the content.';
        if (!isset($data->additionalinstructions) || 
            empty(trim($data->additionalinstructions))) {
          $prompt = 'Please improve the structure and clarity of the course content. Ensure that the content is well-organized and easy to understand. ';
          $prompt .= $logo_instruction.' '.$no_footer_instruction."\n\nCourse Content:\n".$coursecontent;
        }
        else if (isset($data->additionalinstructions) && 
          !empty(trim($data->additionalinstructions))) {
          $prompt = "Please refine the following course content according to these instructions: ";
          $prompt .= $data->additionalinstructions. " ".$logo_instruction.' '.$no_footer_instruction."\n\nCourse Content:\n".$coursecontent;
        }
        if ($success) {
          $action = new \core_ai\aiactions\generate_text(
              contextid: $context->id,
              userid: $USER->id,
              prompttext: $prompt,
          );
          $manager = \core\di::get(\core_ai\manager::class);
          $response = $manager->process_action($action);
          $results[] = 'Refinement success: '.$response->get_success().' Error: '.$response->get_errormessage();
          if ($response->get_success() && isset($response->get_response_data()['generatedcontent'])) {
              $coursecontent = $response->get_response_data()['generatedcontent'] ?? '';
              //$coursecontent .= $response->get_response_data()['generatedcontent'] ?? ''; // for debugging
              $results[] = 'Course content refined successfully.';
              mtrace('Course content refined successfully.');
          }
          else {
            $success = false;
            $results[] = 'Error refining course content: '.$response->get_errormessage();
            mtrace('Error refining course content: '.$response->get_errormessage());
          }
        }

        
        //  Marp slides from $coursecontent
        if ($success) {
          $numberofslides = $i + $j;
          $marp_example = 
            '---

            marp: true
            style: |
              section.lead {
                border-bottom: 100px solid #e4003a;
                padding-bottom: 110px;
              }
              section:not(.lead) {
                border-bottom: 20px solid #e4003a;
                padding-bottom: 20px;
              }

            ---

            <!--
            class: lead
            -->

            <img src="http://localhost/moodle405kia/ai/placement/contentgenerator/pix/logo.png" alt="TH Lübeck Logo" width="150" style="
              position: absolute;
              top: 30px;
              right: 30px;">

            # Heading of the presentation

            ---

            <!--
            class: follow
            -->

            # Heading of the first slide

            ## Subheading of the first slide

            Text content for the first slide.

            - **First bullet point** example text.
            - **Second bullet point** example text.  
            - **Third bullet point** example text.

            ---

            # Heading of the second slide';

          $prompt = '';

          $prompt .= "You are an expert in creating educational presentations.\n";
          $prompt .= "Please create ".$numberofslides." MARP slides for course content, that will be provided later.\n";
          $prompt .= "Create 1 slide for each part of the content that is marked as 'Page X:'. Use appropriate headings, bullet points, and visuals to enhance understanding. Format the slides using MARP syntax, ensuring clarity and engagement for learners. ";
          $prompt .= "If the content for a slide is too long, split it into multiple slides as needed. It is important that the content fits well on each slide. Please make sure the slides are well-structured and visually appealing. ";
          $prompt .= "Add 1 slide at the beginning as start slide. Add 1 slide at the end as closing slide with source references if applicable.\n";
          $prompt .= "\n\nUse the following MARP example as a template for the slide design and structure:\n".$marp_example;
          $prompt .="\nDo not add unnecessary blank lines or spaces. Do not add any blank slides.";
          $prompt .="\n\nCourse Content:\n".$coursecontent;
          $action = new \core_ai\aiactions\generate_text(
              contextid: $context->id,
              userid: $USER->id,
              prompttext: $prompt,
          );
          $manager = \core\di::get(\core_ai\manager::class);
          $response = $manager->process_action($action);
          $results[] = 'Marp slide generation success: '.$response->get_success().' Error: '.$response->get_errormessage();
          if ($response->get_success() && isset($response->get_response_data()['generatedcontent'])) {
              $coursecontent = $response->get_response_data()['generatedcontent'] ?? '';
              $results[] = 'Marp slides generated successfully.';
              mtrace('Marp slides generated successfully.');
          }
          else {
            $success = false;
            $results[] = 'Error generating Marp slides: '.$response->get_errormessage();
            mtrace('Error generating Marp slides: '.$response->get_errormessage());
          }
          
        }

        // Todo: validate Marp content syntax and refine it again if necessary

        // Todo: render Marp slides to Images
        // use Marp CLI (https://marp.app/cli/) ???
        // use local marp installation
        if ($success) {
          $tempdir = make_temp_directory('aiplacement_slides');
          $uniqueid = uniqid();
          $mdfile = $tempdir . '/slides_'.$uniqueid.'.md';
          file_put_contents($mdfile, $coursecontent);
          $imagesdir = make_temp_directory('aiplacement_slides/images_'.$uniqueid);
          $imagefilename = $imagesdir . '/image';
          $scriptpath = $tempdir . '/execute_marp_'.$uniqueid.'.cmd';
          $pathtomarp = get_config('aiplacement_contentgenerator', 'pathtomarp');
          $pathtonode = 'C:\laragon\bin\nodejs\node-v22\node.exe';
          $logfile = $tempdir . '/marp_log_'.$uniqueid.'.txt';
          file_put_contents($logfile, 'Log file for Marp execution'."\n");

          // Normalize slashes
          $pathtomarp = str_replace('\\', '/', $pathtomarp);
          $pathtonode = str_replace('\\', '/', $pathtonode);
          $mdfile = str_replace('\\', '/', $mdfile);
          //$tempdir = str_replace('\\', '/', $tempdir);
          $imagefilename = str_replace('\\', '/', $imagefilename);
          $scriptpath = str_replace('\\', '/', $scriptpath);
          $logfile = str_replace('\\', '/', $logfile);

          // // Quote paths to avoid issues with spaces
          // $pathtomarp = escapeshellarg($pathtomarp);
          // $pathtonode = escapeshellarg($pathtonode);
          // $mdfile = escapeshellarg($mdfile);
          // //$tempdir = escapeshellarg($tempdir);
          // $imagefilename = escapeshellarg($imagefilename);
          //$scriptpath = escapeshellarg($scriptpath);

          // prevent risk of command injection
          //$pathtomarp = escapeshellcmd($pathtomarp);

          if (stripos(PHP_OS, 'WIN') === 0) {
              // Windows specific command
              // Windows: start marp through cmd.exe
              //$cmd = "$pathtomarp $mdfile --images png --image-scale 2 --allow-local-files --output $imagefilename >nul 2>&1";
              //$cmd = 'cmd /C ' . $pathtomarp ." $mdfile --images png --image-scale 2 --allow-local-files --output $imagefilename >nul 2>&1";
              //$cmd = 'cmd /C "' . $pathtomarp ." $mdfile --images png --image-scale 2 --allow-local-files --output $imagefilename".' >nul 2>&1"';
              //$cmd = 'cmd /C ' . $pathtonode ." $pathtomarp $mdfile --images png --image-scale 2 --output $imagefilename";
              //$cmd = 'cmd /C "' . $pathtonode ." $pathtomarp $mdfile --images png --image-scale 2 --output $imagefilename".'"';
              //$cmd = 'powershell -NoProfile -NonInteractive -Command "' .$pathtomarp." $mdfile --images png --image-scale 2 --output $imagefilename".'"';
              //$cmd = 'powershell -NoProfile -NonInteractive -Command ' .$pathtomarp." $mdfile --images png --image-scale 2 --output $imagefilename";
              //$cmd = escapeshellcmd($cmd);
              //$cmd = 'cmd /C "'.$CFG->dirroot . '/ai/placement/contentgenerator/marp/execute_marp.cmd" ' . $pathtomarp . ' ' . $mdfile . ' ' . $imagefilename;

              $script = '@echo off
REM Dieses Skript führt den Marp-Befehl mit umgeleiteter Ausgabe aus

'.$pathtomarp.' '.$mdfile.' --images png --image-scale 2 --allow-local-files --output '.$imagefilename.' < nul > nul 2>&1

REM Prüft den Exit-Code des vorherigen Befehls
if ERRORLEVEL 1 (
    echo success: 0
) else (
    echo success: 1
)';
              file_put_contents($scriptpath, $script);
              $cmd = $scriptpath;
          } else {
              // Unix/Linux specific command
              $cmd = "$pathtomarp $mdfile --images png --image-scale 2 --output $imagefilename";
          }
          
          mtrace('Executing command: '.$cmd);
          $output = [];
          $returnvar = 0;
          exec($cmd, $output, $returnvar);
          //exec($pathtomarp." --version", $output, $returnvar);
          //mtrace($output[0]);

          if ($returnvar !== 0) {
              $results[] = "Marp failed (".$returnvar."): " . implode("\n", $output);
              mtrace("Marp failed (".$returnvar."): " . implode("\n", $output));
              $success = false;
              // Todo: delete temp files
          }
          else {
              $results[] = "Marp slides rendered to images successfully: " . implode("\n", $output);
              mtrace("Marp slides rendered to images successfully: " . implode("\n", $output));
              // Todo: delete temp files
          }
         

        }

        // Todo: generate speaker text for each Marp slide
        // use action: generate_text


        // Todo: generate audio from speaker text
        // use new action: generate_audio with text-to-speech (https://wiki.mylab.th-luebeck.dev/de/myLab_services/ai_platform)
        //$newcontent = \aiplacement_contentgenerator\placement::generate_video($coursecontent, $data->additionalinstructions);
        //$results[] = 'Video generation success: '.$newcontent['success'].' Error: '.$newcontent['error'];


        // Todo: create video from slide images and audio
        // use php library ffmpeg-php???
        // Todo: delete temp files

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
