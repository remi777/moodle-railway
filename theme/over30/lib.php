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
 * Build the global left-sidebar context (logged-in app navigation).
 *
 * Items: Kokpit, Moje kursy, Kalendarz, Katalog + footer (Profil, Wyloguj).
 * Active item is derived from the current page URL/pagetype. Inside a course
 * the sidebar renders as a narrow icon rail (rail=true) so it coexists with
 * Boost's course-index drawer.
 *
 * @param moodle_page $page the current page
 * @return array context for theme_over30/o30sidebar (loggedin=false hides it)
 */
function theme_over30_sidebar_context($page) {
    global $USER, $CFG, $OUTPUT;
    if (!isloggedin() || isguestuser() || $page->pagetype === 'site-index') {
        return ['loggedin' => false];
    }
    $wwwroot = rtrim($CFG->wwwroot, '/');
    $pagetype = (string)$page->pagetype;     // e.g. 'my-index', 'course-view-topics'
    $incourse = !empty($page->course) && (int)$page->course->id !== (int)SITEID;

    // Active detection by pagetype prefix.
    $is = function($prefix) use ($pagetype) {
        return strpos($pagetype, $prefix) === 0;
    };
    $mkicon = function($name) {
        return [
            'icon_grid'     => $name === 'grid',
            'icon_book'     => $name === 'book',
            'icon_calendar' => $name === 'calendar',
            'icon_search'   => $name === 'search',
        ];
    };
    $items = [
        ['label' => 'Kokpit',     'url' => $wwwroot . '/my/'] + $mkicon('grid')     + ['active' => $pagetype === 'my-index'],
        ['label' => 'Moje kursy', 'url' => $wwwroot . '/my/courses.php'] + $mkicon('book') + ['active' => $is('course-view') || $is('my-courses')],
        ['label' => 'Kalendarz',  'url' => $wwwroot . '/calendar/view.php'] + $mkicon('calendar') + ['active' => $is('calendar-')],
        ['label' => 'Katalog',    'url' => $wwwroot . '/course/'] + $mkicon('search') + ['active' => $is('course-index') || $is('course-category')],
    ];

    $userpic = $OUTPUT->user_picture($USER, ['size' => 36, 'link' => false, 'class' => 'o30-sidebar__avatar']);

    return [
        'loggedin'    => true,
        'rail'        => $incourse,
        'items'       => $items,
        'userfullname' => fullname($USER),
        'userpicture' => $userpic,
        'profileurl'  => (new \core\url('/user/profile.php', ['id' => $USER->id]))->out(false),
        'logouturl'   => (new \core\url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
        'homeurl'     => $wwwroot . '/',
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
                'id' => $c->id,
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
 * Build catalog teaser cards for the dashboard: visible courses the current
 * user is NOT enrolled in, with optional price (course custom field 'price').
 *
 * @param int $limit max cards
 * @return array list of ['cat','title','img','url','price','hasprice']
 */
function theme_over30_dashboard_catalog_cards($limit = 4) {
    global $USER, $DB, $OUTPUT;
    // Courses the user is enrolled in (exclusion set).
    $enrolled = [];
    foreach (enrol_get_users_courses($USER->id, true, 'id') as $c) {
        $enrolled[(int)$c->id] = true;
    }
    // Price values by course id (direct join; handler returns NULL on this build).
    $prices = [];
    try {
        $sql = "SELECT d.instanceid, d.value
                  FROM {customfield_data} d
                  JOIN {customfield_field} f ON f.id = d.fieldid
                  JOIN {customfield_category} c ON c.id = f.categoryid
                 WHERE f.shortname = 'price'
                   AND c.component = 'core_course' AND c.area = 'course'";
        foreach ($DB->get_records_sql($sql) as $r) {
            $prices[(int)$r->instanceid] = trim((string)$r->value);
        }
    } catch (\Throwable $e) {
        $prices = [];
    }

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
            if ($c->id == SITEID || empty($c->visible) || !empty($enrolled[(int)$c->id])) {
                continue;
            }
            $catname = '';
            try {
                $cat = core_course_category::get($c->category, IGNORE_MISSING, true);
                $catname = $cat ? $cat->get_formatted_name() : '';
            } catch (\Throwable $e) { $catname = ''; }
            $img = '';
            try {
                $img = \core_course\external\course_summary_exporter::get_course_image(get_course($c->id)) ?: '';
            } catch (\Throwable $e) { $img = ''; }
            if (!$img) { $img = $fallback[$i % count($fallback)]; }
            $price = isset($prices[(int)$c->id]) ? $prices[(int)$c->id] : '';
            $cards[] = [
                'cat' => core_text::strtoupper($catname),
                'title' => format_string($c->fullname),
                'img' => $img,
                'url' => (new \core\url('/course/view.php', ['id' => $c->id]))->out(false),
                'price' => $price,
                'hasprice' => $price !== '',
            ];
            $i++;
            if ($limit && $i >= $limit) { break; }
        }
    } catch (\Throwable $e) { $cards = []; }
    return $cards;
}

/**
 * Render the dashboard's calendar (mini month) and timeline native blocks to
 * HTML, deterministically (independent of per-user block placement).
 * Signatures verified by a live spike on this Moodle 5.2 build.
 *
 * @param moodle_page $page
 * @return array ['calendarhtml'=>string, 'timelinehtml'=>string]
 */
function theme_over30_dashboard_blocks($page) {
    global $CFG;
    $calendarhtml = '';
    $timelinehtml = '';
    try {
        $renderable = new \block_timeline\output\main(0, 'sortbydates', false, 0);
        $timelinehtml = $page->get_renderer('block_timeline')->render($renderable);
    } catch (\Throwable $e) {
        $timelinehtml = '';
    }
    try {
        require_once($CFG->dirroot . '/calendar/lib.php');
        $calendar = \calendar_information::create(time(), SITEID, null);
        list($data, $template) = calendar_get_view($calendar, 'mini');
        $calendarhtml = $page->get_renderer('core_calendar')->render_from_template($template, $data);
    } catch (\Throwable $e) {
        $calendarhtml = '';
    }
    return ['calendarhtml' => $calendarhtml, 'timelinehtml' => $timelinehtml];
}

/**
 * Build "Moje kursy" cards: courses the current user is enrolled in, with
 * completion progress. Rendered directly (deterministic) so the dashboard does
 * not depend on the per-user My-page block layout.
 *
 * @return array list of ['title','url','img','cat','hasprogress','progress']
 */
function theme_over30_my_courses_cards() {
    global $USER, $OUTPUT, $CFG;
    require_once($CFG->libdir . '/completionlib.php');
    $fallback = [
        $OUTPUT->image_url('course-1', 'theme_over30')->out(),
        $OUTPUT->image_url('course-2', 'theme_over30')->out(),
        $OUTPUT->image_url('course-3', 'theme_over30')->out(),
        $OUTPUT->image_url('course-4', 'theme_over30')->out(),
    ];
    $cards = [];
    $i = 0;
    try {
        $courses = enrol_get_users_courses($USER->id, true, ['id', 'fullname', 'category', 'visible']);
        foreach ($courses as $c) {
            if ($c->id == SITEID || empty($c->visible)) {
                continue;
            }
            $course = get_course($c->id);
            $hasprogress = false;
            $progress = 0;
            try {
                $completion = new \completion_info($course);
                if ($completion->is_enabled()) {
                    $pct = \core_completion\progress::get_course_progress_percentage($course, $USER->id);
                    if ($pct !== null) {
                        $hasprogress = true;
                        $progress = (int) round($pct);
                    }
                }
            } catch (\Throwable $e) {
                $hasprogress = false;
            }
            $catname = '';
            try {
                $cat = core_course_category::get($course->category, IGNORE_MISSING, true);
                $catname = $cat ? $cat->get_formatted_name() : '';
            } catch (\Throwable $e) {
                $catname = '';
            }
            $img = '';
            try {
                $img = \core_course\external\course_summary_exporter::get_course_image($course) ?: '';
            } catch (\Throwable $e) {
                $img = '';
            }
            if (!$img) {
                $img = $fallback[$i % count($fallback)];
            }
            $cards[] = [
                'title' => format_string($course->fullname),
                'url' => (new \core\url('/course/view.php', ['id' => $course->id]))->out(false),
                'img' => $img,
                'cat' => core_text::strtoupper($catname),
                'hasprogress' => $hasprogress,
                'progress' => $progress,
            ];
            $i++;
        }
    } catch (\Throwable $e) {
        $cards = [];
    }
    return $cards;
}

/**
 * Build the "Program Kursu" accordion data (sections -> lessons) for preview.
 * Names only; no links/content (safe to show to anonymous visitors).
 *
 * @param stdClass $course
 * @return array list of ['module'=>string,'lessons'=>[['name'=>..]],'haslessons'=>bool]
 */
function theme_over30_course_program($course) {
    $out = [];
    try {
        $modinfo = get_fast_modinfo($course);
        foreach ($modinfo->get_section_info_all() as $section) {
            if ($section->section == 0 && trim((string)$section->name) === '') {
                continue;
            }
            $lessons = [];
            if (!empty($modinfo->sections[$section->section])) {
                foreach ($modinfo->sections[$section->section] as $cmid) {
                    $cm = $modinfo->cms[$cmid];
                    if ($cm->deletioninprogress) {
                        continue;
                    }
                    if (!$cm->visible && !$cm->visibleoncoursepage) {
                        continue;
                    }
                    $lessons[] = ['name' => format_string($cm->name)];
                }
            }
            $title = $section->name ? format_string($section->name)
                : get_string('section') . ' ' . $section->section;
            $out[] = ['module' => $title, 'lessons' => $lessons, 'haslessons' => !empty($lessons)];
        }
    } catch (\Throwable $e) {
        $out = [];
    }
    return $out;
}

/**
 * Read over30 course metadata custom fields (duration/level/audience/certificate).
 * @param stdClass $course
 * @return array list of ['label'=>..,'value'=>..] for fields that have a value.
 */
function theme_over30_course_meta($course) {
    global $DB;
    $labels = ['price' => 'Cena', 'duration' => 'Czas trwania', 'level' => 'Poziom', 'audience' => 'Dla kogo', 'certificate' => 'Certyfikat'];
    $rows = [];
    try {
        // Read the stored values directly. The customfield handler's
        // get_instance_data() returned NULL values here (the controllers don't
        // bind the saved rows in this build), but the data is cleanly stored, so
        // a direct join is the reliable source.
        $sql = "SELECT f.shortname, d.value
                  FROM {customfield_data} d
                  JOIN {customfield_field} f ON f.id = d.fieldid
                  JOIN {customfield_category} c ON c.id = f.categoryid
                 WHERE d.instanceid = :courseid
                   AND c.component = 'core_course' AND c.area = 'course'";
        $byshort = [];
        foreach ($DB->get_records_sql($sql, ['courseid' => $course->id]) as $r) {
            $byshort[$r->shortname] = $r->value;
        }
        foreach ($labels as $short => $label) {
            $val = isset($byshort[$short]) ? trim((string)$byshort[$short]) : '';
            if ($val !== '') {
                $rows[] = ['label' => $label, 'value' => format_string($val)];
            }
        }
    } catch (\Throwable $e) {
        $rows = [];
    }
    return $rows;
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
