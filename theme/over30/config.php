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
