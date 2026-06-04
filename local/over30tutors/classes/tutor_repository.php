<?php
namespace local_over30tutors;

defined('MOODLE_INTERNAL') || die();

/**
 * Odczyt danych tutorów z natywnych mechanizmów Moodle (cohorta, profil, role).
 */
class tutor_repository {

    /** idnumber cohorty wyznaczającej tutorów. */
    const COHORT_IDNUMBER = 'tutors';

    /** Shortname'y pól profilu używanych przez plugin. */
    const PROFILE_FIELDS = [
        'tutor_bio', 'tutor_tagline', 'tutor_slug',
        'tutor_web', 'tutor_linkedin', 'tutor_instagram', 'tutor_youtube',
    ];

    /** @var int|null|false cache id cohorty (false = nieustalone, null = brak). */
    private $cohortid = false;

    /** @var int[]|null cache listy userid tutorów (memoizacja w obrębie requestu). */
    private $tutoruserids = null;

    /** Cache id ról nauczycielskich. @var int[]|null */
    private $teacherroleids = null;

    private function get_cohort_id(): ?int {
        global $DB;
        if ($this->cohortid === false) {
            $rec = $DB->get_record('cohort', ['idnumber' => self::COHORT_IDNUMBER], 'id');
            $this->cohortid = $rec ? (int)$rec->id : null;
        }
        return $this->cohortid;
    }

    /** @return int[] userid wszystkich tutorów. */
    public function get_tutor_userids(): array {
        global $DB;
        if ($this->tutoruserids !== null) {
            return $this->tutoruserids;
        }
        $cid = $this->get_cohort_id();
        if ($cid === null) {
            return $this->tutoruserids = [];
        }
        return $this->tutoruserids = array_map('intval', $DB->get_fieldset_select(
            'cohort_members', 'userid', 'cohortid = :cid', ['cid' => $cid]
        ));
    }

    public function is_tutor(int $userid): bool {
        return in_array($userid, $this->get_tutor_userids(), true);
    }

    /** @return int[] roleid ról editingteacher + teacher. */
    private function get_teacher_role_ids(): array {
        global $DB;
        if ($this->teacherroleids === null) {
            $this->teacherroleids = array_map('intval', $DB->get_fieldset_select(
                'role', 'id', "shortname IN ('editingteacher','teacher')", []
            ));
        }
        return $this->teacherroleids;
    }

    /**
     * Kursy, w których user ma rolę (editing)teacher.
     *
     * @param int $userid
     * @param bool $includehidden true → zwraca też kursy visible=0 (widok właściciela)
     * @return \stdClass[] rekordy kursów (id, fullname, shortname, summary, summaryformat, visible)
     */
    public function get_tutor_courses(int $userid, bool $includehidden): array {
        global $DB;
        $roleids = $this->get_teacher_role_ids();
        if (empty($roleids)) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($roleids, SQL_PARAMS_NAMED, 'r');
        $params['userid'] = $userid;
        $params['clvl'] = CONTEXT_COURSE;
        $visiblesql = $includehidden ? '' : 'AND c.visible = 1';
        $sql = "SELECT DISTINCT c.id, c.fullname, c.shortname, c.summary, c.summaryformat, c.visible
                  FROM {role_assignments} ra
                  JOIN {context} ctx ON ctx.id = ra.contextid AND ctx.contextlevel = :clvl
                  JOIN {course} c ON c.id = ctx.instanceid
                 WHERE ra.userid = :userid
                   AND ra.roleid $insql
                   AND c.id <> " . SITEID . "
                   $visiblesql
              ORDER BY c.fullname ASC";
        return array_values($DB->get_records_sql($sql, $params));
    }

    /**
     * @return array<string,string> shortname => wartość (pusty string gdy brak)
     */
    public function get_profile_fields(int $userid): array {
        global $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $record = profile_user_record($userid, false); // obiekt: shortname => value
        $out = [];
        foreach (self::PROFILE_FIELDS as $sn) {
            $out[$sn] = isset($record->$sn) ? (string)$record->$sn : '';
        }
        return $out;
    }

    /**
     * Slug → userid tutora. Najpierw pole tutor_slug, potem username.
     * @return int userid albo 0 gdy nie znaleziono / nie jest tutorem.
     */
    public function resolve_slug(string $slug): int {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/profile/lib.php');
        $slug = trim($slug);
        if ($slug === '') {
            return 0;
        }
        // 1) pole tutor_slug
        $field = $DB->get_record('user_info_field', ['shortname' => 'tutor_slug'], 'id');
        if ($field) {
            $uid = $DB->get_field('user_info_data', 'userid',
                ['fieldid' => $field->id, 'data' => $slug]);
            if ($uid && $this->is_tutor((int)$uid)) {
                return (int)$uid;
            }
        }
        // 2) fallback: username
        $user = $DB->get_record('user', ['username' => $slug, 'deleted' => 0], 'id');
        if ($user && $this->is_tutor((int)$user->id)) {
            return (int)$user->id;
        }
        return 0;
    }

    /** @return string[] nazwy tagów (interests) usera. */
    public function get_tutor_tags(int $userid): array {
        $tags = \core_tag_tag::get_item_tags('core', 'user', $userid);
        return array_values(array_map(fn($t) => $t->rawname, $tags));
    }

    /**
     * userid tutorów oznaczonych danym tagiem.
     * @return int[]
     */
    public function get_tutors_by_tag(string $tag): array {
        $tutorids = $this->get_tutor_userids();
        if (empty($tutorids)) {
            return [];
        }
        $out = [];
        foreach ($tutorids as $uid) {
            $names = array_map('core_text::strtolower', $this->get_tutor_tags($uid));
            if (in_array(\core_text::strtolower($tag), $names, true)) {
                $out[] = (int)$uid;
            }
        }
        return $out;
    }
}
