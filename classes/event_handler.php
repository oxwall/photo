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
 * 
 * 
 * @authors Egor Bulgakov <egor.bulgakov@gmail.com>, Kairat Bakitow <kainisoft@gmail.com>
 * @package ow_plugins.photo.classes
 * @since 1.5.3
 */
class PHOTO_CLASS_EventHandler
{
    /**
     * @var PHOTO_CLASS_EventHandler
     */
    private static $classInstance;

    const EVENT_ALBUM_ADD = 'photo.album_add';
    const EVENT_ALBUM_FIND = 'photo.album_find';
    const EVENT_ALBUM_DELETE = 'photo.album_delete';
    const EVENT_ENTITY_ALBUMS_FIND = 'photo.entity_albums_find';
    const EVENT_ENTITY_ALBUMS_COUNT = 'photo.entity_albums_count';
    
    const EVENT_ENTITY_PHOTOS_FIND = 'photo.entity_photos_find';
    const EVENT_ENTITY_PHOTOS_COUNT = 'photo.entity_photos_count';
    const EVENT_ENTITY_ALBUMS_DELETE = 'photo.entity_albums_delete';

    const EVENT_ON_ALBUM_ADD = 'photo.on_album_add';
    const EVENT_ON_ALBUM_EDIT = 'photo.on_album_edit';
    const EVENT_BEFORE_ALBUM_DELETE = 'photo.before_album_delete';

    const EVENT_PHOTO_ADD = 'photo.add';
    const EVENT_PHOTO_FIND = 'photo.find';
    const EVENT_PHOTO_FINDS = 'photo.finds';
    const EVENT_PHOTO_DELETE = 'photo.delete';
    const EVENT_ALBUM_PHOTOS_COUNT = 'photo.album_photos_count';
    const EVENT_ALBUM_PHOTOS_FIND = 'photo.album_photos_find';
    const EVENT_INIT_FLOATBOX = 'photo.init_floatbox';
    const EVENT_GET_PHOTO_VIEW_STATUS = 'photo.get_photo_view_status';
    const EVENT_GET_ADDPHOTO_URL = 'photo.getAddPhotoURL';
    const EVENT_ON_PHOTO_CONTENT_UPDATE = 'photo.onUpdateContent';

    const EVENT_ON_PHOTO_ADD = 'plugin.photos.add_photo';
    const EVENT_ON_PHOTO_EDIT = 'photo.after_edit';
    const EVENT_ON_PHOTO_DELETE = 'photo.after_delete';
    const EVENT_BEFORE_PHOTO_MOVE = 'photo.onBeforePhotoMove';
    const EVENT_BEFORE_PHOTO_DELETE = 'photo.onBeforeDelete';
    const EVENT_BEFORE_MULTIPLE_PHOTO_DELETE = 'photo.onBeforeMultiplePhotoDelete';
    const EVENT_AFTER_PHOTO_MOVE = 'photo.onAfterPhotoMove';
    const EVENT_CREATE_USER_ALBUM = 'photo.createUserAlbum';
    const EVENT_GET_MAIN_ALBUM = 'photo.getMainAlbum';
    const EVENT_ADD_SEARCH_DATA = 'photo.addSearchData';
    const EVENT_BACKGROUND_LOAD_PHOTO = 'photo.backgroundLoadPhoto';
    const EVENT_COLLECT_PHOTO_SUB_MENU = 'photo.collectSubMenu';
    
    const EVENT_SUGGEST_DEFAULT_ALBUM = 'photo.suggest_default_album';
    const EVENT_ON_FORM_READY = 'photo.form_ready';
    const EVENT_ON_FORM_COMPLETE = 'photo.form_complete';
    const EVENT_GET_UPLOAD_DATA = 'photo.upload_data';
    const EVENT_GET_ALBUM_COVER_URL = 'photo.get_cover';
    const EVENT_GET_ALBUM_NAMES = 'photo.get_album_names';

    /**
     * @return PHOTO_CLASS_EventHandler
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
     * @var PHOTO_BOL_PhotoAlbumService
     */
    private $albumService;

    /**
     * @var PHOTO_BOL_PhotoService
     */
    private $photoService;

    private function __construct()
    {
        $this->albumService = PHOTO_BOL_PhotoAlbumService::getInstance();
        $this->photoService = PHOTO_BOL_PhotoService::getInstance();
    }

    public function albumAdd( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( empty($params['name']) )
        {
            return false;
        }

        $albumName = trim($params['name']);
        $userId = !empty($params['userId']) ? (int) $params['userId'] : null;
        $entityId = !empty($params['entityId']) ? (int) $params['entityId'] : $userId;
        $entityType = !empty($params['entityType']) ? (int) $params['entityType'] : 'user';

        $album = $this->albumService->findEntityAlbumByName($albumName, $entityId, $entityType);
        
        if ( empty($album) && $entityType == "user" && !empty($userId) )
        {
            $album = $this->albumService->findAlbumByName($albumName, $userId);
        }

        if ( !empty($album) )
        {
            $data['albumId'] = $album->id;
            $e->setData($data);

            return $data;
        }

        $album = new PHOTO_BOL_PhotoAlbum();
        $album->name = $albumName;
        $album->userId = $userId;
        $album->entityId = $entityId;
        $album->entityType = $entityType;
        $album->createDatetime = time();

        $albumId = $this->albumService->addAlbum($album);

        $data['albumId'] = $albumId;
        $e->setData($data);

        return $data;
    }
    
