<?php
// Idempotentny provisioning strony tutora: pola profilu + cohorta.
// Uruchom W MIEJSCU instalacji (ścieżka do config.php liczona względem tego pliku):
//   railway ssh -s <svc> "su -s /bin/sh -c 'php /var/www/html/public/local/over30tutors/provision/provision_tutors.php' www-data"
define('CLI_SCRIPT', true);
require(dirname(__DIR__, 3) . '/config.php'); // .../public/config.php → root
require_once($CFG->libdir . '/clilib.php');    // cli_writeln() nie jest auto-ładowane
require_once($CFG->dirroot . '/cohort/lib.php');

global $DB;

// 1) Kategoria pól profilu „Tutor".
$catname = 'Tutor';
$category = $DB->get_record('user_info_category', ['name' => $catname]);
if (!$category) {
    $category = (object)['name' => $catname, 'sortorder' => 1];
    $category->id = $DB->insert_record('user_info_category', $category);
    cli_writeln("created profile category: $catname");
}

// 2) Pola profilu (shortname => [name, unique, textarea]).
$fields = [
    'tutor_tagline'   => ['Tagline', false, false],
    'tutor_bio'       => ['Bio', false, true],
    'tutor_slug'      => ['Slug (vanity URL)', true, false],
    'tutor_web'       => ['Website', false, false],
    'tutor_linkedin'  => ['LinkedIn', false, false],
    'tutor_instagram' => ['Instagram', false, false],
    'tutor_youtube'   => ['YouTube', false, false],
];
$sort = 1;
foreach ($fields as $shortname => [$name, $unique, $textarea]) {
    if ($DB->record_exists('user_info_field', ['shortname' => $shortname])) {
        cli_writeln("profile field exists: $shortname");
        $sort++;
        continue;
    }
    $f = (object)[
        'shortname' => $shortname,
        'name' => $name,
        'datatype' => $textarea ? 'textarea' : 'text',
        'categoryid' => $category->id,
        'sortorder' => $sort++,
        'required' => 0,
        'locked' => 0,
        'visible' => 1,                 // PROFILE_VISIBLE_ALL
        'forceunique' => $unique ? 1 : 0,
        'signup' => 0,
        'defaultdata' => '',
        'description' => '',
        'descriptionformat' => FORMAT_HTML,
        'param1' => $textarea ? null : 30,  // wyświetlana szerokość text
        'param2' => $textarea ? null : 2048,
    ];
    $DB->insert_record('user_info_field', $f);
    cli_writeln("created profile field: $shortname");
}

// 3) Cohorta 'tutors'.
if (!$DB->record_exists('cohort', ['idnumber' => 'tutors'])) {
    $cohort = (object)[
        'contextid' => context_system::instance()->id,
        'name' => 'Tutors',
        'idnumber' => 'tutors',
        'description' => 'Użytkownicy widoczni jako tutorzy w katalogu.',
        'descriptionformat' => FORMAT_HTML,
        'visible' => 1,
    ];
    cohort_add_cohort($cohort);
    cli_writeln('created cohort: tutors');
} else {
    cli_writeln('cohort exists: tutors');
}

cli_writeln('DONE');
