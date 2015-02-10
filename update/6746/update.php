<?php

$sql = array();
$sql[] = "ALTER TABLE `" . OW_DB_PREFIX . "photo` ADD `hash` VARCHAR( 16 ) NULL DEFAULT NULL;";
$sql[] = "ALTER TABLE `" . OW_DB_PREFIX . "photo` ADD `uploadKey` VARCHAR( 32 ) NULL DEFAULT NULL";

foreach ( $sql as $query )
{
    try
    {
        Updater::getDbo()->query($query);
    }
    catch ( Exception $e ) { }
}