<?php
namespace local_over30tutors;

defined('MOODLE_INTERNAL') || die();

final class tutor_repository_test extends \advanced_testcase {

    /** Tworzy cohortę 'tutors' i dodaje userów; zwraca repo. */
    private function setup_cohort(array $userids): \local_over30tutors\tutor_repository {
        global $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        $cohort = $this->getDataGenerator()->create_cohort(['idnumber' => 'tutors']);
        foreach ($userids as $uid) {
            cohort_add_member($cohort->id, $uid);
        }
        return new \local_over30tutors\tutor_repository();
    }

    /** Tworzy pole profilu typu text. */
    private function create_profile_field(string $shortname, bool $unique = false): void {
        $this->getDataGenerator()->create_custom_profile_field([
            'datatype' => 'text',
            'shortname' => $shortname,
            'name' => $shortname,
            'forceunique' => $unique ? 1 : 0,
        ]);
    }

    public function test_get_tutor_userids_returns_cohort_members(): void {
        $this->resetAfterTest();
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $this->getDataGenerator()->create_user(); // not a member
        $repo = $this->setup_cohort([$u1->id, $u2->id]);

        $ids = $repo->get_tutor_userids();

        sort($ids);
        $expected = [$u1->id, $u2->id];
        sort($expected);
        $this->assertEquals($expected, $ids);
    }

    public function test_get_tutor_userids_empty_when_no_cohort(): void {
        $this->resetAfterTest();
        $repo = new \local_over30tutors\tutor_repository();
        $this->assertSame([], $repo->get_tutor_userids());
    }

    public function test_is_tutor(): void {
        $this->resetAfterTest();
        $u1 = $this->getDataGenerator()->create_user();
        $u2 = $this->getDataGenerator()->create_user();
        $repo = $this->setup_cohort([$u1->id]);

        $this->assertTrue($repo->is_tutor($u1->id));
        $this->assertFalse($repo->is_tutor($u2->id));
    }

    public function test_get_tutor_courses_only_teacher_visible(): void {
        $this->resetAfterTest();
        $tutor = $this->getDataGenerator()->create_user();
        $repo = $this->setup_cohort([$tutor->id]);

        $teaches = $this->getDataGenerator()->create_course(['visible' => 1, 'fullname' => 'Taught']);
        $hidden  = $this->getDataGenerator()->create_course(['visible' => 0, 'fullname' => 'Hidden']);
        $student = $this->getDataGenerator()->create_course(['visible' => 1, 'fullname' => 'AsStudent']);

        $this->getDataGenerator()->enrol_user($tutor->id, $teaches->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($tutor->id, $hidden->id, 'editingteacher');
        $this->getDataGenerator()->enrol_user($tutor->id, $student->id, 'student');

        $courses = $repo->get_tutor_courses($tutor->id, false); // false = tylko widoczne

        $ids = array_map(fn($c) => (int)$c->id, $courses);
        $this->assertContains((int)$teaches->id, $ids);
        $this->assertNotContains((int)$hidden->id, $ids);   // ukryty pominięty dla gościa
        $this->assertNotContains((int)$student->id, $ids);  // rola student pominięta
    }

    public function test_get_tutor_courses_includes_hidden_for_owner(): void {
        $this->resetAfterTest();
        $tutor = $this->getDataGenerator()->create_user();
        $repo = $this->setup_cohort([$tutor->id]);
        $hidden = $this->getDataGenerator()->create_course(['visible' => 0]);
        $this->getDataGenerator()->enrol_user($tutor->id, $hidden->id, 'teacher');

        $courses = $repo->get_tutor_courses($tutor->id, true); // true = włącznie z ukrytymi
        $ids = array_map(fn($c) => (int)$c->id, $courses);
        $this->assertContains((int)$hidden->id, $ids);
    }

    public function test_get_profile_fields_reads_custom_fields(): void {
        $this->resetAfterTest();
        $this->create_profile_field('tutor_bio');
        $this->create_profile_field('tutor_tagline');
        $user = $this->getDataGenerator()->create_user([
            'profile_field_tutor_bio' => 'Hello bio',
            'profile_field_tutor_tagline' => 'My tagline',
        ]);
        $repo = $this->setup_cohort([$user->id]);

        $fields = $repo->get_profile_fields($user->id);

        $this->assertSame('Hello bio', $fields['tutor_bio']);
        $this->assertSame('My tagline', $fields['tutor_tagline']);
    }

    public function test_resolve_slug_uses_slug_field(): void {
        $this->resetAfterTest();
        $this->create_profile_field('tutor_slug', true);
        $user = $this->getDataGenerator()->create_user(['profile_field_tutor_slug' => 'jan-kowalski']);
        $repo = $this->setup_cohort([$user->id]);

        $this->assertSame((int)$user->id, $repo->resolve_slug('jan-kowalski'));
    }

    public function test_resolve_slug_falls_back_to_username(): void {
        $this->resetAfterTest();
        $this->create_profile_field('tutor_slug', true);
        $user = $this->getDataGenerator()->create_user(['username' => 'janek']);
        $repo = $this->setup_cohort([$user->id]);

        $this->assertSame((int)$user->id, $repo->resolve_slug('janek'));
    }

    public function test_resolve_slug_returns_zero_for_non_tutor(): void {
        $this->resetAfterTest();
        $this->create_profile_field('tutor_slug', true);
        $user = $this->getDataGenerator()->create_user(['username' => 'outsider']);
        $repo = $this->setup_cohort([]); // user nie jest w cohorcie
        $this->assertSame(0, $repo->resolve_slug('outsider'));
    }

    public function test_get_tutor_tags(): void {
        $this->resetAfterTest();
        $user = $this->getDataGenerator()->create_user(['interests' => ['zdrowie', 'rozwój']]);
        $repo = $this->setup_cohort([$user->id]);

        $tags = $repo->get_tutor_tags($user->id);
        sort($tags);
        $this->assertEquals(['rozwój', 'zdrowie'], $tags);
    }

    public function test_get_tutors_by_tag_filters(): void {
        $this->resetAfterTest();
        $a = $this->getDataGenerator()->create_user(['interests' => ['zdrowie']]);
        $b = $this->getDataGenerator()->create_user(['interests' => ['finanse']]);
        $repo = $this->setup_cohort([$a->id, $b->id]);

        $ids = $repo->get_tutors_by_tag('zdrowie');
        $this->assertContains((int)$a->id, $ids);
        $this->assertNotContains((int)$b->id, $ids);
    }
}
