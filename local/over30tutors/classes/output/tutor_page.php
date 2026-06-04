<?php
namespace local_over30tutors\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;
use local_over30tutors\tutor_repository;

class tutor_page implements renderable, templatable {

    /** @var int */
    private $userid;
    /** @var bool czy widz to właściciel/admin (widzi ukryte kursy) */
    private $owner;
    /** @var tutor_repository */
    private $repo;

    public function __construct(int $userid, bool $owner, ?tutor_repository $repo = null) {
        $this->userid = $userid;
        $this->owner = $owner;
        $this->repo = $repo ?? new tutor_repository();
    }

    public function export_for_template(renderer_base $output): \stdClass {
        global $DB;
        $user = $DB->get_record('user', ['id' => $this->userid, 'deleted' => 0], '*', MUST_EXIST);
        $fields = $this->repo->get_profile_fields($this->userid);

        $picture = new \user_picture($user);
        $picture->size = 200;

        $data = new \stdClass();
        $data->userid = (int)$user->id;
        $data->fullname = fullname($user);
        $data->tagline = $fields['tutor_tagline'];
        $data->bio = format_text($fields['tutor_bio'], FORMAT_HTML);
        $data->pictureurl = $picture->get_url($output->get_page())->out(false);

        // Linki social/www — tylko wypełnione.
        $linkmap = [
            'tutor_web' => 'Web', 'tutor_linkedin' => 'LinkedIn',
            'tutor_instagram' => 'Instagram', 'tutor_youtube' => 'YouTube',
        ];
        $data->links = [];
        foreach ($linkmap as $sn => $label) {
            if (!empty($fields[$sn])) {
                $o = new \stdClass();
                $o->label = $label;
                $o->url = $fields[$sn];
                $data->links[] = $o;
            }
        }
        $data->haslinks = !empty($data->links);

        // Tagi.
        $data->tags = [];
        foreach ($this->repo->get_tutor_tags($this->userid) as $t) {
            $o = new \stdClass();
            $o->name = $t;
            $o->url = (new \moodle_url('/local/over30tutors/index.php', ['tag' => $t]))->out(false);
            $data->tags[] = $o;
        }
        $data->hastags = !empty($data->tags);

        // Kursy.
        $data->courses = [];
        foreach ($this->repo->get_tutor_courses($this->userid, $this->owner) as $c) {
            $o = new \stdClass();
            $o->id = (int)$c->id;
            $o->fullname = $c->fullname;
            $o->summary = format_text($c->summary, (int)$c->summaryformat);
            $o->url = (new \moodle_url('/course/view.php', ['id' => $c->id]))->out(false);
            $img = \core_course\external\course_summary_exporter::get_course_image($c);
            $o->imageurl = $img ?: '';
            $o->hasimage = !empty($img);
            $data->courses[] = $o;
        }
        $data->hascourses = !empty($data->courses);

        return $data;
    }
}
