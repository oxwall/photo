<?php

/**
 * This software is intended for use with Oxwall Free Community Software http://www.oxwall.org/ and is
 * licensed under The BSD license.

 * ---
 * Copyright (c) 2009, Skalfa LLC
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
 *  - Neither the name of the Skalfa LLC nor the names of its contributors may be used to endorse or promote products
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
 * Data Access Object for `photo_temporary` table.
 *
 * @authors Egor Bulgakov <egor.bulgakov@gmail.com>, Kairat Bakitow <kainisoft@gmail.com>
 * @package ow.plugin.photo.bol
 * @since 1.0
 */
class PHOTO_BOL_PhotoTemporaryDao extends OW_BaseDao
{
    CONST TMP_PHOTO_ORIGINAL_PREFIX = 'tmp_photo_original_'; // 3
    CONST TMP_PHOTO_FULLSCREEN = 'tmp_photo_fullscreen_';    // 5
    CONST TMP_PHOTO_PREFIX = 'tmp_photo_';                   // 2 main
    CONST TMP_PHOTO_PREVIEW_PREFIX = 'tmp_photo_preview_';   // 1
    CONST TMP_PHOTO_SMALL = 'tmp_photo_small_';              // 4
    
    /**
     * Singleton instance.
     *
     * @var PHOTO_BOL_PhotoTemporaryDao
     */
    private static $classInstance;

    /**
     * Constructor.
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Returns an instance of class.
     *
     * @return PHOTO_BOL_PhotoTemporaryDao
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    /**
     * @see OW_BaseDao::getDtoClassName()
     *
     */
    public function getDtoClassName()
    {
        return 'PHOTO_BOL_PhotoTemporary';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'photo_temporary';
    }

    /**
     * Get temporary photo URL
     *
     * @param int $id
     * @param int $size
     * 
     * @return string
     */
    public function getTemporaryPhotoUrl( $id, $size = 1 )
    {
        $userfilesUrl = OW::getPluginManager()->getPlugin('photo')->getUserFilesUrl();
        $ext = '.jpg';
        
        switch ( $size )
        {
            case 1:
                return $userfilesUrl . self::TMP_PHOTO_PREVIEW_PREFIX . $id . $ext;
            case 2:
                return $userfilesUrl . self::TMP_PHOTO_PREFIX . $id . $ext;
            case 3:
                return $userfilesUrl . self::TMP_PHOTO_ORIGINAL_PREFIX . $id . $ext;
            case 4:
                return $userfilesUrl . self::TMP_PHOTO_SMALL . $id . $ext;
            case 5:
                return $userfilesUrl . self::TMP_PHOTO_FULLSCREEN . $id . $ext;
        }

        return '';
    }
    
    /**
     * Get path to temporary photo in file system
     *
     * @param int $id
     * @param int $size
     * 
     * @return string
     */
    public function getTemporaryPhotoPath( $id, $size = 1 )
    {
        $userfilesDir = OW::getPluginManager()->getPlugin('photo')->getUserFilesDir();
        $ext = '.jpg';
        
        switch ( $size )
        {
            case 1:
                return $userfilesDir . self::TMP_PHOTO_PREVIEW_PREFIX . $id . $ext;
            case 2:
                return $userfilesDir . self::TMP_PHOTO_PREFIX . $id . $ext;
            case 3:
                return $userfilesDir . self::TMP_PHOTO_ORIGINAL_PREFIX . $id . $ext;
            case 4:
                return $userfilesDir . self::TMP_PHOTO_SMALL . $id. $ext;
            case 5:
                return $userfilesDir . self::TMP_PHOTO_FULLSCREEN . $id . $ext;
        }

        return '';
    }

    /**
     * Find photos by user Id
     *
     * @param int $userId
     *
     * @param string $orderBy
     * @return array
     */
    public function findByUserId( $userId, $orderBy = 'timestamp' )
    {
        if ( !$userId )
        {
            return null;
        }
        
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        
        if ( $orderBy == 'timestamp' )
        {
            $example->setOrder('`addDatetime` ASC');
        }
        else 
        {
            $example->setOrder('`order` ASC');
        }

        return $this->findListByExample($example);
    }
    
    public function findLimitedPhotos( $limit = PHOTO_BOL_PhotoTemporaryService::TEMPORARY_PHOTO_LIVE_LIMIT )
    {
        $sql = 'SELECT `id`
            FROM `' . $this->getTableName() . '`
            WHERE `addDatetime` <= :limit';
        
        return $this->dbo->queryForColumnList($sql, array('limit' => $limit));
    }
}