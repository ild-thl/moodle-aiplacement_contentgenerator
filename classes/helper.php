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
            if ($mimetype === 'text/plain') {
                return $this->get_text_from_text_file($file);
            }
            // Todo: add markdown and rtf processing here and allow mimetypes above
        }   
        return false;
    }

    /**
     * Check whether a file id references a PDF file.
     *
     * @param int $fileid Moodle file id.
     * @return bool
     */
    public function is_pdf_fileid($fileid) {
        $fs = get_file_storage();
        $file = $fs->get_file_by_id((int)$fileid);
        if (!$file || $file->is_directory()) {
            return false;
        }
        return $file->get_mimetype() === 'application/pdf';
    }

    /**
     * Render selected PDF file ids to base64 PNG page images via poppler-utils (pdftoppm).
     *
     * @param array $pdffileids
     * @return array{success:bool,pdfimages:array,result:string}
     */
    public function build_pdf_images_from_fileids($pdffileids) {
        $result = [
            'success' => true,
            'pdfimages' => [],
            'result' => ''
        ];
        $pdftoppm = get_config('aiplacement_contentgenerator', 'pathtopdftoppm');
        if (empty($pdftoppm)) {
            $pdftoppm = '/usr/bin/pdftoppm';
        }
        $pdftoppm = str_replace('\\', '/', $pdftoppm);
        $pdftoppmcheck = shell_exec(escapeshellarg($pdftoppm).' -v 2>&1');
        if ($pdftoppmcheck === null || stripos($pdftoppmcheck, 'pdftoppm version') === false) {
            return [
                'success' => false,
                'pdfimages' => [],
                'result' => 'PDF page rendering failed: pdftoppm (poppler-utils) is not installed or not executable at "'.$pdftoppm.'".'
            ];
        }

        $fs = get_file_storage();
        $processedfiles = 0;
        $processedpages = 0;
        $errors = [];

        $tempbase = make_temp_directory('aiplacement_slides/pdf_render_'.uniqid());
        $tempbase = str_replace('\\', '/', $tempbase);
        $pdfworkdir = $tempbase.'/pdf';
        $imgworkdir = $tempbase.'/img';
        mkdir($pdfworkdir, 0777, true);
        mkdir($imgworkdir, 0777, true);

        foreach ($pdffileids as $fileid) {
            $fileid = (int)$fileid;
            if ($fileid <= 0) {
                continue;
            }
            $file = $fs->get_file_by_id($fileid);
            if (!$file || $file->is_directory()) {
                $errors[] = 'File '.$fileid.' not found.';
                continue;
            }
            if ($file->get_mimetype() !== 'application/pdf') {
                continue;
            }

            $processedfiles++;
            $pdffile = $pdfworkdir.'/file_'.$fileid.'.pdf';
            file_put_contents($pdffile, $file->get_content());

            $imageprefix = $imgworkdir.'/file_'.$fileid.'_page';
            $cmd = escapeshellarg($pdftoppm).' -png '.escapeshellarg($pdffile).' '.escapeshellarg($imageprefix).' 2>&1';
            $output = [];
            $returnvar = 0;
            exec($cmd, $output, $returnvar);
            if ($returnvar !== 0) {
                $errors[] = 'pdftoppm failed for file '.$fileid.' ('.$returnvar.'): '.implode("\n", $output);
                continue;
            }

            $images = [];
            $generatedfiles = glob($imageprefix.'-*.png');
            if (!empty($generatedfiles)) {
                natsort($generatedfiles);
                foreach ($generatedfiles as $generatedfile) {
                    $binary = file_get_contents($generatedfile);
                    if ($binary === false) {
                        continue;
                    }
                    $images[] = 'data:image/png;base64,'.base64_encode($binary);
                    $processedpages++;
                }
            }
            if (!empty($images)) {
                $result['pdfimages'][$fileid] = $images;
            } else {
                $errors[] = 'No page images generated for file '.$fileid.'.';
            }
        }

        // Cleanup conversion temp dir.
        if (is_dir($tempbase)) {
            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($tempbase, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            foreach ($items as $item) {
                if ($item->isDir()) {
                    @rmdir($item->getPathname());
                } else {
                    @unlink($item->getPathname());
                }
            }
            @rmdir($tempbase);
        }

        if (!empty($errors)) {
            $result['success'] = !empty($result['pdfimages']);
        }
        $result['result'] = 'Rendered PDF pages with pdftoppm: '.$processedfiles.' PDF files, '.$processedpages.
            ' pages, '.count($errors).' errors.'.(!empty($errors) ? ' First error: '.$errors[0] : '');
        return $result;
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
        $errors = [];
        foreach ($pdfimages as $fileid => $images) {
            $pageindex = 0;
            foreach ($images as $image) {
                $pageindex++;
                // $image is a base64 encoded image string that shows one page of the pdf
                $result = \aiplacement_contentgenerator\placement::process_pdf($image);
                if ($result['success']) {
                    $successcount++;
                    $pdfcontent .= 'Page '.$successcount.':\n'.$result['generatedcontent']."\n\n";
                }
                else {
                    $errorcount++;
                    $errorcode = (int)($result['errorcode'] ?? 0);
                    $errormessage = trim((string)($result['error'] ?? ''));
                    if ($errormessage === '') {
                        $errormessage = 'Unknown error during PDF extraction.';
                    }
                    $errors[] = 'File '.$fileid.', page '.$pageindex.
                        ' failed ('.$errorcode.'): '.$errormessage;
                }
            }
        }
        if (!empty($errors)) {
            foreach ($errors as $error) {
                mtrace($error);
            }
        }
        $result = [
            'success' => ($successcount > 0) ? true : false,
            'extractedcontent' => $pdfcontent,
            'pagesprocessed' => $successcount,
            'result' => 'Processed PDF images: '.$successcount.' success, '.$errorcount.' errors.'.
                (!empty($errors) ? ' First error: '.$errors[0] : '')
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
        $defaultlogo = $CFG->wwwroot.'/ai/placement/contentgenerator/pix/logo.png';
        $logo_url = $this->get_refine_content_logo_url($defaultlogo);
        $template = get_config('aiplacement_contentgenerator', 'refinecontentprompttemplate');
        if (empty(trim((string)$template))) {
            $template = "Please refine the following course content. Ensure that the content is well-organized, ".
                "easy to understand, and suitable for educational slides.\n\n".
                "Additional user instructions:\n{{additionalinstructions}}\n\n".
                "If there are placeholders for the TH Luebeck logo, use this image URL: {{logo_url}}\n".
                "Ensure that there are no footer texts or page numbers included in the content.\n\n".
                "Course content:\n{{content}}";
        }

        $prompt = str_replace(
            ['{{additionalinstructions}}', '{{logo_url}}', '{{content}}'],
            [trim((string)$instructions), $logo_url, $content],
            $template
        );
        
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

    /**
     * Get the logo URL configured for refine-content prompts.
     *
     * @param string $fallbackurl Default logo URL.
     * @return string
     */
    private function get_refine_content_logo_url(string $fallbackurl): string {
        $systemcontext = \context_system::instance();
        $fs = get_file_storage();
        $files = $fs->get_area_files(
            $systemcontext->id,
            'aiplacement_contentgenerator',
            'refinecontentlogo',
            0,
            'filename',
            false
        );

        if (empty($files)) {
            return $fallbackurl;
        }

        $file = reset($files);
        if (!$file) {
            return $fallbackurl;
        }

        return \moodle_url::make_pluginfile_url(
            $file->get_contextid(),
            $file->get_component(),
            $file->get_filearea(),
            $file->get_itemid(),
            $file->get_filepath(),
            $file->get_filename(),
            false
        )->out(false);
    }

    /**
     * Build Marp markdown slides from course content.
     *
     * @param string $coursecontent Aggregated/refined content.
     * @param \context $context Moodle context.
     * @param int $numberofslides Target number of slides.
     * @return array
     */
    public function build_marp_slides ($coursecontent, $context, $numberofslides) {
        global $USER;
        $success = true;
        $slides = '';
        $result = [];
        $marp_example = get_config('aiplacement_contentgenerator', 'buildmarpslidesexample');
        if (empty(trim((string)$marp_example))) {
            $marp_example = "<!-- This part has to be at the beginning of the Marp file -->\n\n".
                "---\n\n".
                "marp: true\n".
                "style: |\n".
                "    section.lead {\n".
                "    border-bottom: 100px solid #e4003a;\n".
                "    padding-bottom: 110px;\n".
                "    }\n".
                "    section:not(.lead) {\n".
                "    border-bottom: 20px solid #e4003a;\n".
                "    padding-bottom: 20px;\n".
                "    }\n\n".
                "<!-- here starts the introduction slide  -->\n\n".
                "---\n\n".
                "<!--\n".
                "class: lead\n".
                "-->\n\n".
                "<img src=\"https://example.org/logo.png\" alt=\"Logo\" width=\"150\" style=\"\n".
                "    position: absolute;\n".
                "    top: 30px;\n".
                "    right: 30px;\">\n\n".
                "# Heading of the presentation\n\n".
                "<!-- here starts the first slide  -->\n\n".
                "---\n\n".
                "<!--\n".
                "class: follow\n".
                "-->\n\n".
                "# Heading of the first slide\n\n".
                "## Subheading of the first slide\n\n".
                "Text content for the first slide.\n\n".
                "- **First bullet point** example text.\n".
                "- **Second bullet point** example text.\n".
                "- **Third bullet point** example text.\n\n".
                "<!-- here starts the second slide and so on  -->\n\n".
                "---\n\n".
                "<!--\n".
                "class: follow\n".
                "-->\n\n".
                "# Heading of the second slide";
        }

        $prompttemplate = get_config('aiplacement_contentgenerator', 'buildmarpslidesprompttemplate');
        if (empty(trim((string)$prompttemplate))) {
            $prompttemplate = "You are an expert in creating educational presentations.\n".
                "Please create {{numberofslides}} MARP slides for course content, that will be provided later.\n".
                "Create 1 slide for each part of the content that is marked as 'Page X:'. Use appropriate headings, bullet points, and visuals to enhance understanding. Format the slides using MARP syntax, ensuring clarity and engagement for learners. ".
                "If the content for a slide is too long, split it into multiple slides as needed. It is important that the content fits well on each slide. Please make sure the slides are well-structured and visually appealing. ".
                "Add 1 slide at the beginning as start slide. Add 1 slide at the end as closing slide with source references if applicable.\n\n".
                "Use the following MARP example as a template for the slide design and structure:\n{{marp_example}}\n".
                "Do not add unnecessary blank lines or spaces. Do not add any blank slides.\n\n".
                "Course Content:\n{{content}}";
        }

        $prompt = str_replace(
            ['{{numberofslides}}', '{{marp_example}}', '{{content}}'],
            [(string)$numberofslides, (string)$marp_example, (string)$coursecontent],
            $prompttemplate
        );
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

    /**
     * Render Marp markdown slides to PNG files.
     *
     * @param string $marp_slides Marp markdown.
     * @return array
     */
    public function render_images_from_marp_slides($marp_slides) {
        $result = [];
        $tempdir = make_temp_directory('aiplacement_slides');
        $uniqueid = uniqid();
        $mdfile = $tempdir . '/slides_'.$uniqueid.'.md';
        file_put_contents($mdfile, $marp_slides);
        $imagesdir = make_temp_directory('aiplacement_slides/images_'.$uniqueid);
        $temphomedir = make_temp_directory('aiplacement_slides/tmphome_'.$uniqueid);
        $imagefilename = $imagesdir . '/image';
        $pathtomarp = get_config('aiplacement_contentgenerator', 'pathtomarp');

        // Normalize slashes
        $pathtomarp = str_replace('\\', '/', $pathtomarp);
        $mdfile = str_replace('\\', '/', $mdfile);
        $imagefilename = str_replace('\\', '/', $imagefilename);
        $temphomedir = str_replace('\\', '/', $temphomedir);

        $pathtomarp = escapeshellcmd($pathtomarp);
        $mdfilearg = escapeshellarg($mdfile);
        $imagefilenamearg = escapeshellarg($imagefilename);
        $temphomedirarg = escapeshellarg($temphomedir);

        if (stripos(PHP_OS, 'WIN') === 0) {
            // Windows specific command
            $cmd = "$pathtomarp $mdfile --images png --image-scale 2 --allow-local-files --output $imagefilename < nul > nul 2>&1";
        } else {
            // Unix/Linux specific command
            // In non-interactive contexts Marp may wait for stdin.
            $cmd = "HOME=$temphomedirarg $pathtomarp $mdfilearg --images png --image-scale 2 ".
                "--allow-local-files ".
                "--no-sandbox ".
                "--disable-gpu ".
                "--disable-dev-shm-usage ".
                "--headless ".
                "--no-stdin ".
                "--output $imagefilenamearg ".
                "</dev/null";
        }
        
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
                    $fileindex++;
                    rename($imagesdir.'/'.$file, $imagesdir.'/slide_'.$fileindex.'.png');
                }
            }
            $result['result'] = "Marp slides rendered to images successfully: " . implode("\n", $output);
            $result['success'] = true;
            $result['imagesdir'] = $imagesdir;
            $result['contentid'] = $uniqueid;
        }
        return $result;
    }

    /**
     * Generate speaker text for each Marp slide.
     *
     * @param string $marp_slides Marp markdown.
     * @param \context $context Moodle context.
     * @return array
     */
    public function generate_speaker_text($marp_slides, $context) {
        global $USER;
        $result = [];
        $speakertext = '';
        $success = true;

        $prompttemplate = get_config('aiplacement_contentgenerator', 'generatespeakertextprompttemplate');
        if (empty(trim((string)$prompttemplate))) {
            $prompttemplate = "You are an expert in creating speaker texts for educational presentations.\n".
                "Please generate a speaker text for each slide in the presentation, provided later as marp slides. The speaker text should complement the slide content, providing additional explanations, context, and insights to enhance the audience's understanding. ".
                "Do not explain the images like logos or decorative images, focus on the educational content of each slide. ".
                "Ensure that the speaker text is clear, engaging, and aligned with the content of each slide. ".
                "Format the speaker text by clearly indicating which slide it corresponds to. ".
                "Mark the beginning of each slide's speaker text with 'New slide:'.\n\n".
                "Marp slides:\n{{marp_slides}}";
        }
        $prompt = str_replace('{{marp_slides}}', (string)$marp_slides, $prompttemplate);

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

    /**
     * Build the final video from slide images and generated audio.
     *
     * @param string $imagesdir Directory containing slide images.
     * @param string $audiodir Directory containing audio files.
     * @param string $contentid Unique content id.
     * @return array
     */
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
        $ffmpeg_path = get_config('aiplacement_contentgenerator', 'pathtoffmpeg');
        $ffmpeg_path = str_replace('\\', '/', $ffmpeg_path);
        $ffmpegcheck = shell_exec($ffmpeg_path.' -version');
        if (stripos($ffmpegcheck, 'ffmpeg version') === false) {
            $result['result'] = 'Error: Generating video failed. ffmpeg is not installed or not found in PATH.';
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
            if (stripos(PHP_OS, 'WIN') === 0) {
                $cmd = "$ffmpeg_path -y -loop 1 -i ".$slide.
                   " -i ".$audio.
                   " -c:v libx264 -tune stillimage -c:a aac -pix_fmt yuv420p -shortest ".$video." < nul > nul 2>&1";
            } else {
                $cmd = "$ffmpeg_path -y -loop 1 -i ".$slide.
                   " -i ".$audio.
                   " -c:v libx264 -tune stillimage -c:a aac -pix_fmt yuv420p -shortest ".$video." > /dev/null 2>&1";
            }
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
            $filelistarg = escapeshellarg($filelist);
            $videofilepatharg = escapeshellarg($videofilepath);
            if (stripos(PHP_OS, 'WIN') === 0) {
                $cmd = "$ffmpeg_path -y -f concat -safe 0 -i $filelistarg -c copy $videofilepatharg < nul 2>&1";
            } else {
                $cmd = "$ffmpeg_path -y -f concat -safe 0 -i $filelistarg -c copy $videofilepatharg </dev/null 2>&1";
            }
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

    /**
     * Save generated video file to the user's private file area.
     *
     * @param string $videofilepath Absolute video file path.
     * @param int $courseid Course id.
     * @return array
     */
    public function save_video_to_course_files($videofilepath, $courseid) {
        global $USER;
        $result = [];
        $filename = basename($videofilepath);
        // add date and time to the end of the filename
        $datetime = date('Ymd_His');
        $filename = pathinfo($filename, PATHINFO_FILENAME).'_'.$datetime.'.'.pathinfo($filename, PATHINFO_EXTENSION);
        $fs = get_file_storage();
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

        $removedirectoryrecursively = function(string $directory) use (&$success): void {
            if (!is_dir($directory)) {
                return;
            }

            $items = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($items as $item) {
                $path = $item->getPathname();
                if ($item->isDir()) {
                    if (!@rmdir($path)) {
                        $success = false;
                    }
                } else {
                    if (!@unlink($path)) {
                        $success = false;
                    }
                }
            }

            if (!@rmdir($directory)) {
                $success = false;
            }
        };

        $tempdirs = [
            make_temp_directory('aiplacement_slides/images_'.$contentid),
            make_temp_directory('aiplacement_slides/audio_'.$contentid),
            make_temp_directory('aiplacement_slides/video_'.$contentid),
            make_temp_directory('aiplacement_slides/tmphome_'.$contentid),
        ];
        foreach ($tempdirs as $dir) {
            $removedirectoryrecursively($dir);
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