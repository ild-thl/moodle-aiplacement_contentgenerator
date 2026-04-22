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

$string['additional_instructions'] = 'Zusätzliche Anweisungen für die Inhaltsgenerierung';
$string['additional_instructions_default'] = 'Extract the text from the above document as if you were reading it naturally. Return the tables in html format. Return the equations in LaTeX representation. If there is an image in the document and image caption is not present, add a small description of the image inside the <img></img> tag; otherwise, add the image caption inside <img></img>. Watermarks should be wrapped in brackets. Ex: <watermark>OFFICIAL COPY</watermark>. Page numbers should be wrapped in brackets. Ex: <page_number>14</page_number> or <page_number>9/22</page_number>. Prefer using ☐ and ☑ for check boxes.';
$string['generatecontent'] = 'Generiere KI-Inhalte';
$string['generation_started'] = 'Die Inhaltsgenerierung wurde gestartet. Sie werden per E-Mail benachrichtigt, sobald sie abgeschlossen ist. Sie können in der Zwischenzeit im Kurs weiterarbeiten, indem Sie unten auf die Schaltfläche "Weiter" klicken.';
$string['error_nocontentselected'] = 'Bitte wähle mindestens ein Inhaltsmodul für die Generierung aus.';
$string['extract_pdf'] = 'PDF-Inhalte extrahieren';
$string['extract_pdf_setting'] = 'PDF-Inhaltsextraktion aktivieren';
$string['extract_pdf_setting_desc'] = 'Wenn aktiviert, können Benutzer Textinhalte aus PDF-Dateien mithilfe von KI extrahieren.';
$string['extractpdfsettings'] = 'Prompt-Einstellungen für PDF-Extraktion';
$string['extractpdfsettings_desc'] = 'Das Prompt-Template für die PDF-Extraktion wird in den Einstellungen des AI-Provider-Plugins konfiguriert: <a href="{$a->url}">aiprovider_myai / extract_pdf</a>.';
$string['generatetextsettings'] = 'Prompt-Einstellungen für Textgenerierung';
$string['generatetextsettings_desc'] = 'Textgenerierung wird für mehrere Schritte benötigt, unter anderem für die Überarbeitung der Kursinhalte und die Erstellung der Marp-Slides. Konfigurieren Sie die Prompt-Templates in den Einstellungen des AI-Provider-Plugins: <a href="{$a->url}">aiprovider_myai / generate_text</a>.';
$string['buildmarpslidessettings'] = 'Prompt-Einstellungen für die Marp-Slide-Erstellung';
$string['buildmarpslidessettings_desc'] = 'Konfigurieren Sie hier, wie Marp-Slides aus den überarbeiteten Kursinhalten erzeugt werden.';
$string['buildmarpslidesprompttemplate'] = 'Prompt-Template für Marp-Slide-Erstellung';
$string['buildmarpslidesprompttemplate_desc'] = 'Verwenden Sie die Platzhalter {{numberofslides}}, {{marp_example}} und {{content}}. {{numberofslides}} enthält die berechnete Zielanzahl der Slides. {{marp_example}} enthält das Template aus dem Feld darunter. {{content}} enthält die überarbeiteten Kursinhalte.';
$string['buildmarpslidesprompttemplate_default'] = 'You are an expert in creating educational presentations.
Please create {{numberofslides}} MARP slides for course content, that will be provided later.
Create 1 slide for each part of the content that is marked as \'Page X:\'. Use appropriate headings, bullet points, and visuals to enhance understanding. Format the slides using MARP syntax, ensuring clarity and engagement for learners. If the content for a slide is too long, split it into multiple slides as needed. It is important that the content fits well on each slide. Please make sure the slides are well-structured and visually appealing. Add 1 slide at the beginning as start slide. Add 1 slide at the end as closing slide with source references if applicable.

Use the following MARP example as a template for the slide design and structure:
{{marp_example}}

Do not add unnecessary blank lines or spaces. Do not add any blank slides.

Course Content:
{{content}}';
$string['buildmarpslidesexample'] = 'MARP-Beispieltemplate';
$string['buildmarpslidesexample_desc'] = 'Beispieltemplate, das im Prompt als {{marp_example}} eingefügt wird.';
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
$string['generatespeakertextsettings'] = 'Prompt-Einstellungen für Sprechertext-Generierung';
$string['generatespeakertextsettings_desc'] = 'Konfigurieren Sie hier, wie aus Marp-Slides Sprechertexte erzeugt werden.';
$string['generatespeakertextprompttemplate'] = 'Prompt-Template für Sprechertext-Generierung';
$string['generatespeakertextprompttemplate_desc'] = 'Verwenden Sie den Platzhalter {{marp_slides}} für die generierten Marp-Slides.';
$string['generatespeakertextprompttemplate_default'] = "You are an expert in creating speaker texts for educational presentations.
Please generate a speaker text for each slide in the presentation, provided later as marp slides. The speaker text should complement the slide content with additional explanations, context, and insights. Do not explain logos or decorative images. Focus on the educational content of each slide. Ensure that the speaker text is clear, engaging, and aligned with the content of each slide. Format the speaker text by clearly indicating which slide it corresponds to. Mark the beginning of each slide speaker text with 'New slide:'.

