<?php
defined('MOODLE_INTERNAL') || die();

/**
 * Dodaje „Moja strona tutora" do menu użytkownika, jeśli jest tutorem.
 *
 * @param navigation_node $parentnode
 * @param stdClass $user
 * @param context_user $context
 * @param stdClass $course
 * @param context_course $coursecontext
 */
function local_over30tutors_extend_navigation_user_settings($parentnode, $user, $context, $course, $coursecontext) {
    global $USER;
    if (empty($USER->id) || $USER->id != $user->id) {
        return;
    }
    $repo = new \local_over30tutors\tutor_repository();
    if (!$repo->is_tutor((int)$USER->id)) {
        return;
    }
    $url = new moodle_url('/local/over30tutors/tutor.php', ['id' => $USER->id]);
    $parentnode->add(
        get_string('mytutorpage', 'local_over30tutors'),
        $url,
        navigation_node::TYPE_SETTING,
        null,
        'local_over30tutors_mypage'
    );
}
