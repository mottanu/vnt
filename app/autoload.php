<?php

use Doctrine\Common\Annotations\AnnotationRegistry;
use Composer\Autoload\ClassLoader;
use Doctrine\ODM\MongoDB\Mapping\Driver\AnnotationDriver;

/**
 * @var ClassLoader $loader
 */
$loader = require __DIR__.'/../vendor/autoload.php';

AnnotationRegistry::registerLoader([$loader, 'loadClass']);
AnnotationDriver::registerAnnotationClasses();

$loader->add('Google_', __DIR__.'/../vendor/google');

return $loader;
