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

/**
 * Class placement.
 *
 * @package    aiplacement_contentgenerator
 * @copyright  2025 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class placement extends \core_ai\placement {
    /**
     * Get the list of actions that this placement uses.
     *
     * @return array An array of action class names.
     */
    public function get_action_list(): array {
        return [
            \aiprovider_myai\aiactions\extract_pdf::class,
        ];
    }

    /**
     * Führt eine AI-Action 'extract_pdf' aus.
     *
     * @param int $fileid Moodle file id (z. B. aus mdl_files)
     * @return array The extracted text content from the PDF.
     */
    public static function process_pdf(string $imagebase64, string $additionalinstructions = ''): array {
        global $USER;
        
        // Prepare the action.
        $action = new \aiprovider_myai\aiactions\extract_pdf(
            userid: $USER->id,
            imagebase64: $imagebase64,
            prompttext: $additionalinstructions,
            contextid: \context_system::instance()->id,
        );

        // Todo check user permission

        // Send the action to the AI manager.
        $manager = \core\di::get(\core_ai\manager::class);
        $response = $manager->process_action($action);
        // Return the response.
        return [
            'success' => $response->get_success(),
            'generatedcontent' => $response->get_response_data()['generatedcontent'] ?? '',
            'finishreason' => $response->get_response_data()['finishreason'] ?? '',
            'errorcode' => $response->get_errorcode(),
            'error' => $response->get_errormessage(),
            'timecreated' => $response->get_timecreated(),
            'prompttext' => $additionalinstructions,
        ];
    }
}
