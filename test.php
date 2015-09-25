<?php

include 'vendor/autoload.php';

$path     = '/www/demo.joomlaavenue.com/administrator/components/com_newsfeeds/newsfeeds.xml';
$path     = '/www/demo.joomlaavenue.com/modules/mod_instagram_media/mod_instagram_media.xml';
$path     = '/www/demo.joomlaavenue.com/administrator/components/com_eacolors/com_eacolors.xml';

$manifest = \JCli\Joomla\ManiferstReader::parse($path, '/www/demo.joomlaavenue.com');