Marp slides:
{{marp_slides}}";
$string['modselection'] = 'PDF-Inhalte für Inhaltsgenerierung auswählen';
$string['mail_content_generated_subject'] = 'Videoeerstellung mit KI abgeschlossen';
$string['mail_content_generated_message'] = 'Die Videoerstellung mit KI für Ihren Kurs wurde abgeschlossen. Sie finden das Video in Ihrem Bereich "Meine Dateien" in Ihrem Kurs: {$a->courselink}';
$string['mail_content_generated_messagehtml'] = '<p>Die KI-Inhaltsgenerierung für Ihren Kurs wurde abgeschlossen.</p><p>Sie finden das Video in Ihrem Bereich "Meine Dateien" in Ihrem Kurs: <a href="{$a->courselink}">Kursseite</a></p>';
$string['pathtomarp'] = 'Pfad zu Marp';
$string['pathtomarp_desc'] = 'Geben Sie den vollständigen Pfad zur Marp-Executable an. Lassen Sie dieses Feld leer, um die Standardinstallation zu verwenden.';
$string['pluginname'] = 'Generiere KI-Inhalte Platzierung';
$string['privacy:metadata'] = 'Das Plugin "Generiere KI-Inhalte Platzierung" speichert keine personenbezogenen Daten.';
$string['pathtoffmpeg'] = 'Pfad zu ffmpeg';
$string['pathtoffmpeg_desc'] = 'Geben Sie den vollständigen Pfad zur ffmpeg-Executable an. Lassen Sie dieses Feld leer, um die Standardinstallation zu verwenden.';
$string['pathtopdftoppm'] = 'Pfad zu pdftoppm';
$string['pathtopdftoppm_desc'] = 'Geben Sie den vollständigen Pfad zur pdftoppm-Executable (poppler-utils) an. Lassen Sie dieses Feld leer, um die Standardinstallation zu verwenden.';
$string['refinecontentsettings'] = 'Prompt-Einstellungen für die Überarbeitung ausgewählter Kursinhalte';
$string['refinecontentsettings_desc'] = 'Diese Einstellungen steuern, wie ausgewählte Kursinhalte vor der Foliengenerierung überarbeitet werden.';
$string['refinecontentprompttemplate'] = 'Prompt-Template für die Inhaltsüberarbeitung';
$string['refinecontentprompttemplate_desc'] = 'Verwenden Sie die Platzhalter {{additionalinstructions}}, {{content}} und {{logo_url}}. {{additionalinstructions}} enthält die zusätzlichen Hinweise, die Nutzer beim Start der Inhaltsgenerierung im Formular eingeben. {{content}} enthält die bereits zusammengeführten Inhalte aus den ausgewählten Kursmaterialien. {{logo_url}} enthält die URL des weiter unten konfigurierbaren Logos.';
$string['refinecontentprompttemplate_default'] = 'Please refine the following course content. Ensure that the content is well-organized, easy to understand, and suitable for educational slides.

Additional user instructions:
{{additionalinstructions}}

If there are placeholders for a logo, use this image URL: {{logo_url}}
Ensure that there are no footer texts or page numbers included in the content.

Course content:
{{content}}';
$string['refinecontentlogo'] = 'Logo für Prompts zur Inhaltsüberarbeitung';
$string['refinecontentlogo_desc'] = 'Optionales Logo-Bild, das im Prompt als {{logo_url}} verwendet wird. Wenn leer, wird die Standard-Logo-URL des Plugins verwendet.';
$string['mail_content_generation_failed_subject'] = 'KI-Inhaltsgenerierung fehlgeschlagen';
$string['mail_content_generation_failed_message'] = 'Die KI-Inhaltsgenerierung für Ihren Kurs ist fehlgeschlagen. Die folgenden Fehler sind aufgetreten: {$a}';
$string['mail_content_generation_failed_messagehtml'] = '<p>Die KI-Inhaltsgenerierung für Ihren Kurs ist fehlgeschlagen. Die folgenden Fehler sind aufgetreten: {$a}</p>';