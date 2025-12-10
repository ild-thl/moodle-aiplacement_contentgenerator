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
$string['modselection'] = 'PDF-Inhalte für Inhaltsgenerierung auswählen';
$string['mail_content_generated_subject'] = 'Videoeerstellung mit KI abgeschlossen';
$string['mail_content_generated_message'] = 'Die Videoerstellung mit KI für Ihren Kurs wurde abgeschlossen. Sie finden das Video in Ihrem Bereich "Meine Dateien" in Ihrem Kurs: {$a->courselink}';
$string['mail_content_generated_messagehtml'] = '<p>Die KI-Inhaltsgenerierung für Ihren Kurs wurde abgeschlossen.</p><p>Sie können die generierten Inhalte anzeigen, indem Sie die Kursseite besuchen: <a href="{$a->courselink}">Kursseite</a></p>';
$string['pathtomarp'] = 'Pfad zu Marp';
$string['pathtomarp_desc'] = 'Geben Sie den vollständigen Pfad zur Marp-Executable an. Lassen Sie dieses Feld leer, um die Standardinstallation zu verwenden.';
$string['pluginname'] = 'Generiere KI-Inhalte Platzierung';
$string['privacy:metadata'] = 'Das Plugin "Generiere KI-Inhalte Platzierung" speichert keine personenbezogenen Daten.';
