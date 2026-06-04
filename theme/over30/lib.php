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
 * Build the shared over30 navigation context (logo + auth-aware links).
 * Used by the front page, dashboard and course layouts so the top menu is
 * identical everywhere.
 *
 * @param renderer_base $output the page renderer (for image_url)
 * @return array
 */
function theme_over30_nav_context($output) {
    global $USER;
    $loggedin = (isloggedin() && !isguestuser());
    return [
        'logo' => $output->image_url('logo', 'theme_over30')->out(false),
        'loggedin' => $loggedin,
        'userfirstname' => $loggedin ? format_string($USER->firstname) : '',
        'homeurl' => (new \core\url('/'))->out(false),
        'courselisturl' => (new \core\url('/course/'))->out(false),
        'dashboardurl' => (new \core\url('/my/'))->out(false),
        'loginurl' => (new \core\url('/login/index.php'))->out(false),
        'logouturl' => (new \core\url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
    ];
}

/**
 * Build over30 course-card contexts for a set of visible courses.
 *
 * @param int $categoryid 0 = all categories, else only courses in this category.
 * @param int $limit max number of cards (0 = no limit).
 * @return array list of ['cat','title','img','url'] ready for .o30-card templates.
 */
function theme_over30_course_cards($categoryid = 0, $limit = 0) {
    global $OUTPUT;
    $fallback = [
        $OUTPUT->image_url('course-1', 'theme_over30')->out(),
        $OUTPUT->image_url('course-2', 'theme_over30')->out(),
        $OUTPUT->image_url('course-3', 'theme_over30')->out(),
        $OUTPUT->image_url('course-4', 'theme_over30')->out(),
    ];
    $cards = [];
    $i = 0;
    try {
        foreach (get_courses('all', 'c.sortorder ASC', 'c.id, c.fullname, c.category, c.visible') as $c) {
            if ($c->id == SITEID || empty($c->visible)) {
                continue;
            }
            if ($categoryid && (int)$c->category !== (int)$categoryid) {
                continue;
            }
            $catname = '';
            try {
                $cat = core_course_category::get($c->category, IGNORE_MISSING, true);
                $catname = $cat ? $cat->get_formatted_name() : '';
            } catch (\Throwable $e) {
                $catname = '';
            }
            $img = '';
            try {
                $img = \core_course\external\course_summary_exporter::get_course_image(get_course($c->id)) ?: '';
            } catch (\Throwable $e) {
                $img = '';
            }
            if (!$img) {
                $img = $fallback[$i % count($fallback)];
            }
            $cards[] = [
                'cat' => core_text::strtoupper($catname),
                'title' => format_string($c->fullname),
                'img' => $img,
                'url' => (new \core\url('/course/view.php', ['id' => $c->id]))->out(false),
            ];
            $i++;
            if ($limit && $i >= $limit) {
                break;
            }
        }
    } catch (\Throwable $e) {
        $cards = [];
    }
    return $cards;
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
    // The "Administracja" nav link is in the markup for everyone but hidden by
    // default; reveal it only for site admins (works on every page/layout).
    if (is_siteadmin()) {
        $head .= '<style>.o30-nav__admin{display:inline-block !important;}</style>';
    }
    return $head;
}
