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
 * The over30 front page layout: a faithful reproduction of the mockup HomeView.
 *
 * Built on Boost's drawers layout so all the required head / footer / navbar
 * wiring is preserved, with the editorial over30 sections injected above the
 * standard managed front-page content ({{{ maincontent }}}).
 *
 * @package   theme_over30
 * @copyright 2026 over30
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

// Add block button in editing mode.
$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$extraclasses = ['uses-drawers'];
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $selectmenu = new \core\output\select_menu(
            'tertiarynavigation',
            $overflowdata->urls,
            $overflowdata->selected,
        );
        $selectmenu->set_label($overflowdata->label, $overflowdata->labelattributes);
        $overflow = $selectmenu->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
// If the settings menu will be included in the header then don't add it here.
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$coursefullname = ($PAGE->course?->fullname) ? format_string(
    $PAGE->course->fullname,
    true,
    ['context' => context_course::instance($PAGE->course->id), 'escape' => false],
) : '';
$courseurl = $PAGE->course ? new \core\url('/course/view.php', ['id' => $PAGE->course->id]) : null;

// over30 editorial section data ------------------------------------------------.

// Authored programmes (dark "Programy Autorskie" grid) — REAL visible courses
// from the database. Falls back to bundled portrait images when a course has no
// image of its own.
$fallbackimgs = [
    $OUTPUT->image_url('course-1', 'theme_over30')->out(),
    $OUTPUT->image_url('course-2', 'theme_over30')->out(),
    $OUTPUT->image_url('course-3', 'theme_over30')->out(),
    $OUTPUT->image_url('course-4', 'theme_over30')->out(),
];
$courses = [];
try {
    $i = 0;
    foreach (get_courses('all', 'c.sortorder ASC', 'c.id, c.fullname, c.category, c.visible') as $c) {
        if ($c->id == SITEID || empty($c->visible)) {
            continue;
        }
        $catname = '';
        try {
            $cat = core_course_category::get($c->category, IGNORE_MISSING, true);
            $catname = $cat ? $cat->get_formatted_name() : '';
        } catch (Throwable $e) {
            $catname = '';
        }
        $img = '';
        try {
            $fullcourse = get_course($c->id);
            $img = \core_course\external\course_summary_exporter::get_course_image($fullcourse) ?: '';
        } catch (Throwable $e) {
            $img = '';
        }
        if (!$img) {
            $img = $fallbackimgs[$i % count($fallbackimgs)];
        }
        $courses[] = [
            'cat' => core_text::strtoupper($catname),
            'title' => format_string($c->fullname),
            'instructor' => '',
            'img' => $img,
            'url' => (new \core\url('/course/view.php', ['id' => $c->id]))->out(false),
        ];
        $i++;
        if ($i >= 8) {
            break;
        }
    }
} catch (Throwable $e) {
    $courses = [];
}
$hascourses = !empty($courses);

$features = [
    ['number' => '01', 'title' => 'Wiedza Ekspercka', 'desc' => 'Wyselekcjonowane programy tworzone przez praktyków. Bez lania wody, same konkrety.'],
    ['number' => '02', 'title' => 'Elitarna Sieć', 'desc' => 'Dostęp do zamkniętej społeczności, w której wymiana doświadczeń napędza realny wzrost.'],
    ['number' => '03', 'title' => 'Elegancja Formy', 'desc' => 'Nauka w otoczeniu, które inspiruje. Nasza platforma to dzieło sztuki użytkowej.'],
    ['number' => '04', 'title' => 'Zauważalne Efekty', 'desc' => 'Nie sprzedajemy certyfikatów. Dostarczamy transformację, którą widać w Twoim życiu.'],
];

$audience = [
    ['text' => 'Stoisz przed strategicznym pivotem w karierze i szukasz pewnego planu.'],
    ['text' => 'Budujesz biznes i potrzebujesz twardej wiedzy z zakresu przywództwa.'],
    ['text' => 'Chcesz odzyskać kontrolę nad swoimi finansami i budować majątek.'],
    ['text' => 'Oczekujesz najwyższej jakości w designie, treści i kontaktach.'],
];

$testimonials = [
    ['initial' => 'M', 'name' => 'Magdalena K.', 'role' => 'Marketing Director', 'quote' => 'Jakość tych materiałów to zupełnie inna liga. To nie są kolejne webinary, to strategiczne sesje, które realnie wpłynęły na moją pozycję w zarządzie.'],
    ['initial' => 'P', 'name' => 'Piotr M.', 'role' => 'Founder & CEO', 'quote' => 'Społeczność jest niesamowita. Kontakt z ludźmi na podobnym poziomie, wymiana doświadczeń. To miejsce ma duszę i klasę.'],
    ['initial' => 'J', 'name' => 'Joanna W.', 'role' => 'Senior Architect', 'quote' => 'Zmieniałam branżę po 10 latach. over30 dało mi nie tylko wiedzę, ale przede wszystkim mentalność zwycięzcy w nowym otoczeniu.'],
];

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), "escape" => false]),
    'coursefullname' => $coursefullname,
    'courseurl' => $courseurl ? $courseurl->out(false) : null,
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
    // over30 editorial front page data.
    'o30' => [
        'hero' => $OUTPUT->image_url('hero', 'theme_over30')->out(),
        'desk' => $OUTPUT->image_url('desk', 'theme_over30')->out(),
        'cta' => $OUTPUT->image_url('course-cta', 'theme_over30')->out(),
        'logo' => $OUTPUT->image_url('logo', 'theme_over30')->out(),
        'logolight' => $OUTPUT->image_url('logo-light', 'theme_over30')->out(),
        'courses' => $courses,
        'hascourses' => $hascourses,
        'features' => $features,
        'audience' => $audience,
        'testimonials' => $testimonials,
        'year' => date('Y'),
        // Auth-aware navigation.
        'loggedin' => (isloggedin() && !isguestuser()),
        'userfirstname' => (isloggedin() && !isguestuser()) ? format_string($USER->firstname) : '',
        'dashboardurl' => (new \core\url('/my/'))->out(false),
        'courselisturl' => (new \core\url('/course/'))->out(false),
        'loginurl' => (new \core\url('/login/index.php'))->out(false),
        'logouturl' => (new \core\url('/login/logout.php', ['sesskey' => sesskey()]))->out(false),
    ],
];

echo $OUTPUT->render_from_template('theme_over30/frontpage', $templatecontext);
