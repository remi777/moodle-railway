<?php
// Public course landing/sales page (no login required). Renders the over30
// 'o30sales' theme layout. Enrolled/privileged users are redirected to the
// real course content; everyone else sees the editorial sales page.

require(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/course/lib.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);

// Hidden courses are not public — fall back to normal access control.
if (empty($course->visible)) {
    require_login();
    require_capability('moodle/course:viewhiddencourses', context_course::instance($course->id));
}

// Enrolled or privileged users go straight to the real course content.
$coursecontext = context_course::instance($course->id);
if (isloggedin() && !isguestuser()
        && (is_enrolled($coursecontext, null, '', true)
            || has_capability('moodle/course:update', $coursecontext))) {
    redirect(new moodle_url('/course/view.php', ['id' => $course->id]));
}

$PAGE->set_course($course);
$PAGE->set_url(new moodle_url('/local/over30catalog/course.php', ['id' => $course->id]));
$PAGE->set_pagelayout('o30sales');
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();
echo $OUTPUT->footer();
