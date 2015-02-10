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
 * Data Access Object for `photo_featured` table.
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.bol
 * @since 1.0
 */
class PHOTO_BOL_PhotoFeaturedDao extends OW_BaseDao
{
    /**
     * Singleton instance.
     *
     * @var PHOTO_BOL_PhotoFeaturedDao
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
     * @return PHOTO_BOL_PhotoFeaturedDao
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
        return 'PHOTO_BOL_PhotoFeatured';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'photo_featured';
    }

    /**
     * Check if photo is featured
     * 
     * @param int $photoId
     * @return boolean
     */
    public function isFeatured( $photoId )
    {
        if ( !$photoId )
            return false;

        $example = new OW_Example();
        $example->andFieldEqual('photoId', $photoId);

        $photo = $this->findObjectByExample($example);

        return $photo !== null ? true : false;
    }

    /**
     * Marks photo as featured
     * 
     * @param int $photoId
     * @return boolean
     */
    public function markFeatured( $photoId )
    {
        if ( !$photoId )
            return false;

        if ( $this->isFeatured($photoId) )
            return true;

        $photo = new PHOTO_BOL_PhotoFeatured();
        $photo->photoId = $photoId;

        $this->save($photo);

        return true;
    }

    /**
     * Marks photo as unfeatured
     * 
     * @param int $photoId
     * @return boolean
     */
    public function markUnfeatured( $photoId )
    {
        if ( !$photoId )
            return false;

        $example = new OW_Example();
        $example->andFieldEqual('photoId', $photoId);

        $this->deleteByExample($example);

        return true;
    }
}