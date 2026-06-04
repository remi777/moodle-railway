<?php
require(__DIR__ . '/../../config.php');

$slug = optional_param('slug', '', PARAM_TEXT);
$id   = optional_param('id', 0, PARAM_INT);

$repo = new \local_over30tutors\tutor_repository();
$userid = $id ?: ($slug !== '' ? $repo->resolve_slug($slug) : 0);

// Nie-tutor / brak → redirect do katalogu.
if (!$userid || !$repo->is_tutor($userid)) {
    redirect(new moodle_url('/local/over30tutors/index.php'));
}

$user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

$PAGE->set_url(new moodle_url('/local/over30tutors/tutor.php', ['id' => $userid]));
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout('standard');

$fields = $repo->get_profile_fields($userid);
$title = fullname($user) . ($fields['tutor_tagline'] !== '' ? ' — ' . $fields['tutor_tagline'] : '');
$PAGE->set_title($title);
$PAGE->set_heading(fullname($user));

// Widz widzi ukryte kursy tylko jeśli to jego strona albo admin.
$owner = isloggedin() && (($USER->id == $userid) || is_siteadmin());
$page = new \local_over30tutors\output\tutor_page($userid, $owner);

// SEO: meta description, canonical, OpenGraph — wstrzyknięte w <head>
// przez additionalhtmlhead (bezpieczne na wszystkich wersjach Moodle).
$desc = $fields['tutor_tagline'] !== '' ? $fields['tutor_tagline']
        : shorten_text(html_to_text($fields['tutor_bio']), 160);
$slugforurl = $fields['tutor_slug'] !== '' ? $fields['tutor_slug'] : $user->username;
$canonical = (new moodle_url('/tutor/' . $slugforurl))->out(false);
$ogpic = new user_picture($user);
$ogpic->size = 200;
$ogimage = $ogpic->get_url($PAGE)->out(false);
if (!isset($CFG->additionalhtmlhead)) {
    $CFG->additionalhtmlhead = '';
}
$CFG->additionalhtmlhead .=
    '<meta name="description" content="' . s($desc) . '">' .
    '<link rel="canonical" href="' . s($canonical) . '">' .
    '<meta property="og:type" content="profile">' .
    '<meta property="og:title" content="' . s($title) . '">' .
    '<meta property="og:description" content="' . s($desc) . '">' .
    '<meta property="og:image" content="' . s($ogimage) . '">';

echo $OUTPUT->header();
echo $OUTPUT->render_from_template('local_over30tutors/tutor_page',
    $page->export_for_template($OUTPUT));
echo $OUTPUT->footer();
