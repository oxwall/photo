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
class PHOTO_BOL_SearchIndexDao extends OW_BaseDao
{
    CONST ENTITY_TYPE_ID = 'entityTypeId';
    CONST ENTITY_ID = 'entityId';
    CONST CONTENT = 'content';
    
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
        return OW_DB_PREFIX . 'photo_search_index';
    }
    
    public function getDtoClassName()
    {
        return 'PHOTO_BOL_SearchIndex';
    }
    
    public function getMinWordLen()
    {
        static $ftMinWordLen = NULL;
        
        if ( $ftMinWordLen === NULL )
        {
            $len = $this->dbo->queryForRow('SHOW VARIABLES LIKE "ft_min_word_len"');
            $ftMinWordLen = (int)$len['Value'];
        }
        
        return $ftMinWordLen;
    }
    
    public function findIndexedData( $searchVal, array $entityTypes = array(), $limit = PHOTO_BOL_SearchService::SEARCH_LIMIT )
    {
        $condition = PHOTO_BOL_PhotoService::getInstance()->getQueryCondition('searchByDesc', array('photo' => 'p', 'album' => 'a'));

        $sql = 'SELECT `index`.*
            FROM `' . $this->getTableName() . '` AS `index`
                INNER JOIN `' . PHOTO_BOL_PhotoDao::getInstance()->getTableName() . '` AS `p` ON(`index`.`entityId` = `p`.`id`)
                INNER JOIN `' . PHOTO_BOL_PhotoAlbumDao::getInstance()->getTableName() . '` AS `a` ON(`a`.`id` = `p`.`albumId`)
            ' . $condition['join'] . '
            WHERE MATCH(`index`.`' . self::CONTENT . '`) AGAINST(:val IN BOOLEAN MODE) AND `p`.`privacy` = :everybody AND `p`.`status` = :status AND ' . $condition['where'];
        
        if ( count($entityTypes) !== 0 )
        {
            $sql .= ' AND `index`.`' . self::ENTITY_TYPE_ID . '` IN (SELECT `entity`.`id`
                FROM `' . PHOTO_BOL_SearchEntityTypeDao::getInstance()->getTableName() . '` AS `entity`
                WHERE `entity`.`' . PHOTO_BOL_SearchEntityTypeDao::ENTITY_TYPE . '` IN( ' . $this->dbo->mergeInClause($entityTypes) . '))';
        }
        
        $sql .= ' LIMIT :limit';
        
        return $this->dbo->queryForObjectList($sql, $this->getDtoClassName(), array_merge($condition['params'], array('val' => $searchVal, 'limit' => (int)$limit, 'everybody' => PHOTO_BOL_PhotoDao::PRIVACY_EVERYBODY, 'status' => 'approved')));
    }
    
    public function deleteIndexItem( $entityTypeId, $entityId )
    {
        if ( empty($entityTypeId) || empty($entityId) )
        {
            return FALSE;
        }
        
        $example = new OW_Example();
        $example->andFieldEqual(self::ENTITY_TYPE_ID, $entityTypeId);
        $example->andFieldEqual(self::ENTITY_ID, $entityId);
        
        return $this->deleteByExample($example);
    }
}
