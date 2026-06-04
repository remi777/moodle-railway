<?php
namespace local_over30tutors;

defined('MOODLE_INTERNAL') || die();

final class output_test extends \advanced_testcase {

    private function make_tutor(): \stdClass {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'tutor_bio', 'name' => 'bio']);
        $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'tutor_tagline', 'name' => 'tagline']);
        $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'tutor_web', 'name' => 'web']);
        $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'tutor_linkedin', 'name' => 'li']);
        $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'tutor_instagram', 'name' => 'ig']);
        $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'tutor_youtube', 'name' => 'yt']);
        $this->getDataGenerator()->create_custom_profile_field(
            ['datatype' => 'text', 'shortname' => 'tutor_slug', 'name' => 'slug', 'forceunique' => 1]);
        $user = $this->getDataGenerator()->create_user([
            'firstname' => 'Jan', 'lastname' => 'Kowalski',
            'interests' => ['zdrowie'],
            'profile_field_tutor_bio' => 'Bio text',
            'profile_field_tutor_tagline' => 'Tagline text',
            'profile_field_tutor_web' => 'https://example.com',
        ]);
        $cohort = $this->getDataGenerator()->create_cohort(['idnumber' => 'tutors']);
        cohort_add_member($cohort->id, $user->id);
        return $user;
    }

    public function test_tutor_page_export(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->make_tutor();
        $course = $this->getDataGenerator()->create_course(['visible' => 1, 'fullname' => 'Kurs A']);
        $this->getDataGenerator()->enrol_user($user->id, $course->id, 'editingteacher');

        $page = new \local_over30tutors\output\tutor_page($user->id, false);
        $data = $page->export_for_template($PAGE->get_renderer('core'));

        $this->assertSame('Jan Kowalski', $data->fullname);
        $this->assertSame('Tagline text', $data->tagline);
        $this->assertSame('Bio text', $data->bio);
        $this->assertNotEmpty($data->pictureurl);
        $this->assertContains('zdrowie', array_map(fn($t) => $t->name, $data->tags));
        $this->assertCount(1, $data->courses);
        $this->assertSame('Kurs A', $data->courses[0]->fullname);
        $links = array_map(fn($l) => $l->url, $data->links);
        $this->assertContains('https://example.com', $links);
    }

    public function test_catalog_export_lists_tutors(): void {
        global $PAGE;
        $this->resetAfterTest();
        $user = $this->make_tutor(); // Jan Kowalski, tag 'zdrowie'

        $catalog = new \local_over30tutors\output\catalog(null);
        $data = $catalog->export_for_template($PAGE->get_renderer('core'));

        $this->assertTrue($data->hastutors);
        $names = array_map(fn($t) => $t->fullname, $data->tutors);
        $this->assertContains('Jan Kowalski', $names);
    }

    public function test_catalog_export_filters_by_tag(): void {
        global $PAGE;
        $this->resetAfterTest();
        $this->make_tutor(); // tag 'zdrowie'

        $match = new \local_over30tutors\output\catalog('zdrowie');
        $this->assertTrue($match->export_for_template($PAGE->get_renderer('core'))->hastutors);

        $nomatch = new \local_over30tutors\output\catalog('finanse');
        $this->assertFalse($nomatch->export_for_template($PAGE->get_renderer('core'))->hastutors);
    }
}