    public function onAlbumAdd( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['id']) || ($album = $this->albumService->findAlbumById($params['id'])) === NULL )
        {
            return;
        }
        
        OW::getEventManager()->trigger(new OW_Event(self::EVENT_ADD_SEARCH_DATA,
            array(
                'entityId' => $album->id,
                'entityType' => PHOTO_BOL_SearchService::ENTITY_TYPE_ALBUM,
                'content' => $album->name . ' ' . $album->description
            ))
        );
    }
    
    public function onAlbumEdit( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['id']) || ($album = $this->albumService->findAlbumById($params['id'])) === NULL )
        {
            return;
        }
        
        OW::getEventManager()->trigger(new OW_Event(self::EVENT_ADD_SEARCH_DATA,
            array(
                'entityId' => $album->id,
                'entityType' => PHOTO_BOL_SearchService::ENTITY_TYPE_ALBUM,
                'content' => $album->name . ' ' . $album->description
            ))
        );
    }

    public function albumFind( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        $album = $this->findRequestedAlbum($params);

        if ( empty($album) )
        {
            return null;
        }

        $list = $this->prepareAlbums(array($album));

        $data = $list[$album->id];
        $e->setData($data);

        return $data;
    }

    public function albumDelete( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( empty($params['albumId']) )
        {
            return false;
        }

        $album = $this->albumService->findAlbumById($params['albumId']);

        if ( !$album )
        {
            return false;
        }

        $this->albumService->deleteAlbum($album->id);

        return $data;
    }

    public function entityAlbumsFind( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( empty($params['entityId']) )
        {
            return false;
        }

        $entityType = !empty($params['entityType']) ? $params['entityType'] : 'user';
        $offset = !empty($params['offset']) ? (int) $params['offset'] : 0;
        $limit = !empty($params['limit']) ? (int) $params['limit'] : OW::getConfig()->getValue('photo', 'photos_per_page');

        $albums = $this->albumService->findEntityAlbums($params['entityId'], $entityType, $offset, $limit);
        $list = $this->prepareAlbums($albums);
        $data['albums'] = $list;
        $e->setData($data);

        return $data;
    }
    
    public function entityAlbumsCount( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( empty($params['entityId']) )
        {
            return false;
        }

        $entityType = !empty($params['entityType']) ? $params['entityType'] : 'user';

        $data["count"] = $this->albumService->countEntityAlbums($params['entityId'], $entityType);

        $e->setData($data);
        
        return $data;
    }
    
    public function createUserAlbum( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['userId']) || ($user = BOL_UserService::getInstance()->findUserById($params['userId'])) === NULL )
        {
            return FALSE;
        }

        $albumName = !empty($params['name']) ? htmlspecialchars(trim($params['name'])) : OW::getLanguage()->text('photo', 'album_my_photos');
        
        if ( !($album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumByName($albumName, $user->id)) )
        {
            $album = new PHOTO_BOL_PhotoAlbum();
            $album->name = $albumName;
            $album->userId = $user->id;
            $album->entityId = !empty($params['entityId']) ? $params['entityId'] : $user->id;
            $album->entityType = !empty($params['entityType']) ? $params['entityType'] : 'user';
            $album->createDatetime = time();
            $album->description = !empty($params['description']) ? htmlspecialchars(trim($params['description'])) : '';
            
            PHOTO_BOL_PhotoAlbumService::getInstance()->addAlbum($album);
        }
        
        $event->setData(array('ablumId' => $album->id));
    }
    
    public function getMainAlbum( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['userId']) || ($user = BOL_UserService::getInstance()->findUserById($params['userId'])) === NULL )
        {
            return;
        }
        
        $albumName = OW::getLanguage()->text('photo', 'album_my_photos');
        $createAlbumEvent = new OW_Event(self::EVENT_CREATE_USER_ALBUM, array('userId' => $user->id, 'name' => $albumName));
        OW::getEventManager()->trigger($createAlbumEvent);
        
        $data = $createAlbumEvent->getData();
        $album = $this->albumService->findAlbumById($data['ablumId']);
        
        $photos = PHOTO_BOL_PhotoDao::getInstance()->getAlbumAllPhotos($album->id);
        $photoList = array();
        
        foreach ( $photos as $photo )
        {
            $dim = !empty($photo->dimension) ? $photo->dimension : FALSE;
            
            $photoList[$photo->id] = get_object_vars($photo);
            $photoList[$photo->id]['url'] = array();
            $photoList[$photo->id]['url'][PHOTO_BOL_PhotoService::TYPE_ORIGINAL] = $this->photoService->getPhotoUrlByPhotoInfo($photo->id, PHOTO_BOL_PhotoService::TYPE_ORIGINAL, $photoList[$photo->id]);
            $photoList[$photo->id]['url'][PHOTO_BOL_PhotoService::TYPE_MAIN] = $this->photoService->getPhotoUrlByPhotoInfo($photo->id, PHOTO_BOL_PhotoService::TYPE_MAIN, $photoList[$photo->id]);
            $photoList[$photo->id]['url'][PHOTO_BOL_PhotoService::TYPE_PREVIEW] = $this->photoService->getPhotoUrlByPhotoInfo($photo->id, PHOTO_BOL_PhotoService::TYPE_PREVIEW, $photoList[$photo->id]);
            $photoList[$photo->id]['url'][PHOTO_BOL_PhotoService::TYPE_SMALL] = $this->photoService->getPhotoUrlByPhotoInfo($photo->id, PHOTO_BOL_PhotoService::TYPE_PREVIEW, $photoList[$photo->id]);
            
            if ( $photo->hasFullsize && (bool)OW::getConfig()->getValue('photo', 'store_fullsize'))
            {
                $photoList[$photo->id]['url'][PHOTO_BOL_PhotoService::TYPE_FULLSCREEN] = $this->photoService->getPhotoUrlByPhotoInfo($photo->id, PHOTO_BOL_PhotoService::TYPE_FULLSCREEN, $photoList[$photo->id]);
            }
        }
        
        $event->setData(array(
            'album' => get_object_vars($album),
            'photoList' => $photoList
        ));
    }

    public function onPhotoAdd( OW_Event $event )
    {
        foreach ( $event->getParams() as $data )
        {
            PHOTO_BOL_SearchService::getInstance()->addSearchIndex(PHOTO_BOL_SearchService::ENTITY_TYPE_PHOTO, $data['photoId'], $data['description']);
        }
    }
    
    public function onAfterPhotoEdit( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['id']) || ($photo = $this->photoService->findPhotoById($params['id'])) === NULL )
        {
            return;
        }
        
        OW::getEventManager()->trigger(new OW_Event(self::EVENT_ADD_SEARCH_DATA,
            array(
                'entityId' => $photo->id,
                'entityType' => PHOTO_BOL_SearchService::ENTITY_TYPE_PHOTO,
                'content' => $photo->description
            ))
        );
    }
    
    public function photoAdd( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();
 
        if ( empty($params['albumId']) )
        {
            return false;
        }

        $addToFeed = !isset($params["addToFeed"]) || $params["addToFeed"];
        $status = empty($params["status"]) ? null : $params["status"];
        $silent = $status !== null;
        
        $album = $this->albumService->findAlbumById($params['albumId']);

        if ( !$album )
        {
            return false;
        }

        if ( empty($params['path']) || !file_exists($params['path']) )
        {
            return false;
        }

        $description = !empty($params['description']) ? $params['description'] : null;
        $tags = !empty($params['tags']) ? $params['tags'] : null;
        $angle = !empty($params['angle']) ? $params['angle'] : 0;
        $uploadKey = !empty($params['uploadKey']) ? $params['uploadKey'] : 0;
        
        $tmpPhotoService = PHOTO_BOL_PhotoTemporaryService::getInstance();
        
        if ( ($tmpId = $tmpPhotoService->addTemporaryPhoto($params['path'], $album->userId, 1)) )
        {
            $photo = $tmpPhotoService->moveTemporaryPhoto($tmpId, $album->id, $description, $tags, $angle, $uploadKey, $status);
            if ( $photo )
            {
                $data['photoId'] = $photo->id;

                if ( $album->userId && $addToFeed )
                {
                    //Newsfeed
                    $event = new OW_Event('feed.action', array(
                        'pluginKey' => 'photo',
                        'entityType' => 'photo_comments',
                        'entityId' => $photo->id,
                        'userId' => $album->userId
                    ));
                    OW::getEventManager()->trigger($event);
                }
                
                $this->photoService->createAlbumCover($album->id, array($photo));
                PHOTO_BOL_PhotoTemporaryService::getInstance()->deleteUserTemporaryPhotos($album->userId);
                
                $movedArray[] = array(
                    'addTimestamp' => time(), 
                    'photoId' => $photo->id, 
                    'description' => $photo->description, 
                    "status" => $photo->status, 
                    "silent" => $silent
                );
                
                $event = new OW_Event(PHOTO_CLASS_EventHandler::EVENT_ON_PHOTO_ADD, $movedArray);
                OW::getEventManager()->trigger($event);
            }
        }

        $e->setData($data);

        return $data;
    }

    public function photoFind( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( empty($params['photoId']) )
        {
            return false;
        }

        $photoId = (int) $params['photoId'];
        $photo = $this->photoService->findPhotoById($photoId);

        if ( !$photo )
        {
            return false;
        }

        $list = $this->preparePhotos(array($photo));

        $data['photo'] = $list[$photoId];
        $e->setData($data);

        return $data;
    }

    public function photoFinds( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['idList']) )
        {
            return false;
        }

        $photos = $this->photoService->findPhotoListByIdList($params['idList'], 1, count($params['idList']));

        if ( !$photos )
        {
            return false;
        }

        $list = $this->preparePhotos($photos);
        $event->setData(array(
            'photos' => $list
        ));

        return $event->getData();
    }

    public function photoDelete( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( empty($params['photoId']) )
        {
            return false;
        }

        $photo = $this->photoService->findPhotoById($params['photoId']);

        if ( !$photo )
        {
            return false;
        }

        $this->photoService->deletePhoto($photo->id);

        return $data;
    }
    
    public function onPhotoDelete( OW_Event $event )
    {
        $params = $event->getParams();
        
        PHOTO_BOL_SearchService::getInstance()->deleteSearchItem(PHOTO_BOL_SearchService::ENTITY_TYPE_PHOTO, $params['id']);

        OW::getEventManager()->trigger(
            new OW_Event(BOL_ContentService::EVENT_BEFORE_DELETE, array(
                'entityType' => PHOTO_CLASS_ContentProvider::ENTITY_TYPE,
                'entityId' => $params['id']
            ))
        );
    }

    public function albumPhotosCount( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['albumId']) )
        {
            return null;
        }

        $event->setData($this->photoService->countAlbumPhotos($params['albumId'], array()));

        return $event->getData();
    }

    /**
     * @param OW_Event $e
     * @return array
     */
    public function albumPhotosFind( OW_Event $e )
    {
        $params = $e->getParams();

        $album = $this->findRequestedAlbum($params);

        if ( empty($album) )
        {
            return false;
        }

        $offset = !empty($params['offset']) ? (int) $params['offset'] : 0;
        $limit = !empty($params['limit']) ? (int) $params['limit'] : OW::getConfig()->getValue('photo', 'photos_per_page');
        $listType = isset($params['listType']) ? $params['listType'] : 'latest';
        $privacy = isset($params['privacy']) ? $params['privacy'] : 'everybody';

        $photos = $this->photoService->findAlbumPhotoList($album->id, $listType, $offset, $limit, $privacy);

        $list = $this->preparePhotos($photos);
        $e->setData($list);

        return $list;
    }

    private function findRequestedAlbum( $params )
    {
        if ( empty($params['albumId']) )
        {
            if ( empty($params['userId']) || empty($params['albumTitle']) )
            {
                return null;
            }

            $album = $this->albumService->findAlbumByName($params['albumTitle'], $params['userId']);
        }
        else
        {
            $album = $this->albumService->findAlbumById($params['albumId']);
        }

        return $album;
    }
    
    public function entityPhotosFind( OW_Event $e )
    {
        $params = $e->getParams();

        if ( empty($params['entityId']) || empty($params['entityType']) )
        {
            return null;
        }

        $offset = !empty($params['offset']) ? (int) $params['offset'] : 0;
        $limit = !empty($params['limit']) ? (int) $params['limit'] : OW::getConfig()->getValue('photo', 'photos_per_page');
        $status = isset($params["status"]) ? $params["status"] : "approved";
        $privacy = isset($params['privacy']) || $params['privacy'] === null ? $params['privacy'] : 'everybody';

        $photos = $this->photoService->findEntityPhotoList($params['entityType'], $params['entityId'], $offset, $limit, $status, $privacy);
        
        $list = $this->preparePhotos($photos);
        $e->setData($list);

        return $list;
    }
    
    public function entityPhotosCount( OW_Event $e )
    {
        $params = $e->getParams();

        if ( empty($params['entityId']) || empty($params['entityType']) )
        {
            return null;
        }

        $status = isset($params["status"]) ? $params["status"] : "approved";

        $count = $this->photoService->countEntityPhotos($params['entityType'], $params['entityId'], $status);
        $e->setData($count);

        return $count;
    }
    
    public function entityAlbumsDelete( OW_Event $e )
    {
        $params = $e->getParams();

        if ( empty($params['entityId']) || empty($params['entityType']) )
        {
            return null;
        }

        $this->albumService->deleteEntityAlbums($params['entityId'], $params['entityType']);
    }
        
    private function prepareAlbums( array $albums )
    {
        if ( !count($albums) )
        {
            return null;
        }

        $list = array();
        foreach ( $albums as $album )
        {
            $id = $album->id;
            $username = BOL_UserService::getInstance()->getUserName($album->userId);

            $list[$id]['id'] = $id;
            $list[$id]['name'] = $album->name;
            $list[$id]['userId'] = $album->userId;
            $list[$id]['url'] = OW::getRouter()->urlForRoute('photo_user_album', array('user' => $username, 'album' => $album->id));
            $list[$id]['coverImage'] = PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->getAlbumCoverUrlByAlbumId($album->id);
            $list[$id]['photoCount'] = $this->albumService->countAlbumPhotos($album->id);
            $list[$id]['entityType'] = $album->entityType;
            $list[$id]['entityId'] = $album->entityId;
        }

        return $list;
    }

    private function preparePhotos( array $photos )
    {
        if ( !count($photos) )
        {
            return array();
        }

        $list = array();
        foreach ( $photos as $_photo )
        {
            $photo = (array)$_photo;
            $dimensions = array();
            
            if ( !empty($photo['dimension']) )
            {
                $dimensions = json_decode($photo['dimension'], true);
            }
            
            $id = $photo['id'];
            $album = $this->albumService->findAlbumById($photo['albumId']);
            $list[$id]['albumId'] = $photo['albumId'];
            $list[$id]['id'] = $photo['id'];
            $list[$id]['description'] = $photo['description'];
            $list[$id]['userId'] = $album->userId;
            $list[$id]['url'] = OW::getRouter()->urlForRoute('view_photo', array('id' => $id));
            $list[$id]['dimension'] = $dimensions;
            
            $list[$id]['photoUrl'] = $this->photoService->getPhotoUrlByPhotoInfo($id, PHOTO_BOL_PhotoService::TYPE_MAIN, $photo);
            $list[$id]['previewUrl'] = $this->photoService->getPhotoUrlByPhotoInfo($id, PHOTO_BOL_PhotoService::TYPE_PREVIEW, $photo);
            $list[$id]['smallUrl'] = $this->photoService->getPhotoUrlByPhotoInfo($id, PHOTO_BOL_PhotoService::TYPE_SMALL, $photo);
            $list[$id]['fullscreenUrl'] = $this->photoService->getPhotoUrlByPhotoInfo($id, PHOTO_BOL_PhotoService::TYPE_FULLSCREEN, $photo);
            $list[$id]['originalUrl'] = $this->photoService->getPhotoUrlByPhotoInfo($id, PHOTO_BOL_PhotoService::TYPE_ORIGINAL, $photo);
            $list[$id]['dto'] = $photo;
        }

        return $list;
    }


    public function initFloatbox( OW_Event $event )
    {
        static $isInitialized = FALSE;
        
        if ( $isInitialized )
        {
            return;
        }
        
        $params = $event->getParams();
        $layout = (!empty($params['layout']) && in_array($params['layout'], array('page', 'floatbox'))) ? $params['layout'] : 'floatbox';
        
        $document = OW::getDocument();
        $plugin = OW::getPluginManager()->getPlugin('photo');
        
        $document->addStyleSheet($plugin->getStaticCssUrl() . 'photo_floatbox.css');
        $document->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'jquery-ui.min.js');
        $document->addScript($plugin->getStaticJsUrl() . 'slider.min.js', 'text/javascript', 1000000);
        $document->addScript($plugin->getStaticJsUrl() . 'utils.js');
        $document->addScript($plugin->getStaticJsUrl() . 'photo.js');

        $language = OW::getLanguage();
        
        $language->addKeyForJs('photo', 'tb_edit_photo');
        $language->addKeyForJs('photo', 'confirm_delete');
        $language->addKeyForJs('photo', 'mark_featured');
        $language->addKeyForJs('photo', 'remove_from_featured');
        $language->addKeyForJs('photo', 'rating_total');
        $language->addKeyForJs('photo', 'rating_your');
        $language->addKeyForJs('photo', 'of');
        $language->addKeyForJs('photo', 'album');
        $language->addKeyForJs('base', 'rate_cmp_owner_cant_rate_error_message');
        $language->addKeyForJs('base', 'rate_cmp_auth_error_message');
        $language->addKeyForJs('photo', 'slideshow_interval');
        $language->addKeyForJs('photo', 'pending_approval');

        $viewEvent = new OW_Event(self::EVENT_GET_PHOTO_VIEW_STATUS, $params);
        OW::getEventManager()->trigger($viewEvent);
        $photoViewStatus = $viewEvent->getData();
        
        $document->addScriptDeclarationBeforeIncludes(
            UTIL_JsGenerator::composeJsString('
                ;window.photoViewParams = Object.freeze({$params});',
                array(
                    'params' => array(
                        'ajaxResponder' => OW::getRouter()->urlFor('PHOTO_CTRL_Photo', 'ajaxResponder'),
                        'rateUserId' => OW::getUser()->getId(),
                        'layout' => $layout,
                        'isClassic' => (bool)OW::getConfig()->getValue('photo', 'photo_view_classic'),
                        'urlHome' => OW_URL_HOME,
                        'isDisabled' => empty($photoViewStatus['available']),
                        'isEnableFullscreen' => (bool)OW::getConfig()->getValue('photo', 'store_fullsize'),
                        'tagUrl' => OW::getRouter()->urlForRoute('view_tagged_photo_list', array('tag' => '-tag-'))
                    )
                )
            )
        );
        
        $document->addOnloadScript(';window.photoView.init();');
        
        $cmp = new PHOTO_CMP_PhotoFloatbox($layout, $photoViewStatus);
        $document->appendBody($cmp->render());
        
        $isInitialized = TRUE;
    }

    /**
     * @param BASE_CLASS_EventCollector $event
     */
    public function addNewContentItem( BASE_CLASS_EventCollector $event )
    {
        $url = OW::getEventManager()->call('photo.getAddPhotoURL');

        if ( $url !== false )
        {
            $resultArray = array(
                BASE_CMP_AddNewContent::DATA_KEY_ICON_CLASS => 'ow_ic_picture',
                BASE_CMP_AddNewContent::DATA_KEY_URL => 'javascript:' . $url . '()',
                BASE_CMP_AddNewContent::DATA_KEY_LABEL => OW::getLanguage()->text('photo', 'photo')
            );

            $event->add($resultArray);
        }
    }

    /**
     * @param BASE_CLASS_EventCollector $event
     */
    public function addQuickLink( BASE_CLASS_EventCollector $event )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return;
        }

        $service = PHOTO_BOL_PhotoAlbumService::getInstance();
        $userId = OW::getUser()->getId();
        $username = OW::getUser()->getUserObject()->getUsername();

        $albumCount = (int) $service->countUserAlbums($userId, null, true);

        if ( $albumCount > 0 )
        {
            $event->add(array(
                BASE_CMP_QuickLinksWidget::DATA_KEY_LABEL => OW::getLanguage()->text('photo', 'my_albums'),
                BASE_CMP_QuickLinksWidget::DATA_KEY_URL => OW::getRouter()->urlForRoute('photo_user_albums', array('user' => $username)),
                BASE_CMP_QuickLinksWidget::DATA_KEY_COUNT => $albumCount,
                BASE_CMP_QuickLinksWidget::DATA_KEY_COUNT_URL => OW::getRouter()->urlForRoute('photo_user_albums', array('user' => $username))
            ));
        }
    }

    /**
     * @param BASE_CLASS_EventCollector $event
     */
    public function adsEnabled( BASE_CLASS_EventCollector $event )
    {
        $event->add('photo');
    }

    /**
     * @param BASE_CLASS_EventCollector $event
     */
    public function addAuthLabels( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(
            array(
                'photo' => array(
                    'label' => $language->text('photo', 'auth_group_label'),
                    'actions' => array(
                        'upload' => $language->text('photo', 'auth_action_label_upload'),
                        'view' => $language->text('photo', 'auth_action_label_view'),
                        'add_comment' => $language->text('photo', 'auth_action_label_add_comment')
                    )
                )
            )
        );
    }

    /**
     * @param OW_Event $event
     */
    public function onUserUnregister( OW_Event $event )
    {
        $params = $event->getParams();

        if ( !isset($params['deleteContent']) || !(bool) $params['deleteContent'] )
        {
            return;
        }

        $userId = (int) $params['userId'];

        if ( $userId > 0 )
        {
            PHOTO_BOL_PhotoAlbumService::getInstance()->deleteUserAlbums($userId);
        }
    }

    /**
     * @param BASE_CLASS_EventCollector $event
     */
    public function addPrivacyAction( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();

        $action = array(
            'key' => 'photo_view_album',
            'pluginKey' => 'photo',
            'label' => $language->text('photo', 'privacy_action_view_album'),
            'description' => '',
            'defaultValue' => 'everybody'
        );

        $event->add($action);
    }

    /**
     * @param OW_Event $e
     */
    public function onChangePrivacy( OW_Event $e )
    {
        $params = $e->getParams();
        $userId = (int) $params['userId'];

        $actionList = $params['actionList'];

        if ( empty($actionList['photo_view_album']) )
        {
            return;
        }

        PHOTO_BOL_PhotoAlbumService::getInstance()->updatePhotosPrivacy($userId, $actionList['photo_view_album']);
    }

    /**
     * @param BASE_CLASS_EventCollector $e
     */
    public function collectNotificationActions( BASE_CLASS_EventCollector $e )
    {
        $e->add(array(
            'section' => 'photo',
            'action' => 'photo-add_comment',
            'sectionIcon' => 'ow_ic_picture',
            'sectionLabel' => OW::getLanguage()->text('photo', 'email_notifications_section_label'),
            'description' => OW::getLanguage()->text('photo', 'email_notifications_setting_comment'),
            'selected' => true
        ));
    }

    /**
     * @param OW_Event $event
     */
    public function notifyOnNewComment( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['entityType']) || $params['entityType'] !== 'photo_comments' )
        {
            return;
        }

        $entityId = $params['entityId'];
        $userId = $params['userId'];
        $commentId = $params['commentId'];

        $photoService = PHOTO_BOL_PhotoService::getInstance();
        $userService = BOL_UserService::getInstance();
        $ownerId = $photoService->findPhotoOwner($entityId);

        if ( $ownerId != $userId )
        {
            $params = array(
                'pluginKey' => 'photo',
                'entityType' => 'photo_add_comment',
                'entityId' => $commentId,
                'action' => 'photo-add_comment',
                'userId' => $ownerId,
                'time' => time()
            );

            $comment = BOL_CommentService::getInstance()->findComment($commentId);
            $url = OW::getRouter()->urlForRoute('view_photo', array('id' => $entityId));
            $avatars = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($userId));

            $data = array(
                'avatar' => $avatars[$userId],
                'string' => array(
                    'key' => 'photo+email_notifications_comment',
                    'vars' => array(
                        'userName' => $userService->getDisplayName($userId),
                        'userUrl' => $userService->getUserUrl($userId),
                        'photoUrl' => $url
                    )
                ),
                'content' => $comment->getMessage(),
                'url' => $url,
                'contentImage' => $photoService->getPhotoUrlByPhotoInfo($entityId, PHOTO_BOL_PhotoService::TYPE_SMALL)
            );

            $event = new OW_Event('notifications.add', $params, $data);
            OW::getEventManager()->trigger($event);
        }
    }

    public function photoContentFilter( BASE_CLASS_QueryBuilderEvent $event )
    {
        $params = $event->getParams();
        if ($params['type'] == 'photo_comments' || $params['type'] == 'photo_rates')
        {
            if ($params['type'] == 'photo_rates')
            {
                $params['listType'] = 'toprated';
                $aliases = array('alias' => 'r');
            }
            elseif ($params['type'] == 'photo_comments')
            {
                $params['listType'] = 'most_discussed';
                $aliases = array('alias' => 'ce');
            }

            $join = 'INNER JOIN `' . PHOTO_BOL_PhotoDao::getInstance()->getTableName() . '` AS `ph` ON (`'. $aliases['alias'] .'`.`entityId` = `ph`.`id`)
            INNER JOIN `' . PHOTO_BOL_PhotoAlbumDao::getInstance()->getTableName() . '` AS `a` ON (`ph`.`albumId` = `a`.`id`)';

            $event->addJoin($join);
            $event->addWhere('`ph`.`status` = \'approved\'');
        }
    }

    public function feedBeforeStatusUpdate( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['type'] == 'photo' )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');

            if ( $status['status'] != BOL_AuthorizationService::STATUS_AVAILABLE)
            {
                return;
            }

            $userId = OW::getUser()->getId();
            $url = $params['data']['url'];
            
            $tmpFile = OW::getPluginManager()->getPlugin('photo')->getPluginFilesDir() . md5($userId . time()) . basename($url);
            copy($url, $tmpFile);
            
            if ( !file_exists($tmpFile) )
            {
                return;
            }
            
            $albumName = OW::getLanguage()->text('photo', 'newsfeed_album');
            
            $event = new OW_Event(self::EVENT_CREATE_USER_ALBUM, array('userId' => $userId, 'name' => $albumName));
            OW::getEventManager()->trigger($event);
            
            $p = $event->getData();
            
            if ( empty($p['ablumId']) )
            {
                @unlink($tmpFile);
                
                return;
            }
            
            PHOTO_BOL_PhotoTemporaryService::getInstance()->deleteUserTemporaryPhotos($userId);
            
            $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($p['ablumId']);
            $desc = $params['status'];
            
            if ( ($tmpId = PHOTO_BOL_PhotoTemporaryService::getInstance()->addTemporaryPhoto($tmpFile, $userId)) )
            {
                $photo = PHOTO_BOL_PhotoTemporaryService::getInstance()->moveTemporaryPhoto($tmpId, $album->id, $desc);
                PHOTO_BOL_PhotoTemporaryService::getInstance()->deleteTemporaryPhoto($tmpId);
                
                BOL_AuthorizationService::getInstance()->trackAction('photo', 'upload', NULL, array('checkInterval' => FALSE));
                
                $this->photoService->createAlbumCover($album->id, array($photo));

                $albumUrl = OW::getRouter()->urlForRoute('photo_user_album', array(
                    'user' => BOL_UserService::getInstance()->getUserName($album->userId),
                    'album' => $album->id
                ));

                $data = array(
                    'photoIdList' => array($photo->id),
                    'string' => array(
                        'key' => 'photo+feed_single_description',
                        'vars' => array(
                            'number' => 1,
                            'albumUrl' => $albumUrl,
                            'albumName' => $album->name
                        )
                    )
                );

                if ( !empty($photo->description) )
                {
                    $data['status'] = $photo->description;
                }
                
                $eventParams = array(
                    'pluginKey' => 'photo',
                    'entityType' => 'photo_comments',
                    'entityId' => $photo->id,
                    'userId' => $album->userId,
                    'postOnUserFeed' => false,
                    'feedType' => $params['feedType'],
                    'feedId' => $params['feedId']
                );
                
                if ( !empty($params['visibility']) )
                {
                    $eventParams['visibility'] = $params['visibility'];
                }
                
                OW::getEventManager()->trigger(new OW_Event('feed.action', $eventParams, $data));

                $movedArray = array(array('addTimestamp' => time(), 'photoId' => $photo->id, 'description' => $photo->description));
                OW::getEventManager()->trigger(new OW_Event(PHOTO_CLASS_EventHandler::EVENT_ON_PHOTO_ADD, $movedArray));

                $status = $this->photoService->findPhotoById($photo->id)->status;

                if ( $status == PHOTO_BOL_PhotoDao::STATUS_APPROVAL )
                {
                    $e->setData(array('message' => OW::getLanguage()->text('photo', 'photo_uploaded_pending_approval')));
                }
                else
                {
                    $e->setData(array('entityType' => 'photo_comments', 'entityId' => $photo->id));
                }
            }
            
            @unlink($tmpFile);
        }
    }

    public function feedOnEntityAction( OW_Event $e )
    {
        $params = $e->getParams();
        $data = $e->getData();

        if ( !in_array($params['entityType'], array('photo_comments', 'multiple_photo_upload')) )
        {
            return;
        }

        $photoService = PHOTO_BOL_PhotoService::getInstance();
        $albumService = PHOTO_BOL_PhotoAlbumService::getInstance();
        $photoId = !empty($data['photoIdList']) ? $data['photoIdList'][0] : $params['entityId'];
        $photo = $photoService->findPhotoById($photoId);
        if ( !$photo )
        {
            return;
        }

        $album = $albumService->findAlbumById($photo->albumId);
        if ( !$album )
        {
            return;
        }

        $info = array('route' => array(
            'textKey' => 'photo+album',
            'label' =>  UTIL_String::truncate(strip_tags($album->name), 100, '...'),
            'routeName' => 'photo_user_album',
            'vars' => array(
                'user' => BOL_UserService::getInstance()->getUserName($params['userId']),
                'album' => $album->id
            )
        ));

        $entityType = $params['entityType'];
        if ( $params['entityType'] == 'multiple_photo_upload' && count($data['photoIdList']) == 1 )
        {
            $data['params'] = array(
                'entityType' => 'photo_comments',
                'entityId' => $data['photoIdList'][0],
                'merge' => array(
                    'entityType' => 'multiple_photo_upload',
                    'entityId' => $params['entityId']
                )
            );
            $entityType = 'photo_comments';
        }
        
        $vars = array();
        
        if ( !empty($data['status']) )
        {
            $vars['status'] = $data['status'];
        }
        
        $actionFormat = null;
        
        if ( isset($data["content"]) && is_array($data["content"]) )
        {
            $vars = empty($data["content"]["vars"]) ? array() : $data["content"]["vars"];
            $actionFormat = empty($data["content"]["format"]) ? null : $data["content"]["format"];
        }

        switch ( $entityType )
        {
            case 'multiple_photo_upload':
                $format = 'image_list';
                $photoIdList = array_slice($data['photoIdList'], 0, PHOTO_BOL_PhotoService::FORMAT_LIST_LIMIT);
                $list = array();
                
                foreach ( $photoIdList as $id )
                {
                    $photo = $photoService->findPhotoById($id);

                    if ( !$photo )
                    {
                        continue;
                    }

                    $list[] = array(
                        "image" => $photoService->getPhotoUrlByPhotoInfo($id, PHOTO_BOL_PhotoService::TYPE_PREVIEW),
                        "url" => array("routeName" => "view_photo", "vars" => array('id' => $id)),
                        "title" => $photo->description
                    );
                }

                $vars["list"] = $list;
                $data['features'] = array('likes');
                break;

            case 'photo_comments':
                $format = 'image';
                
                if ( !empty($photo->dimension) )
                {
                    $type = PHOTO_BOL_PhotoService::TYPE_PREVIEW;
                }
                else
                {
                    $type = PHOTO_BOL_PhotoService::TYPE_MAIN;
                }

                $vars["image"] = $photoService->getPhotoUrlByPhotoInfo($photo->id, $type, get_object_vars($photo));
                $vars["url"] = array("routeName" => "view_photo", "vars" => array('id' => $photoId));
                $vars["title"] = $photo->description;
                break;

            default:
                return;
        }

        $vars['info'] = $info;
        
        if ( !empty($actionFormat) )
        {
            $format = $actionFormat;
        }
        
        $data['content'] = array('format' => $format, 'vars' => $vars);
        
        $data['view'] = array('iconClass' => 'ow_ic_picture');

        $e->setData($data);
    }
    
    /**
     * @param OW_Event $event
     */
    public function feedOnItemRender( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();
        $autoId = $params['autoId'];
        $dimension = array();

        switch ( $params['action']['entityType'] )
        {
            case 'photo_comments':
                $photoId = !empty($data['photoIdList']) ? $data['photoIdList'][0] : $params['action']['entityId'];
                
                if ( ($photo = $this->photoService->findPhotoById($photoId)) !== NULL && !empty($photo->dimension) )
                {
                    $dimension[$photoId] = json_decode($photo->dimension);
                }
                
                OW::getDocument()->addOnloadScript(
                    UTIL_JsGenerator::composeJsString('$(".ow_newsfeed_item_picture a", "#" + {$autoId}).on("click", function( event )
                        {
                            event.preventDefault();
                            var dimension = {$dimension}, _data = {}, photoId = {$photoId};

                            if ( dimension.hasOwnProperty(photoId) && dimension[photoId].main )
                            {
                                _data.main = dimension[photoId].main;
                            }
                            else
                            {
                                var img = $(this).find("img")[0];
                                _data.main = [img.naturalWidth, img.naturalHeight];
                            }

                            _data.mainUrl = {$url};
                            window.photoView.setId(photoId, "latest", null, _data, {$photo});
                        });',
                        array(
                            'autoId' => $autoId,
                            'dimension' => $dimension,
                            'photoId' => $photoId,
                            'url' => $this->photoService->getPhotoUrlByPhotoInfo($photo->id, PHOTO_BOL_PhotoService::TYPE_PREVIEW, get_object_vars($photo)),
                            'photo' => array(
                                'id' => $photo->id,
                                'albumId' => $photo->albumId
                            )
                        )
                    )
                );
                break;

            case 'multiple_photo_upload':
                $photos = array();

                if ( !empty($params['action']['format']) )
                {
                    $photoList = PHOTO_BOL_PhotoDao::getInstance()->findByIdList(array_slice($data['photoIdList'], 0, PHOTO_BOL_PhotoService::FORMAT_LIST_LIMIT));

                    foreach ( $photoList as $photo )
                    {
                        $photos[$photo->id] = array(
                            'id' => $photo->id,
                            'albumId' => $photo->albumId
                        );

                        if ( !empty($photo->dimension) )
                        {
                            $dimension[$photo->id] = json_decode($photo->dimension);
                        }
                    }
                }
                
                OW::getDocument()->addOnloadScript(
                    UTIL_JsGenerator::composeJsString('$(".ow_newsfeed_content a[class!=photo_view_more]", "#" + {$autoId}).on("click", function( event )
                        {
                            event.preventDefault();
                            var dimension = {$dimension}, _data = {};
                            var match = this.pathname.match(/\d+$/);
                            var photoId = +match[0];
                            var url = $(this).attr("data-image");
                            var photos = {$photos};

                            if ( dimension.hasOwnProperty(photoId) && dimension[photoId].main )
                            {
                                _data.main = dimension[photoId].main;
                            }
                            else
                            {
                                var img = new Image();
                                img.src = url;
                                _data.main = [img.naturalWidth, img.naturalHeight];
                            }

                            _data.mainUrl = url;
                            window.photoView.setId(photoId, "latest", null, _data, photos[photoId] );
                        });',
                        array(
                            'autoId' => $autoId,
                            'dimension' => $dimension,
                            'photos' => $photos
                        )
                    )
                );
                break;

            default: return;
        }
        
        OW::getEventManager()->trigger(new OW_Event(self::EVENT_INIT_FLOATBOX));
    }
    
    public function onBeforeAlbumDelete( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['id']) || ($album = $this->albumService->findAlbumById($params['id'])) === NULL )
        {
            return;
        }

        foreach ( $this->photoService->findDistinctPhotoUploadKeyByAlbumId($album->id) as $photo )
        {
            $this->photoService->feedDeleteItem('photo_comments', $photo->id);
            $this->photoService->feedDeleteItem('multiple_photo_upload', $photo->uploadKey);
        }
        
        PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->deleteCoverByAlbumId($album->id);
        PHOTO_BOL_SearchService::getInstance()->deleteSearchItem(PHOTO_BOL_SearchService::ENTITY_TYPE_ALBUM, $album->id);
    }

    public function onBeforePhotoDelete( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['id']) || ($photo = $this->photoService->findPhotoById($params['id'])) === NULL || ($album = $this->albumService->findAlbumById($photo->albumId)) == NULL )
        {
            return;
        }
        
        if ( $this->albumService->isNewsfeedAlbum($album) )
        {
            $this->photoService->feedDeleteItem('photo_comments', $photo->id);
            
            return;
        }

        if ( $photo->uploadKey )
        {
            $this->photoService->feedDeleteItem('photo_comments', $photo->id);
            $this->photoService->feedDeleteItem('multiple_photo_upload', $photo->uploadKey);
            
            $photos = $this->photoService->getPhotoListByUploadKey($photo->uploadKey, array($photo->id));

            if ( empty($photos) )
            {
                return;
            }
            
            if ( count($photos) === 1 )
            {
                $this->photoService->triggerNewsfeedEventOnSinglePhotoAdd($album, $photos[0], FALSE);
            }
            else
            {
                $this->photoService->triggerNewsfeedEventOnMultiplePhotosAdd($album, $photos, FALSE);
            }
        }
    }
    
    public function onBeforeMultiplePhotoDelete( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['albumId']) || empty($params['photoIdList']) || ($album = $this->albumService->findAlbumById($params['albumId'])) === NULL )
        {
            return;
        }
        
        if ( $this->albumService->isNewsfeedAlbum($album) )
        {
            foreach ( $params['photoIdList'] as $photoId )
            {
                $this->photoService->feedDeleteItem('photo_comments', $photoId);
            }
            
            return;
        }
        
        $photo = $this->photoService->findPhotoById($params['photoIdList'][0]);

        $this->photoService->feedDeleteItem('photo_comments', $photo->id);
        $this->photoService->feedDeleteItem('multiple_photo_upload', $photo->uploadKey);

        $photos = PHOTO_BOL_PhotoDao::getInstance()->getAlbumAllPhotos($album->id, $params['photoIdList']);
        
        if ( empty($photos) )
        {
            return;
        }
        
        if ( count($photos) === 1 )
        {
            $this->photoService->triggerNewsfeedEventOnSinglePhotoAdd($album, $photos[0], FALSE);
        }
        else
        {
            $this->photoService->triggerNewsfeedEventOnMultiplePhotosAdd($album, $photos, FALSE);
        }
    }

    /**
     * @param BASE_CLASS_EventCollector $event
     */
    public function feedCollectConfigurableActivity( BASE_CLASS_EventCollector $event )
    {
        $language = OW::getLanguage();
        $event->add(array(
            'label' => $language->text('photo', 'feed_content_label'),
            'activity' => array('*:photo_comments', '*:multiple_photo_upload')
        ));
    }

    /**
     * @param BASE_CLASS_EventCollector $event
     */
    public function feedCollectPrivacy( BASE_CLASS_EventCollector $event )
    {
        $event->add(array('create:photo_comments,create:multiple_photo_upload', 'photo_view_album'));
    }

    /**
     * @param OW_Event $event
     */
    public function feedAfterCommentAdd( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != 'photo_comments' )
        {
            return;
        }

        $service = PHOTO_BOL_PhotoService::getInstance();
        $photo = $service->findPhotoById($params['entityId']);
        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);
        $userId = $album->userId;

        if ( $userId == $params['userId'] )
        {
            $string = array('key' => 'photo+feed_activity_owner_photo_string');
        }
        else
        {
            $userName = BOL_UserService::getInstance()->getDisplayName($userId);
            $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
            $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';
            $string = array('key' => 'photo+feed_activity_photo_string', 'vars' => array('user' => $userEmbed));
        }

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
            'activityType' => 'comment',
            'activityId' => $params['commentId'],
            'entityId' => $params['entityId'],
            'entityType' => $params['entityType'],
            'userId' => $params['userId'],
            'pluginKey' => 'photo'
        ), array(
            'string' => $string
        )));
    }

    /**
     * @param OW_Event $event
     */
    public function feedAfterLikeAdded( OW_Event $event )
    {
        $params = $event->getParams();

        if ( $params['entityType'] != 'photo_comments' )
        {
            return;
        }

        $service = PHOTO_BOL_PhotoService::getInstance();
        $photo = $service->findPhotoById($params['entityId']);
        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);
        $userId = $album->userId;

        $userName = BOL_UserService::getInstance()->getDisplayName($userId);
        $userUrl = BOL_UserService::getInstance()->getUserUrl($userId);
        $userEmbed = '<a href="' . $userUrl . '">' . $userName . '</a>';

        if ( $params['userId'] == $userId )
        {
            $string = array('key' => 'photo+feed_activity_owner_photo_like');
        }
        else
        {
            $string = array('key' => 'photo+feed_activity_photo_string_like', 'vars' => array('user' => $userEmbed));
        }

        OW::getEventManager()->trigger(new OW_Event('feed.activity', array(
            'activityType' => 'like',
            'activityId' => $params['userId'],
            'entityId' => $params['entityId'],
            'entityType' => $params['entityType'],
            'userId' => $params['userId'],
            'pluginKey' => 'photo'
        ), array(
            'string' => $string
        )));
    }

    public function sosialSharingGetPhotoInfo( OW_Event $event )
    {
        $params = $event->getParams();
        $data = $event->getData();
        $service = PHOTO_BOL_PhotoService::getInstance();

        $data['display'] = false;
        
        if ( empty($params['entityId']) )
        {
            return;
        }
        
        if ( $params['entityType'] == 'photo' )
        {
            if ( !BOL_AuthorizationService::getInstance()->isActionAuthorizedForGuest('photo', 'view') )
            {
                $event->setData($data);
                return;
            }

            $photo = $service->findPhotoById($params['entityId']);
            $data['display'] = $photo->privacy == 'everybody';

            $event->setData($data);
        }
        else if ( $params['entityType'] == 'photo_album' )
        {
            if ( !BOL_AuthorizationService::getInstance()->isActionAuthorizedForGuest('photo', 'view') )
            {
                $event->setData($data);
                return;
            }

            $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($params['entityId']);
            $list = $service->findAlbumPhotoList($params['entityId'], 'latest', 0, 500);

            foreach ( $list as $photo )
            {
                if ( $photo['privacy'] == 'everybody' )
                {
                    $data['image'] = $service->getPhotoUrl($photo['id']);
                    $data['title'] = $album->name;
                    $data['display'] = true;
                    break;
                }
            }
            
            $event->setData($data);
        }
    }
    
    public function onBeforePhotoMove( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['fromAlbum']) || empty($params['photoIdList']) )
        {
            return;
        }

        $fromAlbum = $this->albumService->findAlbumById($params['fromAlbum']);
        $fromAlbumLastPhoto = PHOTO_BOL_PhotoDao::getInstance()->getLastPhoto($params['fromAlbum']);
        
        $toAlbum = $this->albumService->findAlbumById($params['toAlbum']);
        $toAlbumLastPhoto = PHOTO_BOL_PhotoDao::getInstance()->getLastPhoto($params['toAlbum']);
        
        if ( $fromAlbumLastPhoto )
        {
            $this->photoService->feedDeleteItem('photo_comments', $fromAlbumLastPhoto->id);
            $this->photoService->feedDeleteItem('multiple_photo_upload', $fromAlbumLastPhoto->uploadKey);
        }
        
        if ( $toAlbumLastPhoto )
        {
            $this->photoService->feedDeleteItem('photo_comments', $toAlbumLastPhoto->id);
            $this->photoService->feedDeleteItem('multiple_photo_upload', $toAlbumLastPhoto->uploadKey);
        }
        
        if ( empty($params['toAlbum']) )
        {
            $photoIdList = PHOTO_BOL_PhotoDao::getInstance()->findPhotoIdListByAlbumId($fromAlbum->id);
            $count = count($photoIdList);
            $user = BOL_UserService::getInstance()->findUserById($fromAlbum->userId);
            $albumUrl = OW::getRouter()->urlForRoute('photo.user_photos', array('user' => $user->username));
            
            $event = new OW_Event('feed.action', array(
                'pluginKey' => 'photo',
                'entityType' => $count === 1 ? 'photo_comments' : 'multiple_photo_upload',
                'entityId' => $fromAlbumLastPhoto->uploadKey,
                'userId' => $user->id
            ), array(
                'photoIdList' => $photoIdList,
                'string' => array(
                    'key' => 'photo+feed_move_photo_descriptions',
                    'vars' => array('number' => $count, 'albumUrl' => $albumUrl)
                ),
                'features' => array('likes'),
                'content' => '',
                'view' => array('iconClass' => 'ow_ic_picture')
            ));

            OW::getEventManager()->trigger($event);
        }
        else
        {
            $fromPhotoIdList = PHOTO_BOL_PhotoDao::getInstance()->findPhotoIdListByAlbumId($fromAlbum->id, $params['photoIdList']);
            
            if ( empty($fromPhotoIdList) )
            {
                $photo = $this->photoService->findPhotoById($params['photoIdList'][0]);

                $this->photoService->feedDeleteItem('photo_comments', $photo->id);
                $this->photoService->feedDeleteItem('photo_comments', $photo->uploadKey);
                $this->photoService->feedDeleteItem('multiple_photo_upload', $photo->uploadKey);
            }
            else
            {
                $fromCount = count($fromPhotoIdList);
                $fromEntityType = $fromCount === 1 ? 'photo_comments' : 'multiple_photo_upload';
                $fromAlbumUrl = OW::getRouter()->urlForRoute('photo_user_album', array(
                    'user' => BOL_UserService::getInstance()->getUserName($fromAlbum->userId),
                    'album' => $fromAlbum->id
                ));

                $event = new OW_Event('feed.action', array(
                    'pluginKey' => 'photo',
                    'entityType' => $fromEntityType,
                    'entityId' => $fromAlbumLastPhoto->uploadKey,
                    'userId' => $fromAlbum->userId
                ), array(
                    'photoIdList' => $fromPhotoIdList,
                    'string' => array(
                        'key' => 'photo+feed_multiple_descriptions',
                        'vars' => array(
                            'number' => $fromCount,
                            'albumUrl' => $fromAlbumUrl,
                            'albumName' => $fromAlbum->name
                        )
                    ),
                    'features' => array('likes'),
                    'content' => '',
                    'view' => array('iconClass' => 'ow_ic_picture')
                ));

                OW::getEventManager()->trigger($event);
            }
            
            if ( $toAlbumLastPhoto )
            {
                $toPhotoIdList = array_merge(
                    PHOTO_BOL_PhotoDao::getInstance()->findPhotoIdListByAlbumId($toAlbum->id),
                    $params['photoIdList']
                );
                $toAlbumUrl = OW::getRouter()->urlForRoute('photo_user_album', array(
                    'user' => BOL_UserService::getInstance()->getUserName($toAlbum->userId),
                    'album' => $toAlbum->id
                ));

                $event = new OW_Event('feed.action', array(
                    'pluginKey' => 'photo',
                    'entityType' => 'multiple_photo_upload',
                    'entityId' => $toAlbumLastPhoto->uploadKey,
                    'userId' => $toAlbum->userId
                ), array(
                    'photoIdList' => $toPhotoIdList,
                    'string' => array(
                        'key' => 'photo+feed_multiple_descriptions',
                        'vars' => array(
                            'number' => count($toPhotoIdList),
                            'albumUrl' => $toAlbumUrl,
                            'albumName' => $toAlbum->name
                        )
                    ),
                    'features' => array('likes'),
                    'content' => '',
                    'view' => array('iconClass' => 'ow_ic_picture')
                ));

                OW::getEventManager()->trigger($event);
            }
        }
    }
    
    public function onAfterPhotoMove( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['fromAlbum']) || empty($params['toAlbum']) )
        {
            return;
        }
        
        $fromAlbumId = (int)$params['fromAlbum'];
        $toAlbumId = (int)$params['toAlbum'];
        $coverDao = PHOTO_BOL_PhotoAlbumCoverDao::getInstance();
        
        $fromCover = $coverDao->findByAlbumId($fromAlbumId);
        
        if ( $fromCover === NULL || (int)$fromCover->auto )
        {
            $coverDao->deleteCoverByAlbumId($fromAlbumId);

            $this->photoService->createAlbumCover($fromAlbumId, array_reverse(PHOTO_BOL_PhotoDao::getInstance()->getAlbumAllPhotos($fromAlbumId)));
        }
        
        $toCover = $coverDao->findByAlbumId($toAlbumId);
        
        if ( $toCover === NULL || (int)$toCover->auto )
        {
            $coverDao->deleteCoverByAlbumId($toAlbumId);

            $this->photoService->createAlbumCover($toAlbumId, array_reverse(PHOTO_BOL_PhotoDao::getInstance()->getAlbumAllPhotos($toAlbumId)));
        }
    }

    public function addPhotoURL( OW_Event $event )
    {
        $id = uniqid('addNewPhoto');
        
        $params = $event->getParams();
        $albumId = !empty($params['albumId']) ? (int)$params['albumId'] : null;
        $albumName = !empty($params['albumName']) ? $params['albumName'] : null;
        $albumDescription = !empty($params['albumDescription']) ? $params['albumDescription'] : null;
        $url = !empty($params['url']) ? $params['url'] : null;
        $data = $event->getData();

        $extraEventData = OW::getEventManager()->trigger(new OW_Event(self::EVENT_GET_UPLOAD_DATA, $params, $data));
        
        if ( !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');

            OW::getDocument()->addScriptDeclaration(
                UTIL_JsGenerator::composeJsString(
                    ';window[{$addNewPhoto}] = function()
                    {
                        OW.authorizationLimitedFloatbox({$msg});
                    }',
                    array(
                        'addNewPhoto' => $id,
                        'msg' => $status['msg']
                    )
                )
            );
        }
        else
        {
            OW::getDocument()->addScriptDeclaration(
                UTIL_JsGenerator::composeJsString(';window[{$addNewPhoto}] = function()
                    {
                        var ajaxUploadPhotoFB = OW.ajaxFloatBox("PHOTO_CMP_AjaxUpload", [{$albumId}, {$albumName}, {$albumDescription}, {$url}, {$data}], {
                            title: {$title},
                            width: "746px"
                        });

                        ajaxUploadPhotoFB.bind("close", function()
                        {
                            if ( ajaxPhotoUploader.isHasData() )
                            {
                                if ( confirm({$close_alert}) )
                                {
                                    OW.trigger("photo.onCloseUploaderFloatBox");
                                    return true;
                                }
                                
                                return false;
                            }
                            else
                            {
                                OW.trigger("photo.onCloseUploaderFloatBox");
                            }
                        });
                    }', array(
                        'addNewPhoto' => $id,
                        'albumId' => $albumId,
                        'albumName' => $albumName,
                        'albumDescription' => $albumDescription,
                        'url' => $url,
                        'data' => $extraEventData->getData(),
                        'title' => OW::getLanguage()->text('photo', 'upload_photos'),
                        'close_alert' => OW::getLanguage()->text('photo', 'close_alert')
                    )
                )
            );
        }
        
        return $id;
    }
    
    public function addSearchData( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['entityId']) || empty($params['entityType']) || empty($params['content']) )
        {
            return;
        }
        
        PHOTO_BOL_SearchService::getInstance()->addSearchData($params['entityId'], $params['entityType'], $params['content']);
    }
    
    public function backgroundLoadPhoto( OW_Event $event )
    {
        $params = $event->getParams();
        
        if ( empty($params['photoIdList']) )
        {
            return;
        }
        
        $photoList = PHOTO_BOL_PhotoDao::getInstance()->findByIdList($params['photoIdList']);
        $js = '$(window).load(function(){';
        
        foreach ( $photoList as $photo )
        {
            if ( $photo->hasFullsize )
            {
                $js .= ';new Image().src = ' . json_encode($this->photoService->getPhotoFullsizeUrl($photo->id, $photo->hash));
            }
            else
            {
                $js .= ';new Image().src = ' . json_encode($this->photoService->getPhotoUrl($photo->id, FALSE, $photo->hash));
            }
        }
        
        $js .= '});';
        
        OW::getDocument()->addScriptDeclaration($js);
    }

    public function collectAlbumsForAvatar( BASE_CLASS_EventCollector $e )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return;
        }

        $params = $e->getParams();
        $userId = OW::getUser()->getId();

        $total = $this->albumService->countUserAlbums($userId);
        $albums = $this->albumService->findUserAlbums($userId, 0, $total);

        if ( !$albums )
        {
            return;
        }

        foreach ( $albums as $album )
        {
            $photoCount = $this->photoService->countAlbumPhotos($album->id, array());
            if ( !$photoCount )
            {
                continue;
            }

            $photos = $this->photoService->getAlbumPhotos($album->id, 1, $params['limit']);

            $list = array();
            foreach ( $photos as $photo )
            {
                $list[] = array(
                    'id' => $photo['id'],
                    'entityId' => $album->id,
                    'entityType' => 'photo_album',
                    'url' => $photo['url'],
                    'bigUrl' => $this->photoService->getPhotoUrlByPhotoInfo($photo['id'], PHOTO_BOL_PhotoService::TYPE_MAIN, get_object_vars($photo['dto']))
                );
            }

            $section = array(
                'entityId' => $album->id,
                'entityType' => 'photo_album',
                'label' => $album->name,
                'count' => $photoCount,
                'list' => $list
            );

            $e->add($section);
        }
    }

    public function collectAlbumPhotosForAvatar( BASE_CLASS_EventCollector $e )
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            return;
        }

        $params = $e->getParams();

        if ( $params['entityType'] != 'photo_album' )
        {
            return;
        }

        $albumId = $params['entityId'];
        $page = floor($params['offset'] / $params['limit']) + 1;

        $photos = $this->photoService->getAlbumPhotos($albumId, $page, $params['limit']);

        if ( !$photos )
        {
            return;
        }

        $list = array();
        foreach ( $photos as $photo )
        {
            $list[] = array(
                'id' => $photo['id'],
                'url' => $photo['url'],
                'bigUrl' => $this->photoService->getPhotoUrlByPhotoInfo($photo['id'], PHOTO_BOL_PhotoService::TYPE_MAIN, get_object_vars($photo['dto']))
            );
        }

        $section = array(
            'count' => $this->photoService->countAlbumPhotos($albumId, array()),
            'list' => $list
        );

        $e->add($section);
    }

    public function getPhotoForAvatar( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['entityType'] == 'photo_album' )
        {
            $id = $params['id'];
            $photo = $this->photoService->findPhotoById($id);

            if ( $photo )
            {
                $type = (bool)$photo->hasFullsize ? PHOTO_BOL_PhotoService::TYPE_ORIGINAL : PHOTO_BOL_PhotoService::TYPE_MAIN;

                $data = array(
                    'url' => $this->photoService->getPhotoUrlByPhotoInfo($photo->id, $type, get_object_vars($photo)),
                    'path' => $this->photoService->getPhotoPath($photo->id, $photo->hash, $type)
                );

                $e->setData($data);

                return $data;
            }
        }
    }

    public function onUpdateContent( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['id']) )
        {
            return;
        }

        $this->photoService->updateFeedEntity($params['id']);
    }

    public function getPhotoViewStatus( OW_Event $event )
    {
        $params = $event->getParams();
        $modPermissions = OW::getUser()->isAuthorized('photo');

        if ( $modPermissions || !empty($params['isOwner']) )
        {
            $event->setData(array('available' => true));
        }
        else
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'view');

            $event->setData(array(
                'available' => $status['status'] == BOL_AuthorizationService::STATUS_AVAILABLE,
                'msg' => $status['msg']
            ));
        }

        return $event->getData();
    }

    public function getAlbumCoverUrl( OW_Event $event )
    {
        $params = $event->getParams();
        $albumId = $params['albumId'];
        $coverDao = PHOTO_BOL_PhotoAlbumCoverDao::getInstance();

        if ( ($coverDto = $coverDao->findByAlbumId($albumId)) === null )
        {
            if ( ($photo = $this->albumService->getLastPhotoByAlbumId($albumId)) === null )
            {
                $coverUrl = $coverDao->getAlbumCoverDefaultUrl();
            }
            else
            {
                $coverUrl = $this->photoService->getPhotoUrlByPhotoInfo($photo->id, PHOTO_BOL_PhotoService::TYPE_MAIN, get_object_vars($photo));
            }

            $coverUrlOrig = $coverUrl;
        }
        else
        {
            $coverUrl = $coverDao->getAlbumCoverUrlForCoverEntity($coverDto);
            $coverUrlOrig = $coverDao->getAlbumCoverOrigUrlForCoverEntity($coverDto);
        }

        $event->setData(array(
            'coverUrl' => $coverUrl,
            'coverUrlOrig' => $coverUrlOrig
        ));

        return $event->getData();
    }

    public function getAlbumNames( OW_Event $event )
    {
        $params = $event->getParams();

        if ( empty($params['userId']) )
        {
            $event->setData(array());

            return $event->getData();
        }

        $exclude = !empty($params['exclude']) && is_array($params['exclude']) ? $params['exclude'] : array();
        $event->setData($this->albumService->findAlbumNameListByUserId($params['userId'], $exclude));

        return $event->getData();
    }


    /**
     * Get sitemap urls
     *
     * @param OW_Event $event
     * @return void
     */
    public function onSitemapGetUrls( OW_Event $event )
    {
        $params = $event->getParams();

        if ( BOL_AuthorizationService::getInstance()->isActionAuthorizedForGuest('photo', 'view') )
        {
            $offset = (int) $params['offset'];
            $limit  = (int) $params['limit'];
            $urls   = array();

            switch ( $params['entity'] )
            {
                case 'photo_users' :
                    $usersIds  = PHOTO_BOL_PhotoService::getInstance()->findLatestPublicPhotosAuthorsIds($offset, $limit);
                    $userNames = BOL_UserService::getInstance()->getUserNamesForList($usersIds);

                    // skip deleted users
                    foreach ( array_filter($userNames) as $userName )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('photo.user_photos', array(
                            'user' => $userName
                        ));
                    }
                    break;

                case 'photo_user_albums' :
                    $usersIds  = PHOTO_BOL_PhotoAlbumService::getInstance()->findLatestAlbumsAuthorsIds($offset, $limit);
                    $userNames = BOL_UserService::getInstance()->getUserNamesForList($usersIds);

                    // skip deleted users
                    foreach ( array_filter($userNames) as $userName )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('photo_user_albums', array(
                            'user' => $userName
                        ));
                    }

                    break;

                case 'photo_tags' :
                    $tags = BOL_TagService::getInstance()->findMostPopularTags('photo', $limit, $offset);

                    foreach ( $tags as $tag )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('view_tagged_photo_list', array(
                            'tag' => $tag['label']
                        ));
                    }
                    break;

                case 'photos_latest' :
                    $photos  = PHOTO_BOL_PhotoService::getInstance()->findLastPublicPhotos($offset, $limit);

                    foreach ( $photos as $photo )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('view_photo_type', array(
                            'id' => $photo['id'],
                            'listType' => 'latest'
                        ));
                    }
                    break;

                case 'photos_toprated' :
                    $photos  = PHOTO_BOL_PhotoService::getInstance()->findLastPublicPhotos($offset, $limit);

                    foreach ( $photos as $photo )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('view_photo_type', array(
                            'id' => $photo['id'],
                            'listType' => 'toprated'
                        ));
                    }
                    break;

                case 'photos_most_discussed' :
                    $photos  = PHOTO_BOL_PhotoService::getInstance()->findLastPublicPhotos($offset, $limit);

                    foreach ( $photos as $photo )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('view_photo_type', array(
                            'id' => $photo['id'],
                            'listType' => 'most_discussed'
                        ));
                    }
                    break;

                case 'photos' :
                    $photos  = PHOTO_BOL_PhotoService::getInstance()->findLastPublicPhotos($offset, $limit);

                    foreach ( $photos as $photo )
                    {
                        $urls[] = OW::getRouter()->urlForRoute('view_photo', array(
                            'id' => $photo['id']
                        ));
                    }
                    break;

                case 'photo_albums' :
                    $albums = PHOTO_BOL_PhotoAlbumService::getInstance()->findLastAlbumsIds($offset, $limit);

                    foreach ( $albums as $albumId )
                    {
                        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);
                        $userName = BOL_UserService::getInstance()->getUsername($album->userId);

                        // skip deleted users
                        if ( !$userName )
                        {
                            continue;
                        }

                        $urls[] = OW::getRouter()->urlForRoute('photo_user_album', array(
                            'user' => $userName,
                            'album' => $album->id
                        ));
                    }
                    break;

                case 'photo_list' :
                    $urls[] = OW::getRouter()->urlForRoute('view_photo_list', array(
                        'listType' => 'latest'
                    ));

                    $urls[] = OW::getRouter()->urlForRoute('view_photo_list', array(
                        'listType' => 'toprated'
                    ));

                    $urls[] = OW::getRouter()->urlForRoute('view_photo_list', array(
                        'listType' => 'most_discussed'
                    ));

                    $urls[] = OW::getRouter()->urlForRoute('view_tagged_photo_list_st');
                    break;
            }

            if ( $urls )
            {
                $event->setData($urls);
            }
        }
    }

    public function onCollectMetaData( BASE_CLASS_EventCollector $e )
    {
        $language = OW::getLanguage();

        $items = array(
            array(
                "entityKey" => "taggedList",
                "entityLabel" => $language->text("photo", "seo_meta_tagged_list_label"),
                "iconClass" => "ow_ic_tag",
                "langs" => array(
                    "title" => "photo+meta_title_tagged_list",
                    "description" => "photo+meta_desc_tagged_list",
                    "keywords" => "photo+meta_keywords_tagged_list"
                ),
                "vars" => array("site_name")
            ),
            array(
                "entityKey" => "photoList",
                "entityLabel" => $language->text("photo", "seo_meta_photo_list_label"),
                "iconClass" => "ow_ic_picture",
                "langs" => array(
                    "title" => "photo+meta_title_photo_list",
                    "description" => "photo+meta_desc_photo_list",
                    "keywords" => "photo+meta_keywords_photo_list"
                ),
                "vars" => array("site_name", "list_type")
            ),
            array(
                "entityKey" => "userAlbums",
                "entityLabel" => $language->text("photo", "seo_meta_user_albums_label"),
                "iconClass" => "ow_ic_picture",
                "langs" => array(
                    "title" => "photo+meta_title_user_albums",
                    "description" => "photo+meta_desc_user_albums",
                    "keywords" => "photo+meta_keywords_user_albums"
                ),
                "vars" => array("user_name", "user_gender", "user_age", "user_location", "site_name")
            ),
            array(
                "entityKey" => "userAlbum",
                "entityLabel" => $language->text("photo", "seo_meta_user_album_label"),
                "iconClass" => "ow_ic_picture",
                "langs" => array(
                    "title" => "photo+meta_title_user_album",
                    "description" => "photo+meta_desc_user_album",
                    "keywords" => "photo+meta_keywords_user_album"
                ),
                "vars" => array("user_name", "user_gender", "user_age", "user_location", "site_name", "album_name")
            ),
            array(
                "entityKey" => "userPhotos",
                "entityLabel" => $language->text("photo", "seo_meta_user_photos_label"),
                "iconClass" => "ow_ic_picture",
                "langs" => array(
                    "title" => "photo+meta_title_user_photos",
                    "description" => "photo+meta_desc_user_photos",
                    "keywords" => "photo+meta_keywords_user_photos"
                ),
                "vars" => array("user_name", "user_gender", "user_age", "user_location", "site_name")
            ),
            array(
                "entityKey" => "photoView",
                "entityLabel" => $language->text("photo", "seo_meta_photo_view_label"),
                "iconClass" => "ow_ic_picture",
                "langs" => array(
                    "title" => "photo+meta_title_photo_view",
                    "description" => "photo+meta_desc_photo_view",
                    "keywords" => "photo+meta_keywords_photo_view"
                ),
                "vars" => array("photo_id", "user_name", "site_name")
            )
        );


        foreach ($items as &$item)
        {
            $item["sectionLabel"] = $language->text("photo", "seo_meta_section");
            $item["sectionKey"] = "photo";
            $e->add($item);
        }
    }

    public function init()
    {
        $this->genericInit();
        $em = OW::getEventManager();

        $em->bind(BASE_CMP_AddNewContent::EVENT_NAME, array($this, 'addNewContentItem'));
        $em->bind(BASE_CMP_QuickLinksWidget::EVENT_NAME, array($this, 'addQuickLink'));
        $em->bind('feed.on_item_render', array($this, 'feedOnItemRender'));
        $em->bind(self::EVENT_BEFORE_MULTIPLE_PHOTO_DELETE, array($this, 'onBeforeMultiplePhotoDelete'));
        $em->bind(self::EVENT_BEFORE_PHOTO_MOVE, array($this, 'onBeforePhotoMove'));
        $em->bind(self::EVENT_AFTER_PHOTO_MOVE, array($this, 'onAfterPhotoMove'));
        $em->bind(self::EVENT_GET_ADDPHOTO_URL, array($this, 'addPhotoURL'));
        $em->bind('base.avatar_change_collect_sections', array($this, 'collectAlbumsForAvatar'));
        $em->bind('base.avatar_change_get_section', array($this, 'collectAlbumPhotosForAvatar'));
        $em->bind('base.avatar_change_get_item', array($this, 'getPhotoForAvatar'));
        $em->bind("base.collect_seo_meta_data", array($this, 'onCollectMetaData'));
    }

    public function genericInit()
    {
        $em = OW::getEventManager();

        $em->bind(self::EVENT_ALBUM_ADD, array($this, 'albumAdd'));
        $em->bind(self::EVENT_ALBUM_FIND, array($this, 'albumFind'));
        $em->bind(self::EVENT_ALBUM_DELETE, array($this, 'albumDelete'));
        $em->bind(self::EVENT_ENTITY_ALBUMS_FIND, array($this, 'entityAlbumsFind'));
        $em->bind(self::EVENT_PHOTO_ADD, array($this, 'photoAdd'));
        $em->bind(self::EVENT_PHOTO_FIND, array($this, 'photoFind'));
        $em->bind(self::EVENT_PHOTO_FINDS, array($this, 'photoFinds'));
        $em->bind(self::EVENT_PHOTO_DELETE, array($this, 'photoDelete'));
        $em->bind(self::EVENT_ALBUM_PHOTOS_COUNT, array($this, 'albumPhotosCount'));
        $em->bind(self::EVENT_ALBUM_PHOTOS_FIND, array($this, 'albumPhotosFind'));
        
        $em->bind(self::EVENT_ENTITY_ALBUMS_COUNT, array($this, 'entityAlbumsCount'));
        
        $em->bind(self::EVENT_ENTITY_PHOTOS_FIND, array($this, 'entityPhotosFind'));
        $em->bind(self::EVENT_ENTITY_PHOTOS_COUNT, array($this, 'entityPhotosCount'));
        $em->bind(self::EVENT_ENTITY_ALBUMS_DELETE, array($this, 'entityAlbumsDelete'));
        
        $em->bind(self::EVENT_INIT_FLOATBOX, array($this, 'initFloatbox'));
        $em->bind(self::EVENT_GET_PHOTO_VIEW_STATUS, array($this, 'getPhotoViewStatus'));

        $em->bind('ads.enabled_plugins', array($this, 'adsEnabled'));
        $em->bind('admin.add_auth_labels', array($this, 'addAuthLabels'));
        $em->bind(OW_EventManager::ON_USER_UNREGISTER, array($this, 'onUserUnregister'));
        $em->bind('plugin.privacy.get_action_list', array($this, 'addPrivacyAction'));
        $em->bind('plugin.privacy.on_change_action_privacy', array($this, 'onChangePrivacy'));
        $em->bind('notifications.collect_actions', array($this, 'collectNotificationActions'));
        $em->bind('base_add_comment', array($this, 'notifyOnNewComment'));
        $em->bind('base.query.content_filter', array($this, 'photoContentFilter'));
        $em->bind('feed.on_entity_action', array($this, 'feedOnEntityAction'));
        $em->bind('feed.collect_configurable_activity', array($this, 'feedCollectConfigurableActivity'));
        $em->bind('feed.collect_privacy', array($this, 'feedCollectPrivacy'));
        $em->bind('feed.after_comment_add', array($this, 'feedAfterCommentAdd'));
        $em->bind('feed.after_like_added', array($this, 'feedAfterLikeAdded'));

        $credits = new PHOTO_CLASS_Credits();
        $em->bind('usercredits.on_action_collect', array($credits, 'bindCreditActionsCollect'));
        $em->bind('usercredits.get_action_key', array($credits, 'getActionKey'));

        $em->bind('socialsharing.get_entity_info', array($this, 'sosialSharingGetPhotoInfo'));
        
        $em->bind(self::EVENT_ON_ALBUM_ADD, array($this, 'onAlbumAdd'));
        $em->bind(self::EVENT_ON_ALBUM_EDIT, array($this, 'onAlbumEdit'));
        $em->bind(self::EVENT_BEFORE_ALBUM_DELETE, array($this, 'onBeforeAlbumDelete'));
        $em->bind(self::EVENT_ON_PHOTO_ADD, array($this, 'onPhotoAdd'));
        $em->bind(self::EVENT_ON_PHOTO_EDIT, array($this, 'onAfterPhotoEdit'));
        $em->bind(self::EVENT_BEFORE_PHOTO_DELETE, array($this, 'onBeforePhotoDelete'));
        $em->bind(self::EVENT_ON_PHOTO_DELETE, array($this, 'onPhotoDelete'));
        $em->bind(self::EVENT_CREATE_USER_ALBUM, array($this, 'createUserAlbum'));
        $em->bind(self::EVENT_GET_MAIN_ALBUM, array($this, 'getMainAlbum'));
        $em->bind(self::EVENT_ADD_SEARCH_DATA, array($this, 'addSearchData'));
        $em->bind('feed.before_content_add', array($this, 'feedBeforeStatusUpdate'));
        $em->bind(self::EVENT_BACKGROUND_LOAD_PHOTO, array($this, 'backgroundLoadPhoto'));
        $em->bind(self::EVENT_ON_PHOTO_CONTENT_UPDATE, array($this, 'onUpdateContent'));

        $em->bind(self::EVENT_GET_ALBUM_COVER_URL, array($this, 'getAlbumCoverUrl'));
        $em->bind(self::EVENT_GET_ALBUM_NAMES, array($this, 'getAlbumNames'));
        $em->bind("base.sitemap.get_urls", array($this, 'onSitemapGetUrls'));

        PHOTO_CLASS_ContentProvider::getInstance()->init();
    }
}
