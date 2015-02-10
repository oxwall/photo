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

/**
 * @author Kairat Bakitow <kainisoft@gmail.com>
 * @package ow_plugins.photo.bol
 * @since 1.6.1
 */
class PHOTO_BOL_PhotoAlbumCoverDao extends OW_BaseDao
{
    CONST ALBUM_ID = 'albumId';
    CONST AUTO = 'auto';
    
    CONST PREFIX_ALBUM_COVER = 'cover_';
    CONST PREFIX_ALBUM_COVER_ORIG = 'cover_orig_';
    CONST PREFIX_ALBUM_COVER_DEFAULT = 'album_no_cover.png';
    
    private static $classInstance;

    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }
    
    public function getTableName()
    {
        return OW_DB_PREFIX . 'photo_album_cover';
    }
    
    public function getDtoClassName()
    {
        return 'PHOTO_BOL_PhotoAlbumCover';
    }

    public function isAlbumCoverExist( $albumId )
    {
        if ( empty($albumId) )
        {
            return FALSE;
        }
        
        $sql = 'SELECT COUNT(*)
            FROM `' . $this->getTableName() . '`
            WHERE `' . self::ALBUM_ID . '` = :albumId';
        
        return (int)$this->dbo->queryForColumn($sql, array('albumId' => $albumId)) > 0;
    }

    public function findByAlbumId( $albumId )
    {
        if ( empty($albumId) )
        {
            return NULL;
        }
        
        $sql = 'SELECT *
            FROM `' . $this->getTableName() . '`
            WHERE `' . self::ALBUM_ID . '` = :albumId';
        
        return $this->dbo->queryForObject($sql, $this->getDtoClassName(), array('albumId' => $albumId));
    }

    public function getAlbumCoverUrlByAlbumId( $albumId, $orig = FALSE )
    {
        if ( empty($albumId) )
        {
            return NULL;
        }
        
        if ( ($cover = $this->findByAlbumId($albumId)) === NULL )
        {
            if ( ($photo = PHOTO_BOL_PhotoAlbumService::getInstance()->getLastPhotoByAlbumId($albumId)) !== NULL )
            {
                return PHOTO_BOL_PhotoDao::getInstance()->getPhotoUrl($photo->id, $photo->hash, PHOTO_BOL_PhotoService::TYPE_PREVIEW, !empty($photo->dimension) ? $photo->dimension : FALSE);
            }
            else
            {
                return $this->getAlbumCoverDefaultUrl();
            }
        }
        else
        {
            if ( $orig )
            {
                return OW::getStorage()->getFileUrl(OW::getPluginManager()->getPlugin('photo')->getUserFilesDir() . self::PREFIX_ALBUM_COVER_ORIG . $cover->id . '_' . $cover->hash . '.jpg');
            }

            return OW::getStorage()->getFileUrl(OW::getPluginManager()->getPlugin('photo')->getUserFilesDir() . self::PREFIX_ALBUM_COVER . $cover->id . '_' . $cover->hash . '.jpg');
        }
    }
    
    public function getAlbumCoverPathByAlbumId( $albumId )
    {
        if ( empty($albumId) || ($cover = $this->findByAlbumId($albumId)) === NULL )
        {
            $lastPhoto = PHOTO_BOL_PhotoAlbumService::getInstance()->getLastPhotoByAlbumId($albumId);
            
            return PHOTO_BOL_PhotoDao::getInstance()->getPhotoPath($lastPhoto->id, $lastPhoto->hash, 'main');
        }
        
        return OW::getPluginManager()->getPlugin('photo')->getUserFilesDir() . self::PREFIX_ALBUM_COVER_ORIG . $cover->id . '_' . $cover->hash . '.jpg';
    }

    public function getAlbumCoverUrlForCoverEntity( PHOTO_BOL_PhotoAlbumCover $cover )
    {
        return OW::getStorage()->getFileUrl(OW::getPluginManager()->getPlugin('photo')->getUserFilesDir() . self::PREFIX_ALBUM_COVER . $cover->id . '_' . $cover->hash . '.jpg');
    }
    
    public function getAlbumCoverOrigUrlForCoverEntity( PHOTO_BOL_PhotoAlbumCover $cover )
    {
        return OW::getStorage()->getFileUrl(OW::getPluginManager()->getPlugin('photo')->getUserFilesDir() . self::PREFIX_ALBUM_COVER_ORIG . $cover->id . '_' . $cover->hash . '.jpg');
    }

    public function getAlbumCoverDefaultUrl()
    {
        static $url = NULL;
        
        if ( $url === NULL )
        {
            $url = OW_URL_STATIC_THEMES . OW::getConfig()->getValue('base', 'selectedTheme') . '/images/' . self::PREFIX_ALBUM_COVER_DEFAULT;
        }
        
        return $url;
    }
    
    public function getAlbumCoverPathForCoverEntity( PHOTO_BOL_PhotoAlbumCover $cover )
    {
        return OW::getPluginManager()->getPlugin('photo')->getUserFilesDir() . self::PREFIX_ALBUM_COVER . $cover->id . '_' . $cover->hash . '.jpg';
    }
    
    public function getAlbumCoverOrigPathForCoverEntity( PHOTO_BOL_PhotoAlbumCover $cover )
    {
        return OW::getPluginManager()->getPlugin('photo')->getUserFilesDir() . self::PREFIX_ALBUM_COVER_ORIG . $cover->id . '_' . $cover->hash . '.jpg';
    }
    
    public function getAlbumCoverUrlListForAlbumIdList( array $albumIdList )
    {
        if ( count($albumIdList) === 0 )
        {
            return array();
        }
        
        $sql = 'SELECT *
            FROM `' . $this->getTableName() . '`
            WHERE `' . self::ALBUM_ID . '` IN (' . implode(',', array_map('intval', $albumIdList)) . ')';
        
        $list = $this->dbo->queryForObjectList($sql, $this->getDtoClassName());
        
        $result = array();
        $storage = OW::getStorage();
        $dir = OW::getPluginManager()->getPlugin('photo')->getUserFilesDir() . self::PREFIX_ALBUM_COVER;

        foreach ( $list as $cover )
        {
            $result[$cover->albumId] = $storage->getFileUrl($dir . $cover->id . '_' . $cover->hash . '.jpg');
        }
        
        return $result;
    }
    
    public function deleteCoverByAlbumId( $albumId )
    {
        if ( empty($albumId) || ($cover = $this->findByAlbumId($albumId)) === NULL )
        {
            return FALSE;
        }
        
        $storate = OW::getStorage();
        $storate->removeFile($this->getAlbumCoverPathForCoverEntity($cover));
        $storate->removeFile($this->getAlbumCoverOrigPathForCoverEntity($cover));
        
        $example = new OW_Example();
        $example->andFieldEqual(self::ALBUM_ID, $albumId);
        
        return $this->deleteByExample($example);
    }
}
