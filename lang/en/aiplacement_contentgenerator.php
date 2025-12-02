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
 * Strings for component aiplacement_contentgenerator, language 'en'.
 *
 * @package    aiplacement_contentgenerator
 * @copyright  2025 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['additional_instructions'] = 'Additional instructions for content generation';
$string['additional_instructions_default'] = 'Extract the text from the above document as if you were reading it naturally. Return the tables in html format. Return the equations in LaTeX representation. If there is an image in the document and image caption is not present, add a small description of the image inside the <img></img> tag; otherwise, add the image caption inside <img></img>. Watermarks should be wrapped in brackets. Ex: <watermark>OFFICIAL COPY</watermark>. Page numbers should be wrapped in brackets. Ex: <page_number>14</page_number> or <page_number>9/22</page_number>. Prefer using ☐ and ☑ for check boxes.';
$string['generatecontent'] = 'Generate AI content';
$string['generation_started'] = 'Content generation has been started. You will be notified by e-mail once it is complete. You can continue working in the course meanwhile by clicking the continue button below.';
$string['error_nocontentselected'] = 'Please select at least one content module for generation.';
$string['extract_pdf'] = 'Extract PDF content';
$string['extract_pdf_setting'] = 'Enable PDF content extraction';
$string['extract_pdf_setting_desc'] = 'If enabled, users can extract text content from PDF files using AI.';
$string['mail_content_generated_subject'] = 'Your AI content generation is complete';
$string['mail_content_generated_message'] = 'The AI content generation for your course has been completed. You can view the generated content by visiting the course page: {$a->courselink}';
$string['mail_content_generated_messagehtml'] = '<p>The AI content generation for your course has been completed.</p><p>You can view the generated content by visiting the course page: <a href="{$a->courselink}">Course Page</a></p>';
$string['modselection'] = 'Select PDF content for content generation';
$string['pathtomarp'] = 'Path to Marp';
$string['pathtomarp_desc'] = 'Specify the full path to the Marp executable. Leave this field empty to use the default installation.';
$string['pluginname'] = 'Generate content placement';
$string['privacy:metadata'] = 'The Generate content placement plugin does not store any personal data.';
