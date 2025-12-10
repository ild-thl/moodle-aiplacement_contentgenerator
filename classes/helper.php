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

    /**
     * Process PDF images and extract text content
     * @param array $pdfimages: An array of base64 encoded images representing PDF pages
     * @return array: An array with success status, extracted content, and result message
     */
    public function process_pdfimages($pdfimages) {
        $errorcount = 0;
        $successcount = 0;
        $pdfcontent = '';
        foreach ($pdfimages as $fileid => $images) {
            foreach ($images as $image) {
                // $image is a base64 encoded image string that shows one page of the pdf
                $result = \aiplacement_contentgenerator\placement::process_pdf($image);
                if ($result['success']) {
                    $successcount++;
                    $pdfcontent .= 'Page '.$successcount.':\n'.$result['generatedcontent']."\n\n";
                }
                else {
                    $errorcount++;
                }
            }
        }
        $result = [
            'success' => ($successcount > 0) ? true : false,
            'extractedcontent' => $pdfcontent,
            'pagesprocessed' => $successcount,
            'result' => 'Processed PDF images: '.$successcount.' success, '.$errorcount.' errors.'
        ];
        return $result;
    }

    /**
     * Refine course content using AI
     * @param string $content: The course content to refine
     * @param object $context: The context object
     * @param string $instructions: Additional instructions for refinement
     * @return array: An array with success status, extracted content, and result message
     */
    public function refine_content ($content, $context, $instructions = '') {
        global $USER, $CFG;
        $success = true;
        $refined = '';
        $result = [];
        $prompt = '';
        // if no additional instructions are given remove placeholder text for images 
        $logo_url = $CFG->wwwroot.'/ai/placement/contentgenerator/pix/logo.png';
        $logo_instruction = 'If there are any placeholder texts for images, that should show the TH Luebeck logo, please use the following url to embed the logo image: '.$logo_url;
        $no_footer_instruction = 'Ensure that there are no footer texts or page numbers included in the content.';
        if ($instructions === '') {
          $prompt = 'Please improve the structure and clarity of the course content. Ensure that the content is well-organized and easy to understand. ';
          $prompt .= $logo_instruction.' '.$no_footer_instruction."\n\nCourse Content:\n".$content;
        }
        else if ($instructions !== '') {
          $prompt = "Please refine the following course content according to these instructions: ";
          $prompt .= $instructions. " ".$logo_instruction.' '.$no_footer_instruction."\n\nCourse Content:\n".$content;
        }
        
        $action = new \core_ai\aiactions\generate_text(
            contextid: $context->id,
            userid: $USER->id,
            prompttext: $prompt,
        );
        $manager = \core\di::get(\core_ai\manager::class);
        $response = $manager->process_action($action);
        if ($response->get_success() && isset($response->get_response_data()['generatedcontent'])) {
            $refined = $response->get_response_data()['generatedcontent'] ?? '';
        }
        else {
            $success = false;
        }
        
        $result = [
            'success' => $success,
            'extractedcontent' => $refined,
            'result' => 'Refinement success: '.$response->get_success().', Error: '.$response->get_errormessage()
        ];
        return $result;
        
    }

    public function build_marp_slides ($coursecontent, $context, $numberofslides) {
        global $USER;
        $success = true;
        $slides = '';
        $result = [];
        $marp_example = 
        '<!-- This part has to be at the beginning of the Marp file -->

        ---

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

<!-- here starts the introduction slide  -->

        ---

        <!--
        class: lead
        -->

        <img src="http://localhost/moodle405kia/ai/placement/contentgenerator/pix/logo.png" alt="TH Lübeck Logo" width="150" style="
            position: absolute;
            top: 30px;
            right: 30px;">

        # Heading of the presentation

<!-- here starts the first slide  -->

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

<!-- here starts the second slide and so on  -->

        ---

        <!--
        class: follow
        -->

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
        if ($response->get_success() && isset($response->get_response_data()['generatedcontent'])) {
            $slides = $response->get_response_data()['generatedcontent'] ?? '';
        }
        else {
            $success = false;
        }
        $result = [
        'success' => $success,
        'marp_slides' => $slides,
        'result' => 'Marp slide generation success: '.$response->get_success().', Error: '.$response->get_errormessage()
        ];
        return $result;
    }

    public function render_images_from_marp_slides($marp_slides) {
        $result = [];
        $tempdir = make_temp_directory('aiplacement_slides');
        $uniqueid = uniqid();
        $mdfile = $tempdir . '/slides_'.$uniqueid.'.md';
        file_put_contents($mdfile, $marp_slides);
        $imagesdir = make_temp_directory('aiplacement_slides/images_'.$uniqueid);
        $imagefilename = $imagesdir . '/image';
        $pathtomarp = get_config('aiplacement_contentgenerator', 'pathtomarp');

        // Normalize slashes
        $pathtomarp = str_replace('\\', '/', $pathtomarp);
        $mdfile = str_replace('\\', '/', $mdfile);
        $imagefilename = str_replace('\\', '/', $imagefilename);

        $pathtomarp = escapeshellcmd($pathtomarp);

        if (stripos(PHP_OS, 'WIN') === 0) {
            // Windows specific command
            $cmd = "$pathtomarp $mdfile --images png --image-scale 2 --allow-local-files --output $imagefilename < nul > nul 2>&1";
        } else {
            // Unix/Linux specific command
            $cmd = "$pathtomarp $mdfile --images png --image-scale 2 --output $imagefilename";
        }
        
        //mtrace('Executing command: '.$cmd);
        $output = [];
        $returnvar = 0;
        exec($cmd, $output, $returnvar);

        if ($returnvar !== 0) {
            $result['result'] = "Marp failed (".$returnvar."): " . implode("\n", $output);
            $result['success'] = false;
            $result['imagesdir'] = null;
        }
        else {
            // rename images to have correct extension
            $files = scandir($imagesdir);
            $fileindex = 0;
            foreach ($files as $file) {
                if (is_file($imagesdir.'/'.$file)) {
                    if (pathinfo($file, PATHINFO_EXTENSION) === 'png') {
                        continue;
                    }
                    $fileindex++;
                    rename($imagesdir.'/'.$file, $imagesdir.'/slide_'.$fileindex.'.png');
                }
            }
            $result['result'] = "Marp slides rendered to images successfully: " . implode("\n", $output);
            $result['success'] = true;
            $result['imagesdir'] = $imagesdir;
            $result['contentid'] = $uniqueid;
        }
        //Todo: delete temp file, do not delete imagesdir here, it is needed later
        //unlink($mdfile);
        return $result;
    }

    public function generate_speaker_text($marp_slides, $context) {
        global $USER;
        $result = [];
        $speakertext = '';
        $success = true;

        $prompt = '';
        $prompt .= "You are an expert in creating speaker texts for educational presentations.\n";
        $prompt .= "Please generate a speaker text for each slide in the presentation, provided later as marp slides. The speaker text should complement the slide content, providing additional explanations, context, and insights to enhance the audience's understanding. ";
        $prompt .= "Do not explain the images like logos or decorative images, focus on the educational content of each slide. ";
        $prompt .= "Ensure that the speaker text is clear, engaging, and aligned with the content of each slide. ";
        $prompt .= "Format the speaker text by clearly indicating which slide it corresponds to. ";
        $prompt .= "Mark the beginning of each slide's speaker text with 'New slide:'.\n\n";
        $prompt .= "\n\nMarp slides:\n".$marp_slides;

        $action = new \core_ai\aiactions\generate_text(
            contextid: $context->id,
            userid: $USER->id,
            prompttext: $prompt,
        );
        $manager = \core\di::get(\core_ai\manager::class);
        $response = $manager->process_action($action);
        $result['result'] = 'Speaker text generation success: '.$response->get_success().' Error: '.$response->get_errormessage();
        if ($response->get_success() && isset($response->get_response_data()['generatedcontent'])) {
            $speakertext = $response->get_response_data()['generatedcontent'] ?? '';
        }
        else {
          $success = false;
        }
        $result = [
            'success' => $success,
            'speaker_text' => $speakertext,
            'result' => 'Speaker text generation success: '.$response->get_success().', Error: '.$response->get_errormessage()
        ];
        return $result;
    }

    /**
     * Generate audio from text using text-to-speech
     * @param string $texttotransform: The texts to transform into audio
     * @return array: An array with success status and audio file path
     */
    public function generate_audio($texttotransform, $contentid, $context) {
        $success = true;
        $result = [];
        $errorcount = 0;

        // $texttotransform contains multiple texts. Each text starts with "New slide:".
        // add each text to an array
        $texts = explode('New slide:', $texttotransform);
        // remove empty entries
        $texts = array_filter($texts, function($text) {
            return trim($text) !== '';
        });
        
        $audiodir = make_temp_directory('aiplacement_slides/audio_'.$contentid);
        $fileindex = 0;
        foreach ($texts as $text) {
            $result = \aiplacement_contentgenerator\placement::text_to_speech($text, $context);
            if ($result['success']) {
                $fileindex++;
                $audio = $result['generatedcontent'];
                $audiofile = $audiodir.'/audio_'.$fileindex.'.mp3';
                file_put_contents($audiofile, $audio);
            }
            else {
                $errorcount++;
                $success = false;
            }
        }
        $result = [
            'success' => $success,
            'audiodir' => $audiodir,
            'result' => 'Audio generation success: '.$fileindex.' audio files created. Errors: '.$errorcount
        ];

        return $result;
    }

    public function generate_video_from_images_and_audio($imagesdir, $audiodir, $contentid) {
        $result = [];
        $videofilepath = '';
        $audiodir = str_replace('\\', '/', $audiodir);
        $imagesdir = str_replace('\\', '/', $imagesdir);

        $videodir = make_temp_directory('aiplacement_slides/video_'.$contentid);
        $videofilepath = $videodir.'/video_'.$contentid.'.mp4';
        $videofilepath = str_replace('\\', '/', $videofilepath);

        // check if images and audio files exist and each image has a corresponding audio file
        $imagecount = 1;
        if (!is_file($imagesdir.'/slide_'.$imagecount.'.png')) {
            $result['result'] = 'No slide images found in '.$imagesdir;
            $result['success'] = false;
            $result['videofilepath'] = $videofilepath;
            return $result;
        }
        while (is_file($imagesdir.'/slide_'.$imagecount.'.png')) {
            mtrace('Found image file: '.$imagesdir.'/slide_'.$imagecount.'.png');
            if (!is_file($audiodir.'/audio_'.$imagecount.'.mp3')) {
                $result['result'] = 'Missing audio file for slide '.$imagecount;
                $result['success'] = false;
                $result['videofilepath'] = $videofilepath;
                return $result;
            }
            mtrace('Found audio file: '.$audiodir.'/audio_'.$imagecount.'.mp3');
            $imagecount++;
        }

        // check if ffmpeg is installed
        $ffmpeg_path = 'C:/laragon/bin/ffmpeg/ffmpeg.exe'; // Todo: make configurable
        $ffmpegcheck = shell_exec($ffmpeg_path.' -version');
        if (stripos($ffmpegcheck, 'ffmpeg version') === false) {
            $result['result'] = 'ffmpeg is not installed or not found in PATH.';
            $result['success'] = false;
            $result['videofilepath'] = $videofilepath;
            return $result;
        }

        $ffmpeg_path = escapeshellarg($ffmpeg_path);
        for ($i = 1; $i < $imagecount; $i++) {
            mtrace('Build video from slide '.$i.' with image and audio.');
            $slide = '"'.$imagesdir.'/slide_'.$i.'.png"';
            $audio = '"'.$audiodir.'/audio_'.$i.'.mp3"';
            $video = '"'.$videodir.'/video_'.$i.'.mp4"';
            $video = str_replace('\\', '/', $video);
            $cmd = "$ffmpeg_path -y -loop 1 -i ".$slide.
                   " -i ".$audio.
                   " -c:v libx264 -tune stillimage -c:a aac -pix_fmt yuv420p -shortest ".$video."  < nul > nul 2>&1";
            mtrace('Executing command: '.$cmd);
            $output = [];
            $returnvar = 0;
            exec($cmd, $output, $returnvar);
            if ($returnvar !== 0) {
                $result['result'] = "Video generation failed (".$returnvar."): " . implode("\n", $output);
                $result['success'] = false;
                $result['videofilepath'] = $videofilepath;
                return $result;
            }
            else {
                mtrace('Video for slide '.$i.' generated successfully.');
            }
        }

        // if videodir contains multiple video files, merge them into one
        if ($imagecount > 2) {
            mtrace('Merging individual slide videos into final video.');
            $filelist = $videodir.'/filelist.txt';
            $filelistcontent = '';
            for ($i = 1; $i < $imagecount; $i++) {
                $filelistcontent .= "file 'video_".$i.".mp4'\n";
            }
            file_put_contents($filelist, $filelistcontent);
            $cmd = "$ffmpeg_path -y -f concat -safe 0 -i ".$filelist." -c copy ".$videofilepath." < nul > nul 2>&1";
            mtrace('Executing command: '.$cmd);
            $output = [];
            $returnvar = 0;
            exec($cmd, $output, $returnvar);
            if ($returnvar !== 0) {
                $result['result'] = "Video merging failed (".$returnvar."): " . implode("\n", $output);
                $result['success'] = false;
                $result['videofilepath'] = $videofilepath;
                return $result;
            }
            else {
                mtrace('Final video generated successfully at '.$videofilepath);
            }
        }
        else {
            // rename single video file to final videofilepath
            rename($videodir.'/video_1.mp4', $videofilepath);
        }
        
        $result['result'] = "Video generated successfully";
        $result['success'] = true;
        $result['videofilepath'] = $videofilepath;

        return $result;
    }

    public function save_video_to_course_files($videofilepath, $courseid) {
        global $USER;
        $result = [];
        $filename = basename($videofilepath);
        // add date and time to the end of the filename
        $datetime = date('Ymd_His');
        $filename = pathinfo($filename, PATHINFO_FILENAME).'_'.$datetime.'.'.pathinfo($filename, PATHINFO_EXTENSION);
        $fs = get_file_storage();
        //$context = \context_course::instance($courseid);
        $usercontext = \context_user::instance($USER->id);
        $filerecord = new \stdClass();
        $filerecord->contextid = $usercontext->id;
        $filerecord->component = 'user';
        $filerecord->filearea = 'private';
        $filerecord->itemid = 0;
        $filerecord->filepath = '/';
        $filerecord->filename = 'ai_generated_'.$filename;
        $filerecord->userid = $USER->id;

        // check if videofilepath exists
        if (!is_file($videofilepath)) {
            $result['success'] = false;
            $result['result'] = 'Video file not found: '.$videofilepath;
            return $result;
        }

        // create file in course files
        if ($file = $fs->create_file_from_pathname($filerecord, $videofilepath)) {
            $result['success'] = true;
            $fileurl = \moodle_url::make_pluginfile_url(
                $file->get_contextid(),
                $file->get_component(),
                $file->get_filearea(),
                $file->get_itemid(),
                $file->get_filepath(),
                $file->get_filename()
            );
            $result['fileurl'] = $fileurl->out(false);
            $result['result'] = 'Video file saved to course files successfully. URL: '.$result['fileurl'];
        }
        else {
            $result['success'] = false;
            $result['result'] = 'Failed to save video file to course files.';
        }
        return $result;
    }

    /**
     * Cleanup temporary files and directories
     * @param string $contentid: The content ID to cleanup temporary files for
     * @return array: An array with success status and result message
     */
    public function cleanup_temporary_files($contentid) {
        $result = [];
        $success = true;
        $tempdirs = [
            make_temp_directory('aiplacement_slides/images_'.$contentid),
            make_temp_directory('aiplacement_slides/audio_'.$contentid),
            make_temp_directory('aiplacement_slides/video_'.$contentid),
        ];
        foreach ($tempdirs as $dir) {
            if (is_dir($dir)) {
                $files = scandir($dir);
                foreach ($files as $file) {
                    if (is_file($dir.'/'.$file)) {
                        unlink($dir.'/'.$file);
                    }
                }
                rmdir($dir);
            }
        }
        $tempdir = make_temp_directory('aiplacement_slides');
        $mdfile = $tempdir . '/slides_'.$contentid.'.md';
        if (is_file($mdfile)) {
            unlink($mdfile);
        }
        $result['success'] = $success;
        $result['result'] = 'Temporary files and directories cleaned up.';
        return $result;
    }

}