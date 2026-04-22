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
$string['extractpdfsettings'] = 'Prompt settings for extracting PDF content';
$string['extractpdfsettings_desc'] = 'The prompt template for PDF extraction is configured in the AI provider plugin settings: <a href="{$a->url}">aiprovider_myai / extract_pdf</a>.';
$string['generatetextsettings'] = 'Prompt settings for text generation';
$string['generatetextsettings_desc'] = 'Text generation is required for several steps, including the refining of course content and Marp slide generation. Configure the prompt templates in the AI provider plugin settings: <a href="{$a->url}">aiprovider_myai / generate_text</a>.';
$string['buildmarpslidessettings'] = 'Prompt settings for Marp slide generation';
$string['buildmarpslidessettings_desc'] = 'Configure how Marp slides are generated from refined course content.';
$string['buildmarpslidesprompttemplate'] = 'Marp slide generation prompt template';
$string['buildmarpslidesprompttemplate_desc'] = 'Use placeholders {{numberofslides}}, {{marp_example}}, and {{content}}. {{numberofslides}} contains the calculated target number of slides. {{marp_example}} contains the template from the setting below. {{content}} contains the refined course content.';
$string['buildmarpslidesprompttemplate_default'] = 'You are an expert in creating educational presentations.
Please create {{numberofslides}} MARP slides for course content, that will be provided later.
Create 1 slide for each part of the content that is marked as \'Page X:\'. Use appropriate headings, bullet points, and visuals to enhance understanding. Format the slides using MARP syntax, ensuring clarity and engagement for learners. If the content for a slide is too long, split it into multiple slides as needed. It is important that the content fits well on each slide. Please make sure the slides are well-structured and visually appealing. Add 1 slide at the beginning as start slide. Add 1 slide at the end as closing slide with source references if applicable.

Use the following MARP example as a template for the slide design and structure:
{{marp_example}}

Do not add unnecessary blank lines or spaces. Do not add any blank slides.

Course Content:
{{content}}';
$string['buildmarpslidesexample'] = 'MARP example template';
$string['buildmarpslidesexample_desc'] = 'Template example inserted into the Marp prompt as {{marp_example}}.';
$string['buildmarpslidesexample_default'] = '<!-- This part has to be at the beginning of the Marp file -->

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

<img src="https://example.org/logo.png" alt="Logo" width="150" style="
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
$string['mail_content_generated_subject'] = 'Your AI video creation is complete';
$string['mail_content_generated_message'] = 'The AI video creation for your course has been completed. You can find the video in your "My files"-area in your course: {$a->courselink}';
$string['mail_content_generated_messagehtml'] = '<p>The AI content generation for your course has been completed.</p><p>You can find the video in your "My files"-area in your course: <a href="{$a->courselink}">Course Page</a></p>';
$string['modselection'] = 'Select PDF content for content generation';
$string['pathtomarp'] = 'Path to Marp';
$string['pathtomarp_desc'] = 'Specify the full path to the Marp executable. Leave this field empty to use the default installation.';
$string['pluginname'] = 'Generate content placement';
$string['privacy:metadata'] = 'The Generate content placement plugin does not store any personal data.';
$string['pathtoffmpeg'] = 'Path to ffmpeg';
$string['pathtoffmpeg_desc'] = 'Specify the full path to the ffmpeg executable. Leave this field empty to use the default installation.';
$string['pathtopdftoppm'] = 'Path to pdftoppm';
$string['pathtopdftoppm_desc'] = 'Specify the full path to the pdftoppm executable (poppler-utils). Leave this field empty to use the default installation.';
$string['refinecontentsettings'] = 'Prompt settings for refining selected course content';
$string['refinecontentsettings_desc'] = 'These settings control how selected course content is refined before slide generation.';
$string['refinecontentprompttemplate'] = 'Refinement prompt template';
$string['refinecontentprompttemplate_desc'] = 'Use placeholders {{additionalinstructions}}, {{content}}, and {{logo_url}}. {{additionalinstructions}} contains the additional instructions users can enter directly in the form when starting content generation. {{content}} contains the merged content from the selected course materials. {{logo_url}} contains the URL of the logo configured below.';
$string['refinecontentprompttemplate_default'] = 'Please refine the following course content. Ensure that the content is well-organized, easy to understand, and suitable for educational slides.

Additional user instructions:
{{additionalinstructions}}

If there are placeholders for a logo, use this image URL: {{logo_url}}
Ensure that there are no footer texts or page numbers included in the content.

Course content:
{{content}}';
$string['refinecontentlogo'] = 'Logo for content refinement prompts';
$string['refinecontentlogo_desc'] = 'Optional logo image used in the refinement prompt as {{logo_url}}. If empty, the default plugin logo URL is used.';
$string['mail_content_generation_failed_subject'] = 'AI content generation failed';
$string['mail_content_generation_failed_message'] = 'The AI content generation for your course has failed. The following errors occurred: {$a}';
$string['mail_content_generation_failed_messagehtml'] = '<p>The AI content generation for your course has failed. The following errors occurred: {$a}</p>';