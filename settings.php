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
 * Plugin administration pages are defined here.
 *
 * @package    aiplacement_contentgenerator
 * @copyright  2025 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_ai\admin\admin_settingspage_placement;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $extractpdfsettingsurl = (new moodle_url('/admin/settings.php', [
        'section' => 'aiprovider_myai_extract_pdf',
    ]))->out(false);
    $generatetextsettingsurl = (new moodle_url('/admin/settings.php', [
        'section' => 'aiprovider_myai_generate_text',
    ]))->out(false);

    $settings->add(new admin_setting_configfile(
        'aiplacement_contentgenerator/pathtomarp',
         new lang_string('pathtomarp', 'aiplacement_contentgenerator'),
         new lang_string('pathtomarp_desc', 'aiplacement_contentgenerator'),
        '',
    ));

    $settings->add(new admin_setting_configfile(
        'aiplacement_contentgenerator/pathtoffmpeg',
        new lang_string('pathtoffmpeg', 'aiplacement_contentgenerator'),
        new lang_string('pathtoffmpeg_desc', 'aiplacement_contentgenerator'),
        '/usr/bin/ffmpeg',
    ));

    $settings->add(new admin_setting_configfile(
        'aiplacement_contentgenerator/pathtopdftoppm',
        new lang_string('pathtopdftoppm', 'aiplacement_contentgenerator'),
        new lang_string('pathtopdftoppm_desc', 'aiplacement_contentgenerator'),
        '/usr/bin/pdftoppm',
    ));

    // Header for extract pdf settings with link to prompt template in aiprovider_myai settings so users can configure the prompt template
    $settings->add(new admin_setting_heading(
        'aiplacement_contentgenerator/extractpdfsettings',
        new lang_string('extractpdfsettings', 'aiplacement_contentgenerator'),
        new lang_string(
            'extractpdfsettings_desc',
            'aiplacement_contentgenerator',
            (object)['url' => $extractpdfsettingsurl]
        )
    ));

    $settings->add(new admin_setting_heading(
        'aiplacement_contentgenerator/generatetextsettings',
        new lang_string('generatetextsettings', 'aiplacement_contentgenerator'),
        new lang_string(
            'generatetextsettings_desc',
            'aiplacement_contentgenerator',
            (object)['url' => $generatetextsettingsurl]
        )
    ));

    $settings->add(new admin_setting_heading(
        'aiplacement_contentgenerator/refinecontentsettings',
        new lang_string('refinecontentsettings', 'aiplacement_contentgenerator'),
        new lang_string('refinecontentsettings_desc', 'aiplacement_contentgenerator')
    ));

    $settings->add(new admin_setting_configtextarea(
        'aiplacement_contentgenerator/refinecontentprompttemplate',
        new lang_string('refinecontentprompttemplate', 'aiplacement_contentgenerator'),
        new lang_string('refinecontentprompttemplate_desc', 'aiplacement_contentgenerator'),
        new lang_string('refinecontentprompttemplate_default', 'aiplacement_contentgenerator'),
        PARAM_RAW,
        12,
        10
    ));

    $settings->add(new admin_setting_configstoredfile(
        'aiplacement_contentgenerator/refinecontentlogo',
        new lang_string('refinecontentlogo', 'aiplacement_contentgenerator'),
        new lang_string('refinecontentlogo_desc', 'aiplacement_contentgenerator'),
        'refinecontentlogo',
        0,
        [
            'maxfiles' => 1,
            'accepted_types' => ['.png', '.jpg', '.jpeg', '.webp', '.gif'],
        ]
    ));

    $settings->add(new admin_setting_heading(
        'aiplacement_contentgenerator/buildmarpslidessettings',
        new lang_string('buildmarpslidessettings', 'aiplacement_contentgenerator'),
        new lang_string('buildmarpslidessettings_desc', 'aiplacement_contentgenerator')
    ));

    $settings->add(new admin_setting_configtextarea(
        'aiplacement_contentgenerator/buildmarpslidesprompttemplate',
        new lang_string('buildmarpslidesprompttemplate', 'aiplacement_contentgenerator'),
        new lang_string('buildmarpslidesprompttemplate_desc', 'aiplacement_contentgenerator'),
        new lang_string('buildmarpslidesprompttemplate_default', 'aiplacement_contentgenerator'),
        PARAM_RAW,
        12,
        10
    ));

    $settings->add(new admin_setting_configtextarea(
        'aiplacement_contentgenerator/buildmarpslidesexample',
        new lang_string('buildmarpslidesexample', 'aiplacement_contentgenerator'),
        new lang_string('buildmarpslidesexample_desc', 'aiplacement_contentgenerator'),
        new lang_string('buildmarpslidesexample_default', 'aiplacement_contentgenerator'),
        PARAM_RAW,
        20,
        10
    ));
}