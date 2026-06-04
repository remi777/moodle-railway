<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/lib.php');

$course = $PAGE->course;
$bodyattributes = $OUTPUT->body_attributes(['o30-sales-layout']);

// Banner image (prefer the real course image, fall back to bundled hero).
$courseimage = '';
try {
    if (class_exists('\core_course\external\course_summary_exporter')) {
        $courseimage = \core_course\external\course_summary_exporter::get_course_image($course);
    }
} catch (\Throwable $e) {
    $courseimage = '';
}
if (empty($courseimage)) {
    $courseimage = $OUTPUT->image_url('hero', 'theme_over30')->out();
}

// Category (best-effort).
$categoryname = '';
try {
    if (!empty($course->category)) {
        $cat = \core_course_category::get($course->category, IGNORE_MISSING, true);
        $categoryname = $cat ? $cat->get_formatted_name() : '';
    }
} catch (\Throwable $e) {
    $categoryname = '';
}

// Summary (formatted).
$summary = '';
try {
    $summary = format_text($course->summary ?? '', $course->summaryformat ?? FORMAT_HTML,
        ['context' => context_course::instance($course->id)]);
} catch (\Throwable $e) {
    $summary = '';
}

$meta = theme_over30_course_meta($course);

// User menu for the shared o30nav partial.
$primary = new core\navigation\output\primary($PAGE);
$primarymenu = $primary->export_for_template($PAGE->get_renderer('core'));

$templatecontext = [
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), 'escape' => false]),
    'o30nav' => theme_over30_nav_context($OUTPUT),
    'usermenu' => $primarymenu['user'],
    'image' => $courseimage,
    'category' => $categoryname,
    'hascategory' => ($categoryname !== ''),
    'title' => format_string($course->fullname),
    'summary' => $summary,
    'program' => theme_over30_course_program($course),
    'meta' => $meta,
    'hasmeta' => !empty($meta),
    'buyurl' => (new \core\url('/login/index.php'))->out(false),
];

echo $OUTPUT->render_from_template('theme_over30/sales', $templatecontext);
