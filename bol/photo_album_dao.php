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
 * Data Access Object for `photo_album` table.
 *
 * @authors Egor Bulgakov <egor.bulgakov@gmail.com>, Kairat Bakitow <kainisoft@gmail.com>
 * @package ow.plugin.photo.bol
 * @since 1.0
 */
class PHOTO_BOL_PhotoAlbumDao extends OW_BaseDao
{
    CONST NAME = 'name';
    CONST USER_ID = 'userId';
    CONST CREATE_DATETIME = 'createDatetime';

    /**
     * Constructor.
     *
     */
    protected function __construct()
    {
        parent::__construct();
    }
    /**
     * Singleton instance.
     *
     * @var PHOTO_BOL_PhotoAlbumDao
     */
    private static $classInstance;

    /**
     * Returns an instance of class (singleton pattern implementation).
     *
     * @return PHOTO_BOL_PhotoAlbumDao
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
        return 'PHOTO_BOL_PhotoAlbum';
    }

    /**
     * @see OW_BaseDao::getTableName()
     *
     */
    public function getTableName()
    {
        return OW_DB_PREFIX . 'photo_album';
    }

    /**
     * Count albums added by a user
     *
     * @param int $userId
     * @param $exclude
     * @return int
     */
    public function countAlbums( $userId, $exclude, $excludeEmpty = false )
    {
        if ( !$userId )
        {
            return false;
        }

        $condition = PHOTO_BOL_PhotoService::getInstance()->getQueryCondition('countAlbums',
            array('album' => 'a', 'photo' => 'p'),
            array(
                'userId' => $userId,
                'exclude' => $exclude,
                'excludeEmpty' => $excludeEmpty
            )
        );

        $sql = 'SELECT COUNT(DISTINCT `a`.`id`)
            FROM `' . $this->getTableName() . '` AS `a`
                ' . $condition['join'] . '
                ' . ($excludeEmpty ? 'INNER JOIN `' . PHOTO_BOL_PhotoDao::getInstance()->getTableName() . '` AS `p` ON(`a`.`id` = `p`.`albumId`)' : '') . '
            WHERE `a`.`userId` = :userId AND
            ' . $condition['where'] . ' AND
            ' . (!empty($exclude) ? '`a`.`id` NOT IN(' . $this->dbo->mergeInClause($exclude) . ')' : '1');

        return $this->dbo->queryForColumn($sql, array_merge(
            array('userId' => $userId),
            $condition['params']
        ));
    }
    
     /**
     * Count albums for entity
     *
     * @param $entityId
     * @param $entityType
     * @return int
     */
    public function countEntityAlbums( $entityId, $entityType )
    {
        if ( !$entityId || !mb_strlen($entityType) )
        {
            return false;
        }

        $example = new OW_Example();
        $example->andFieldEqual('entityId', $entityId);
        $example->andFieldEqual('entityType', $entityType);

        return $this->countByExample($example);
    }

    /**
     * Get the list of user albums
     *
     * @param int $userId
     * @param int $page
     * @param int $limit
     * @param $exclude
     * @return array of PHOTO_BOL_PhotoAlbum
     */
    public function getUserAlbumList( $userId, $page, $limit, $exclude )
    {
        $first = ( $page - 1 ) * $limit;

        $condition = PHOTO_BOL_PhotoService::getInstance()->getQueryCondition('getUserAlbumList',
            array(
                'album' => 'a',
                'photo' => 'p'
            ),
            array(
                'userId' => $userId,
                'page' => $page,
                'limit' => $limit,
                'exclude' => $exclude
            )
        );

        $sql = 'SELECT `a`.*
            FROM `%s` AS `a`
                INNER JOIN `%s` AS `p`
                    ON(`p`.`albumId` = `a`.`id`)
                %s
            WHERE `a`.`userId` = :userId AND
                %s AND
                %s
            GROUP BY `a`.`id`
            LIMIT :first, :limit';
        $sql = sprintf($sql,
            $this->getTableName(),
            PHOTO_BOL_PhotoDao::getInstance()->getTableName(),
            $condition['join'],
            $condition['where'],
            !empty($exclude) ? '`a`.`id` NOT IN(' . $this->dbo->mergeInClause($exclude) . ')' : '1'
        );

        return $this->dbo->queryForObjectList($sql, $this->getDtoClassName(), array_merge(
            array(
                'userId' => $userId,
                'first' => (int) $first,
                'limit' => (int) $limit
            ),
            $condition['params']
        ));
    }
    
