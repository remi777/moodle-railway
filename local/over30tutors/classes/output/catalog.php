<?php
namespace local_over30tutors\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use local_over30tutors\tutor_repository;

class catalog implements renderable, templatable {

    /** @var string|null filtr tagu */
    private $tag;
    /** @var tutor_repository */
    private $repo;

    public function __construct(?string $tag, ?tutor_repository $repo = null) {
        $this->tag = ($tag !== null && trim($tag) !== '') ? trim($tag) : null;
        $this->repo = $repo ?? new tutor_repository();
    }

    public function export_for_template(renderer_base $output): \stdClass {
        global $DB;
        $userids = $this->tag !== null
            ? $this->repo->get_tutors_by_tag($this->tag)
            : $this->repo->get_tutor_userids();

        $data = new \stdClass();
        $data->title = get_string('catalogtitle', 'local_over30tutors');
        $data->tag = $this->tag;
        $data->hastag = $this->tag !== null;
        $data->clearurl = (new \moodle_url('/local/over30tutors/index.php'))->out(false);
        $data->tutors = [];

        foreach ($userids as $uid) {
            $user = $DB->get_record('user', ['id' => $uid, 'deleted' => 0]);
            if (!$user) {
                continue;
            }
            $fields = $this->repo->get_profile_fields($uid);
            $picture = new \user_picture($user);
            $picture->size = 100;

            $o = new \stdClass();
            $o->fullname = fullname($user);
            $o->tagline = $fields['tutor_tagline'];
            $o->pictureurl = $picture->get_url($output->get_page())->out(false);
            $slug = $fields['tutor_slug'] !== '' ? $fields['tutor_slug'] : $user->username;
            $o->url = (new \moodle_url('/local/over30tutors/tutor.php', ['slug' => $slug]))->out(false);
            $o->tags = array_values($this->repo->get_tutor_tags($uid));
            $data->tutors[] = $o;
        }

        // Stabilna kolejność alfabetyczna po nazwisku.
        usort($data->tutors, fn($a, $b) => strcoll($a->fullname, $b->fullname));
        $data->hastutors = !empty($data->tutors);
        return $data;
    }
}
