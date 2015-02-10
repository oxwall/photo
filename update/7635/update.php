<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2011, Oxwall Foundation
 * All rights reserved.

 * Redistribution and use in source and binary forms, with or without modification, are permitted provided that the
 * following conditions are met:
 *
 *  - Redistributions of source code must retain the above copyright notice, this list of conditions and
 *  the following disclaimer.
 *
 *  - Redistributions in binary form must reproduce the above copyright notice, this list of conditions and
 *  the following disclaimer in the documentation and/or other materials provided with the distribution.
 *
 *  - Neither the name of the Oxwall Foundation nor the names of its contributors may be used to endorse or promote products
 *  derived from this software without specific prior written permission.

 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES,
 * INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR
 * PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO,
 * PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED
 * AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 */

try 
{
    OW::getAuthorization()->deleteAction('photo', 'delete_comment_by_content_owner');
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

$plugin = OW::getPluginManager()->getPlugin('photo');
$staticDir = OW_DIR_STATIC_PLUGIN . $plugin->getModuleName() . DS . 'js' .DS;

if ( !file_exists($staticDir) )
{
    mkdir($staticDir);
    chmod($staticDir, 0777);
}

@copy($plugin->getStaticJsDir() . 'album.js', $staticDir . 'album.js');
@copy($plugin->getStaticJsDir() . 'browse_photo.js', $staticDir . 'browse_photo.js');
@copy($plugin->getStaticJsDir() . 'codemirror.min.js', $staticDir . 'codemirror.min.js');
@copy($plugin->getStaticJsDir() . 'jQueryRotate.min.js', $staticDir . 'jQueryRotate.min.js');
@copy($plugin->getStaticJsDir() . 'photo.js', $staticDir . 'photo.js');
@copy($plugin->getStaticJsDir() . 'upload.js', $staticDir . 'upload.js');
@copy($plugin->getStaticJsDir() . 'slider.min.js', $staticDir . 'slider.min.js');

try
{
    OW::getNavigation()->deleteMenuItem('photo', 'photo');
    OW::getNavigation()->addMenuItem(OW_Navigation::MAIN, 'view_photo_list', 'photo', 'photo', OW_Navigation::VISIBLE_FOR_ALL);   
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

try
{
    Updater::getDbo()->query('
        ALTER TABLE `' . OW_DB_PREFIX . 'photo`
            ADD `dimension` VARCHAR(128) NULL DEFAULT NULL ;
        ALTER TABLE `' . OW_DB_PREFIX . 'photo_album` ADD `description` TEXT NULL DEFAULT NULL AFTER `name`;
    ');
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

try
{
    Updater::getDbo()->query('
        CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'photo_album_cover` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `albumId` int(10) unsigned NOT NULL,
          `hash` varchar(100) DEFAULT NULL,
          `auto` tinyint(1) NOT NULL DEFAULT 1,
          PRIMARY KEY (`id`),
          UNIQUE KEY `albumId` (`albumId`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

        CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'photo_cache` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `key` int(11) NOT NULL,
          `data` text NOT NULL,
          `createTimestamp` int(10) unsigned NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `key` (`key`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

        CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'photo_search_data` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `entityTypeId` int(10) unsigned NOT NULL,
          `entityId` int(10) unsigned NOT NULL,
          `content` text NOT NULL,
          PRIMARY KEY (`id`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

        CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'photo_search_entity_type` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `entityType` varchar(15) NOT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `entityType` (`entityType`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

        CREATE TABLE IF NOT EXISTS `' . OW_DB_PREFIX . 'photo_search_index` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `entityTypeId` int(10) unsigned NOT NULL,
          `entityId` int(10) unsigned NOT NULL,
          `content` text NOT NULL,
          PRIMARY KEY (`id`),
          KEY `entityTypeId` (`entityTypeId`,`entityId`),
          FULLTEXT KEY `content` (`content`)
        ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;
    ');
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

try
{
    UPDATE_Autoload::getInstance()->addPackagePointer('PHOTO_BOL', $plugin->getBolDir());
    PHOTO_BOL_SearchService::getInstance()->addEntityType(PHOTO_BOL_SearchService::ENTITY_TYPE_ALBUM);
    PHOTO_BOL_SearchService::getInstance()->addEntityType(PHOTO_BOL_SearchService::ENTITY_TYPE_PHOTO);
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

try
{
    $entityTypeId = PHOTO_BOL_SearchService::getInstance()->getEntityTypeId(PHOTO_BOL_SearchService::ENTITY_TYPE_PHOTO);
    
    Updater::getDbo()->query('INSERT INTO `' . PHOTO_BOL_SearchDataDao::getInstance()->getTableName() . '` (`entityTypeId`, `entityId`, `content`)
        SELECT ' . $entityTypeId . ', `id`, `description`
        FROM `' . PHOTO_BOL_PhotoDao::getInstance()->getTableName() . '`');
}
catch ( Exception $e )
{
    Updater::getLogger()->addEntry(json_encode($e));
}

$config = Updater::getConfigService();

if ( !$config->configExists('photo', 'photo_list_view_classic') )
{
    $config->addConfig('photo', 'photo_list_view_classic', FALSE);
}

if ( !$config->configExists('photo', 'album_list_view_classic') )
{
    $config->addConfig('photo', 'album_list_view_classic', FALSE);
}

if ( !$config->configExists('photo', 'photo_view_classic') )
{
    $config->addConfig('photo', 'photo_view_classic', FALSE);
}

if ( !$config->configExists('photo', 'download_accept') )
{
    $config->addConfig('photo', 'download_accept', TRUE);
}

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__) . DS . 'langs.zip', 'photo');

Updater::getDbo()->query('DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo_update_tag`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo_update_tag` (
  `entityTagId` int(10) unsigned NOT NULL,
  UNIQUE KEY `entityTagId` (`entityTagId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;');

if ( !$config->configExists('photo', 'update_tag_process') )
{
    $config->addConfig('photo', 'update_tag_process', TRUE);
}

$config->saveConfig('photo', 'update_tag_process', TRUE);
