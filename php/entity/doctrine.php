<?php
require_once '../vendor/autoload.php';
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
$isDevMode = true;

// the connection configuration
$dbParams = array(
    'driver'   => 'pdo_mysql',
    'user'     => 'pbjadmin',
    'password' => 'pbjadmin',
    'dbname'   => 'pbj',
);

$config = Setup::createAnnotationMetadataConfiguration($paths, $isDevMode);
$namingStrategy = new \Doctrine\ORM\Mapping\UnderscoreNamingStrategy(CASE_LOWER);
$config->setNamingStrategy($namingStrategy);

$config->setAutoGenerateProxyClasses(TRUE);

$em = EntityManager::create($dbParams, $config);

function getEntityManager() {
  global $em;
  return $em;
}

