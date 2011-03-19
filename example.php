<?php
require_once 'Firebear.php';
$cfg = array('gdoptimizer' => true);
$Screenshots = new Firebear($cfg);

/** пример для винды*/
$Screenshots->InitDaemon('taskmgr');
$Screenshots->PrintScreen('screen.png', 'Диспетчер задач Windows');

/** пример для линукса*/
$Screenshots->InitDaemon('gedit');
$Screenshots->PrintScreen('screen.png');
