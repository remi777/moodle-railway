<?php
defined('MOODLE_INTERNAL') || die();
$THEME->name = 'over30';
$THEME->parents = ['boost'];
$THEME->sheets = [];
$THEME->editor_sheets = [];
$THEME->usefallback = true;
$THEME->scss = function($theme) { return theme_over30_get_main_scss_content($theme); };
$THEME->prescsscallback = 'theme_over30_get_pre_scss';
$THEME->extrascsscallback = 'theme_over30_get_extra_scss';
$THEME->yuicssmodules = [];
$THEME->rendererfactory = 'theme_overridden_renderer_factory';
$THEME->requiredblocks = '';
$THEME->addblockposition = BLOCK_ADDBLOCK_POSITION_FLATNAV;
$THEME->layouts['frontpage'] = [
    'file' => 'frontpage.php',
    'regions' => ['side-pre'],
    'defaultregion' => 'side-pre',
    'options' => ['nonavbar' => true],
];
// Dashboard (/my) — over30 "Twoje Studio". Regions/options copied from Boost's
// mydashboard layout so all wiring is preserved.
$THEME->layouts['mydashboard'] = [
    'file' => 'mydashboard.php',
    'regions' => ['side-pre'],
    'defaultregion' => 'side-pre',
    'options' => ['nonavbar' => true, 'langmenu' => true],
];
// Course view — editorial banner over Boost's drawers layout. Regions/options
// copied verbatim from Boost's 'course' layout so all wiring is preserved.
$THEME->layouts['course'] = [
    'file' => 'course.php',
    'regions' => ['side-pre'],
    'defaultregion' => 'side-pre',
    'options' => ['langmenu' => true],
];
// Course catalog (/course/index.php) — editorial grid with a sticky category aside.
$THEME->layouts['coursecategory'] = [
    'file' => 'catalog.php',
    'regions' => ['side-pre'],
    'defaultregion' => 'side-pre',
    'options' => ['nonavbar' => true],
];

// Public course sales/landing page (set by local_over30catalog for guests).
$THEME->layouts['o30sales'] = [
    'file' => 'sales.php',
    'regions' => [],
    'options' => ['nonavbar' => true],
];