    public function findUserAlbumList( $userId, $first, $limit, array $exclude = array() )
    {
        $condition = PHOTO_BOL_PhotoService::getInstance()->getQueryCondition('findUserAlbumList',
            array(
                'album' => 'a',
                'photo' => 'p'
            ),
            array(
                'userId' => $userId,
                'first' => $first,
                'limit' => $limit,
                'exclude' => $exclude
            )
        );

        $sql = 'SELECT `a`.*
            FROM `' . $this->getTableName() . '` AS `a`
                INNER JOIN `' . PHOTO_BOL_PhotoDao::getInstance()->getTableName() . '` AS `p` ON(`p`.`albumId` = `a`.`id` AND `p`.`status` = :status)
                ' . $condition['join'] . '
            WHERE `a`.`' . self::USER_ID . '` = :userId ' .
                (count($exclude) !== 0 ? ' AND `a`.`id` NOT IN (' . implode(',', array_map('intval', $exclude)) . ')' : '') . ' AND
                ' . $condition['where'] . '
            GROUP BY `a`.`id`
            ORDER BY `a`.`id` DESC
            LIMIT :first, :limit';

        $params = array('userId' => $userId, 'status' => PHOTO_BOL_PhotoDao::STATUS_APPROVED, 'first' => (int)$first, 'limit' => (int)$limit);
        
        return $this->dbo->queryForList($sql, array_merge($params, $condition['params']));
    }
    
    /**
     * Get album list for entity
     *
     * @param $entityId
     * @param $entityType
     * @param int $page
     * @param int $limit
     * @return array of PHOTO_BOL_PhotoAlbum
     */
    public function getEntityAlbumList( $entityId, $entityType, $page, $limit )
    {
        $first = ( $page - 1 ) * $limit;

        $example = new OW_Example();
        $example->andFieldEqual('entityId', $entityId);
        $example->andFieldEqual('entityType', $entityType);
        $example->setLimitClause($first, $limit);

        return $this->findListByExample($example);
    }

    public function getUserAlbums( $userId, $offset, $limit )
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        $example->setLimitClause($offset, $limit);

