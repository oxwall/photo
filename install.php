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

$config = OW::getConfig();

if ( !$config->configExists('photo', 'accepted_filesize') )
{
    $config->addConfig('photo', 'accepted_filesize', 32, 'Maximum accepted file size');
}

if ( !$config->configExists('photo', 'main_image_width') )
{
    $config->addConfig('photo', 'main_image_width', 960, 'Main image width');
}

if ( !$config->configExists('photo', 'main_image_height') )
{
    $config->addConfig('photo', 'main_image_height', 640, 'Main image height');
}

if ( !$config->configExists('photo', 'preview_image_width') )
{
    $config->addConfig('photo', 'preview_image_width', 140, 'Preview image width');
}

if ( !$config->configExists('photo', 'preview_image_height') )
{
    $config->addConfig('photo', 'preview_image_height', 140, 'Preview image height');
}

if ( !$config->configExists('photo', 'photos_per_page') )
{
    $config->addConfig('photo', 'photos_per_page', 20, 'Photos per page');
}

if ( !$config->configExists('photo', 'album_quota') )
{
    $config->addConfig('photo', 'album_quota', 400, 'Maximum number of photos per album');
}

if ( !$config->configExists('photo', 'user_quota') )
{
    $config->addConfig('photo', 'user_quota', 5000, 'Maximum number of photos per user');
}

if ( !$config->configExists('photo', 'store_fullsize') )
{
    $config->addConfig('photo', 'store_fullsize', 1, 'Store full-size photos');
}

if ( !$config->configExists('photo', 'uninstall_inprogress') )
{
    $config->addConfig('photo', 'uninstall_inprogress', 0, 'Plugin is being uninstalled');
}

if ( !$config->configExists('photo', 'uninstall_cron_busy') )
{
    $config->addConfig('photo', 'uninstall_cron_busy', 0, 'Uninstall queue is busy');
}

if ( !$config->configExists('photo', 'maintenance_mode_state') )
{
    $state = (int) $config->getValue('base', 'maintenance');
    $config->addConfig('photo', 'maintenance_mode_state', $state, 'Stores site maintenance mode config before plugin uninstallation');
}

if ( !$config->configExists('photo', 'fullsize_resolution') )
{
    $config->addConfig('photo', 'fullsize_resolution', 1024, 'Full-size photo resolution');
}

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

OW::getDbo()->query('DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `albumId` int(11) NOT NULL,
  `description` text,
  `addDatetime` int(10) DEFAULT NULL,
  `status` enum("approval","approved","blocked") NOT NULL DEFAULT "approved",
  `hasFullsize` tinyint(1) NOT NULL DEFAULT "1",
  `privacy` varchar(50) NOT NULL DEFAULT "everybody",
  `hash` varchar(16) DEFAULT NULL,
  `uploadKey` varchar(32) DEFAULT NULL,
  `dimension` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `albumId` (`albumId`),
  KEY `status` (`status`),
  KEY `privacy` (`privacy`),
  KEY `uploadKey` (`uploadKey`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo_album`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo_album` (
  `id` int(11) NOT NULL auto_increment,
  `userId` int(11) NOT NULL,
  `entityType` VARCHAR( 50 ) CHARACTER SET utf8 COLLATE utf8_general_ci NULL DEFAULT "user",
  `entityId` INT NULL DEFAULT NULL,
  `name` varchar(100) NOT NULL,
  `description` text,
  `createDatetime` int(10) default NULL,
  PRIMARY KEY  (`id`),
  KEY `name` (`name`),
  KEY `userId` (`userId`),
  KEY `entityType` (`entityType`),
  KEY `entityId` (`entityId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo_featured`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo_featured` (
  `id` int(11) NOT NULL auto_increment,
  `photoId` int(11) NOT NULL default "0",
  PRIMARY KEY  (`id`),
  KEY `photoId` (`photoId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo_temporary`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo_temporary` (
  `id` int(11) NOT NULL auto_increment,
  `userId` int(11) NOT NULL,
  `addDatetime` int(11) NOT NULL,
  `hasFullsize` tinyint(1) NOT NULL default "0",
  `order` int(11) NOT NULL default "0",
  PRIMARY KEY  (`id`),
  KEY `userId` (`userId`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo_album_cover`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo_album_cover` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `albumId` int(10) unsigned NOT NULL,
  `hash` varchar(100) DEFAULT NULL,
  `auto` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `albumId` (`albumId`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo_cache`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo_cache` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `key` int(11) NOT NULL,
  `data` text NOT NULL,
  `createTimestamp` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo_search_data`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo_search_data` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entityTypeId` int(10) unsigned NOT NULL,
  `entityId` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo_search_entity_type`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo_search_entity_type` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entityType` varchar(15) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entityType` (`entityType`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;

INSERT IGNORE INTO `' . OW_DB_PREFIX . 'photo_search_entity_type` (`id`, `entityType`) VALUES
(null, "photo.album"),
(null, "photo.photo");

DROP TABLE IF EXISTS `' . OW_DB_PREFIX . 'photo_search_index`;
CREATE TABLE `' . OW_DB_PREFIX . 'photo_search_index` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `entityTypeId` int(10) unsigned NOT NULL,
  `entityId` int(10) unsigned NOT NULL,
  `content` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `entityTypeId` (`entityTypeId`,`entityId`),
  FULLTEXT KEY `content` (`content`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 ;');

OW::getPluginManager()->addPluginSettingsRouteName('photo', 'photo_admin_config');
OW::getPluginManager()->addUninstallRouteName('photo', 'photo_uninstall');

$authorization = OW::getAuthorization();
$groupName = 'photo';
$authorization->addGroup($groupName);
$authorization->addAction($groupName, 'upload');
$authorization->addAction($groupName, 'view', true);
$authorization->addAction($groupName, 'add_comment');

$plugin = OW::getPluginManager()->getPlugin('photo');

OW::getLanguage()->importPluginLangs($plugin->getRootDir() . 'langs.zip', 'photo');
