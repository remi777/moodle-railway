<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/course/lib.php');

$bodyattributes = $OUTPUT->body_attributes(['o30-catalog-layout']);

// Selected category from the URL (?categoryid=N); 0 = all.
$selectedcat = optional_param('categoryid', 0, PARAM_INT);

// Top-level category tree for the sidebar.
$cats = [];
try {
    foreach (\core_course_category::top()->get_children() as $child) {
        if (!$child->is_uservisible()) {
            continue;
        }
        if ($child->get_courses_count() < 1) {
            continue;
        }
        $cats[] = [
            'id' => $child->id,
            'name' => $child->get_formatted_name(),
            'count' => $child->get_courses_count(),
            'active' => ((int)$selectedcat === (int)$child->id),
            'url' => (new \core\url('/course/index.php', ['categoryid' => $child->id]))->out(false),
        ];
    }
} catch (\Throwable $e) {
    $cats = [];
}

$cards = theme_over30_course_cards($selectedcat, 0);

// Course cards link to the public sales/detail page (no login wall) rather than
// the gated /course/view.php.
foreach ($cards as &$card) {
    $card['url'] = (new \core\url('/local/over30catalog/course.php', ['id' => $card['id']]))->out(false);
}
unset($card);

// Primary nav export — provides the user menu / login control to the o30nav partial.
$primary = new core\navigation\output\primary($PAGE);
$primarymenu = $primary->export_for_template($PAGE->get_renderer('core'));

$templatecontext = [
    'output' => $OUTPUT,
    'bodyattributes' => $bodyattributes,
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), 'escape' => false]),
    'o30nav' => theme_over30_nav_context($OUTPUT),
    'usermenu' => $primarymenu['user'],
    'allactive' => ($selectedcat === 0),
    'allurl' => (new \core\url('/course/index.php'))->out(false),
    'categories' => $cats,
    'courses' => $cards,
    'hascourses' => !empty($cards),
];

echo $OUTPUT->render_from_template('theme_over30/catalog', $templatecontext);
