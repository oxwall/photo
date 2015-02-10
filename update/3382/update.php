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

if ( !$config->configExists('photo', 'advanced_upload_enabled') )
{
    $config->addConfig('photo', 'advanced_upload_enabled', 1, 'Enables advanced multiple file flash uploader');
}

if ( !$config->configExists('photo', 'fullsize_resolution') )
{
    $config->addConfig('photo', 'fullsize_resolution', 1024, 'Full-size photo resolution');
}

try
{
    $sql = "ALTER TABLE `".OW_DB_PREFIX."photo` ADD `privacy` varchar(50) NOT NULL default 'everybody';";

    Updater::getDbo()->query($sql);
}
catch ( Exception $e ){ }

try {
    $sql = "CREATE TABLE IF NOT EXISTS `".OW_DB_PREFIX."photo_temporary` (
      `id` int(11) NOT NULL auto_increment,
      `userId` int(11) NOT NULL,
      `addDatetime` int(11) NOT NULL,
      `hasFullsize` tinyint(1) NOT NULL default '0',
      `order` int(11) NOT NULL default '0',
      PRIMARY KEY  (`id`),
      KEY `userId` (`userId`)
    ) ENGINE=MyISAM  DEFAULT CHARSET=utf8;";
    
    Updater::getDbo()->query($sql);
}
catch ( Exception $e ){ }


$plugin = OW::getPluginManager()->getPlugin('photo');

$staticDir = OW_DIR_STATIC_PLUGIN . $plugin->getModuleName() . DS;
$staticJsDir = $staticDir  . 'js' . DS;
$staticSwfDir = $staticDir  . 'swf' . DS;

if ( !file_exists($staticDir) )
{
    mkdir($staticDir);
    chmod($staticDir, 0777);
}

if ( !file_exists($staticJsDir) )
{
    mkdir($staticJsDir);
    chmod($staticJsDir, 0777);
}

@copy($plugin->getStaticJsDir() . 'swfobject.js', $staticJsDir . 'swfobject.js');
@copy($plugin->getStaticJsDir() . 'upload_photo.js', $staticJsDir . 'upload_photo.js');
@copy($plugin->getStaticJsDir() . 'photo.js', $staticJsDir . 'photo.js');

if ( !file_exists($staticSwfDir) )
{
    mkdir($staticSwfDir);
    chmod($staticSwfDir, 0777);
}

@copy($plugin->getStaticDir() . 'swf' . DS . 'playerProductInstall.swf', $staticSwfDir . 'playerProductInstall.swf');
@copy($plugin->getStaticDir() . 'swf' . DS . 'main.swf', $staticSwfDir . 'main.swf');

Updater::getLanguageService()->importPrefixFromZip(dirname(__FILE__).DS.'langs.zip', 'photo');
