<?php
defined('MOODLE_INTERNAL') || die();
if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs('themesettingover30', get_string('configtitle', 'theme_over30'));
    $page = new admin_settingpage('theme_over30_general', get_string('generalsettings', 'theme_over30'));
    $page->add(new admin_setting_configcolourpicker('theme_over30/brandcolor', get_string('brandcolor', 'theme_over30'), '', '#b35041'));
    $page->add(new admin_setting_configtextarea('theme_over30/rawscss', get_string('rawscss', 'theme_over30'), get_string('rawscss_desc', 'theme_over30'), '', PARAM_RAW));
    $page->add(new admin_setting_configstoredfile('theme_over30/logo', get_string('logo', 'theme_over30'), '', 'logo', 0, ['accepted_types' => ['.png', '.svg', '.jpg']]));
    $settings->add($page);
}
