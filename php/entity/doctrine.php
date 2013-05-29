<?php
require_once '../vendor/autoload.php';
require_once '../env/env.php';
require_once 'user.php';
require_once 'event.php';
require_once 'guest.php';
require_once 'event_message.php';
require_once 'communication_preference.php';
require_once 'web_module.php';
require_once 'web_module_role.php';
require_once 'web_module_prop.php';
require_once 'event_web_module_role.php';
require_once 'event_web_module_prop.php';

use Doctrine\ORM\Tools\Setup;
use Doctrine\ORM\EntityManager;

$paths = array("../entity");

$config = Setup::createAnnotationMetadataConfiguration($paths, $DOCTRINE_DEVMODE);
$namingStrategy = new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy(CASE_LOWER);
$config->setNamingStrategy($namingStrategy);

$config->setAutoGenerateProxyClasses(TRUE);

$em = EntityManager::create($DOCTRINE_DBPARAMS, $config);

function getEntityManager() {
  global $em;
  return $em;
}

