<?php
defined('MOODLE_INTERNAL') || die();
function theme_over30_get_main_scss_content($theme) {
    global $CFG;
    $boost = $CFG->dirroot . '/theme/boost/scss/preset/default.scss';
    return file_exists($boost) ? file_get_contents($boost) : '';
}
function theme_over30_get_pre_scss($theme) {
    $pre = file_get_contents(__DIR__ . '/scss/pre.scss');
    if (!empty($theme->settings->brandcolor)) { $pre .= "\n\$primary: {$theme->settings->brandcolor};\n"; }
    return $pre;
}
function theme_over30_get_extra_scss($theme) {
    $extra = file_get_contents(__DIR__ . '/scss/post.scss');
    if (!empty($theme->settings->rawscss)) { $extra .= "\n/* raw */\n" . $theme->settings->rawscss; }
    return $extra;
}