        return $this->findListByExample($example);
    }

    public function getEntityAlbums( $entityId, $entityType, $offset, $limit )
    {
        $example = new OW_Example();
        $example->andFieldEqual('entityId', $entityId);
        $example->andFieldEqual('entityType', $entityType);
        $example->setLimitClause($offset, $limit);

        return $this->findListByExample($example);
    }
    
    public function getUserAlbumIdList( $userId )
    {
        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);

        return $this->findIdListByExample($example);
    }
  
    public function getEntityAlbumIdList( $entityId, $entityType )
    {
        $example = new OW_Example();
        $example->andFieldEqual('entityId', $entityId);
        $example->andFieldEqual('entityType', $entityType);

        return $this->findIdListByExample($example);
    }

    /**
     * Finds Photo album by album name
     *
     * @param string $name
     * @param int $userId
     * @return PHOTO_BOL_PhotoAlbum
     */
    public function findAlbumByName( $name, $userId )
    {
        $name = trim($name);

        $userId = (int) $userId;

        $example = new OW_Example();
        $example->andFieldEqual('name', $name);
        $example->andFieldEqual('userId', $userId);
        $example->setLimitClause(0, 1);

        return $this->findObjectByExample($example);
    }
    
    /**
     * Finds entity photo album by album name
     *
     * @param string $name
     * @param $entityId
     * @param $entityType
     * @return PHOTO_BOL_PhotoAlbum
     */
    public function findEntityAlbumByName( $name, $entityId, $entityType )
    {
        $name = trim($name);

        $entityId = (int) $entityId;

        $example = new OW_Example();
        $example->andFieldEqual('name', $name);
        $example->andFieldEqual('entityId', $entityId);
        $example->andFieldEqual('entityType', $entityType);
        $example->setLimitClause(0, 1);

        return $this->findObjectByExample($example);
    }
    
    /**
     * Get user albums for suggest field
     *
     * @param int $userId
     * @param string $query
     * @return array of PHOTO_Bol_PhotoAlbum
     */
    public function suggestUserAlbums( $userId, $query )
    {
        if ( !$userId )
            return false;

        $example = new OW_Example();
        $example->andFieldEqual('userId', $userId);
        $example->setOrder('`name` ASC');

        if ( strlen($query) )
            $example->andFieldLike('name', $query . '%');

        $example->setLimitClause(0, 10);

        return $this->findListByExample($example);
    }
    
    /**
     * Get entity albums for suggest field
     *
     * @param string $entityType
     * @param int $entityId
     * @param string $query
     * @return array of PHOTO_Bol_PhotoAlbum
     */
    public function suggestEntityAlbums( $entityType, $entityId, $query )
    {
        if ( !$entityId )
        {
            return false;
        }

        $example = new OW_Example();
        $example->andFieldEqual('entityId', $entityId);
        $example->andFieldEqual('entityType', $entityType);
        $example->setOrder('`name` ASC');

        if ( strlen($query) )
            $example->andFieldLike('name', $query . '%');

        $example->setLimitClause(0, 10);

        return $this->findListByExample($example);
    }

    /**
     * Find last albums ids
     *
     * @param int $offset
     * @param int $limit
     * @return array
     */
    public function findLastAlbumsIds( $offset, $limit )
    {
        $example = new OW_Example();
        $example->setOrder('createDatetime DESC');
        $example->setLimitClause((int) $offset, (int) $limit);

        return $this->findIdListByExample($example);
    }

    /**
     * Find latest albums authors ids
     *
     * @param integer $first
     * @param integer $count
     * @return array
     */
    public function findLatestAlbumsAuthorsIds($first, $count)
    {
        $sql = 'SELECT `' . self::USER_ID . '`
            FROM `' . $this->getTableName() . '`
            GROUP BY `' . self::USER_ID . '` ORDER BY `createDatetime` DESC LIMIT :f, :c';

        return $this->dbo->queryForColumnList($sql, array(
            'f' => (int) $first,
            'c' => (int) $count
        ));
    }

    /**
     * Get albums to be deleted
     *
     * @param int $limit
     * @return array
     */
    public function getAlbumsForDelete( $limit )
    {
        $example = new OW_Example();
        $example->setOrder('createDatetime ASC');
        $example->setLimitClause(0, (int)$limit);
        
        return $this->findIdListByExample($example);
    }
    
    public function findAlbumNameListByIdList( array $idList )
    {
        if ( count($idList) === 0 )
        {
            return array();
        }
        
        $sql = 'SELECT `id`, `' . self::NAME . '`, `' . self::USER_ID . '`
            FROM `' . $this->getTableName() . '`
            WHERE `id` IN (' . implode(',', array_map('intval', array_unique($idList))) . ')';
        
        $result = array();
        $resource = $this->dbo->queryForList($sql);
        
        foreach ( $resource as $row )
        {
            $result[$row['id']] = array('name' => $row[self::NAME], 'userId' => $row[self::USER_ID]);
        }
        
        return $result;
    }
    
    public function isAlbumOwner( $albumId, $userId )
    {
        if ( empty($albumId) || empty($userId) )
        {
            return FALSE;
        }
        
        $sql = 'SELECT COUNT(*)
            FROM `' . $this->getTableName() . '`
            WHERE `id` = :albumId AND `userId` = :userId';
        
        return (int)$this->dbo->queryForColumn($sql, array('albumId' => $albumId, 'userId' => $userId)) > 0;
    }
    
    public function findAlbumNameListByUserId( $userId, $excludeIdList = array() )
    {
        if ( empty($userId) )
        {
            return array();
        }
        
        $sql = 'SELECT `id`, `' . self::NAME . '`
            FROM `' . $this->getTableName() . '`
            WHERE `' . self::USER_ID . '` = :userId' . (count($excludeIdList) !== 0 ? ' AND `id` NOT IN (' . implode(',', array_map('intval', array_unique($excludeIdList))) . ')' : '') . '
            ORDER BY `' . self::NAME . '`';

        $result = array();
        $rows = $this->dbo->queryForList($sql, array('userId' => $userId));
        
        foreach ( $rows as $row )
        {
            $result[$row['id']] = $row[self::NAME];
        }

        return $result;
    }
}
