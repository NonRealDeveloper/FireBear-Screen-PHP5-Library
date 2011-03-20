<?php
require_once 'Firebear.php';
$Screenshots = new Firebear(array(
	'gdoptimizer' => true, 
	'screenssize' => '0:300'
));

/*
# пример для винды
$Screenshots->InitDaemon('taskmgr');
$Screenshots->PrintScreen('screen.png', 'Диспетчер задач Windows');

# пример для линукса
$Screenshots->InitDaemon('gedit');
$Screenshots->PrintScreen('screen.png');

# грабинг с гугла (результаты увидите в папке screens)
$Screenshots->MakeScreen('пулемёт');
$Screenshots->MakeScreen('танк');
$Screenshots->MakeScreen('troll');
$Screenshots->MakeScreen('hds8afhds0afhdus9a0hfdu9s');
echo $Screenshots->Path('troll'); // вывести путь к скриншоту для troll
*/