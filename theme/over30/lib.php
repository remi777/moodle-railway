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

/**
 * Inject the over30 brand web fonts into every page head.
 * Loaded here (not via @import url() in SCSS) because scssphp tries to resolve
 * @import url(...) as a local file and fails the whole theme compilation.
 */
function theme_over30_before_standard_html_head() {
    global $OUTPUT;
    $head = '<link rel="preconnect" href="https://fonts.googleapis.com">'
        . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
        . '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?'
        . 'family=Cormorant+Garamond:ital,wght@0,300;0,400;0,500;0,600;1,300;1,400;1,500'
        . '&family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap">';
    // over30 favicons (prefer SVG, with PNG fallbacks + apple-touch icon).
    try {
        $svg = $OUTPUT->image_url('favicon', 'theme_over30')->out(false);
        $p32 = $OUTPUT->image_url('favicon-32', 'theme_over30')->out(false);
        $p180 = $OUTPUT->image_url('favicon-180', 'theme_over30')->out(false);
        $p192 = $OUTPUT->image_url('favicon-192', 'theme_over30')->out(false);
        $head .= '<link rel="icon" type="image/svg+xml" href="' . $svg . '">'
            . '<link rel="icon" type="image/png" sizes="32x32" href="' . $p32 . '">'
            . '<link rel="icon" type="image/png" sizes="192x192" href="' . $p192 . '">'
            . '<link rel="apple-touch-icon" sizes="180x180" href="' . $p180 . '">';
    } catch (Throwable $e) {
        // If image URLs can't be built this early, skip favicons (fonts still load).
    }
    return $head;
}
