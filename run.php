<?php
declare(strict_types=1);

require './vendor/autoload.php';
require 'offers.php';

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\Console\Application;

$console = new Application();
$console->add(new Cli());
$console->run();

