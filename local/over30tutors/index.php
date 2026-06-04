<?php
require(__DIR__ . '/../../config.php');

$tag = optional_param('tag', null, PARAM_TEXT);

$PAGE->set_url(new moodle_url('/local/over30tutors/index.php', $tag ? ['tag' => $tag] : []));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');
$title = get_string('catalogtitle', 'local_over30tutors');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$catalog = new \local_over30tutors\output\catalog($tag);

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_over30tutors/catalog',
    $catalog->export_for_template($OUTPUT));
echo $OUTPUT->footer();
