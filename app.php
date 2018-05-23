<?php

require __DIR__.'/vendor/autoload.php';

use Symfony\Component\Console\Application;
use App\GenerateD3NodesCommand;

$application = new Application();

// ... register commands

$application->add(new GenerateD3NodesCommand());

$application->run();