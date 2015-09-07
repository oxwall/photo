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
 * @author Kairat Bakitow <kainisoft@gmail.com>
 * @package ow.plugin.photo.components
 * @since 1.7.6
 */
class PHOTO_CMP_PageHead extends OW_Component
{
    public function __construct( $ownerMode, $album )
    {
        parent::__construct();

        $language = OW::getLanguage();

        $isAuthorized = OW::getUser()->isAuthorized('photo', 'upload');
        $this->assign('isAuthorized', $isAuthorized);

        if ( $isAuthorized )
        {
            $language->addKeyForJs('photo', 'album_name');
            $language->addKeyForJs('photo', 'album_desc');
            $language->addKeyForJs('photo', 'create_album');
            $language->addKeyForJs('photo', 'newsfeed_album');
            $language->addKeyForJs('photo', 'newsfeed_album_error_msg');
            $language->addKeyForJs('photo', 'upload_photos');
            $language->addKeyForJs('photo', 'close_alert');
        }
        else
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');

            if ( $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED )
            {
                $this->assign('isPromo', true);
                $this->assign('promoMsg', json_encode($status['msg']));
            }
        }

        $this->assign('url', OW::getEventManager()->call(PHOTO_CLASS_EventHandler::EVENT_GET_ADDPHOTO_URL, array(
            'albumId' => (!empty($ownerMode) && !empty($album)) ? $album->id : 0
        )));
        
        $menu = new BASE_CMP_SortControl();
        $menu->setTemplate(OW::getPluginManager()->getPlugin('photo')->getCmpViewDir() . 'sort_control.html');

        $handler = OW::getRequestHandler()->getHandlerAttributes();

        if ( in_array($handler[OW_RequestHandler::ATTRS_KEY_ACTION], array('viewList', 'viewTaggedList')) )
        {
            $menu->addItem(
                'latest',
                $language->text('photo', 'menu_latest'),
                OW::getRouter()->urlForRoute('view_photo_list', array(
                    'listType' => 'latest'
                ))
            );

            if ( PHOTO_BOL_PhotoService::getInstance()->countPhotos('featured') )
            {
                $menu->addItem(
                    'featured',
                    $language->text('photo', 'menu_featured'),
                    OW::getRouter()->urlForRoute('view_photo_list', array(
                        'listType' => 'featured'
                    ))
                );
            }

            $menu->addItem(
                'toprated',
                $language->text('photo', 'menu_toprated'),
                OW::getRouter()->urlForRoute('view_photo_list', array(
                    'listType' => 'toprated'
                ))
            );

            $menu->addItem(
                'most_discussed',
                $language->text('photo', 'menu_most_discussed'),
                OW::getRouter()->urlForRoute('view_photo_list', array(
                    'listType' => 'most_discussed'
                ))
            );

            if ( $handler[OW_RequestHandler::ATTRS_KEY_ACTION] != 'viewTaggedList')
            {
                $menu->setActive(!empty($handler[OW_RequestHandler::ATTRS_KEY_VARLIST]['listType']) ? $handler[OW_RequestHandler::ATTRS_KEY_VARLIST]['listType'] : 'latest');
            }
            
            $menu->assign('initSearchEngine', TRUE);
        }
        else
        {
            if ( !$ownerMode )
            {
                $user = BOL_UserService::getInstance()->findByUsername($handler[OW_RequestHandler::ATTRS_KEY_VARLIST]['user']);
                $this->assign('user', $user);

                $avatar = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($user->id));
                $this->assign('avatar', $avatar[$user->id]);

                $onlineStatus = BOL_UserService::getInstance()->findOnlineStatusForUserList(array($user->id));
                $this->assign('onlineStatus', $onlineStatus[$user->id]);
            }
            
            $menu->addItem(
                'userPhotos',
                $language->text('photo', 'menu_photos'),
                OW::getRouter()->urlForRoute('photo.user_photos', array(
                    'user' => $handler[OW_RequestHandler::ATTRS_KEY_VARLIST]['user']
                ))
            );

            $menu->addItem(
                'userAlbums',
                $language->text('photo', 'menu_albums'),
                OW::getRouter()->urlForRoute('photo_user_albums', array(
                    'user' => $handler[OW_RequestHandler::ATTRS_KEY_VARLIST]['user']
                ))
            );
            
            if ( in_array($handler[OW_RequestHandler::ATTRS_KEY_ACTION], array('userAlbums', 'userAlbum')) )
            {
                $menu->setActive('userAlbums');
            }
            else
            {
                $menu->setActive('userPhotos');
            }
        }

        $event = OW::getEventManager()->trigger(
            new BASE_CLASS_EventCollector(PHOTO_CLASS_EventHandler::EVENT_COLLECT_PHOTO_SUB_MENU)
        );
        
        foreach ( $event->getData() as $menuItem )
        {
            $menu->addItem(
                $menuItem['sortOrder'],
                $menuItem['label'],
                $menuItem['url'],
                isset($menuItem['isActive']) ? (bool) $menuItem['isActive'] : FALSE
            );
        }
        
        $this->addComponent('subMenu', $menu);
        
        if ( OW::getUser()->isAuthenticated() )
        {
            $userObj = OW::getUser()->getUserObject();
            
            if (
                in_array($handler[OW_RequestHandler::ATTRS_KEY_ACTION], array('viewList', 'viewTaggedList')) ||
                (!empty($handler[OW_RequestHandler::ATTRS_KEY_VARLIST]['user']) && $handler[OW_RequestHandler::ATTRS_KEY_VARLIST]['user'] == $userObj->username)
            )
            {
                $menuItems = array();

                $item = new BASE_MenuItem();
                $item->setKey('menu_explore');
                $item->setLabel($language->text('photo', 'menu_explore'));
                $item->setUrl(OW::getRouter()->urlForRoute('view_photo_list'));
                $item->setIconClass('ow_ic_lens');
                $item->setOrder(0);
                $item->setActive(in_array($handler[OW_RequestHandler::ATTRS_KEY_ACTION], array('viewList', 'viewTaggedList')));
                $menuItems[] = $item;

                $item = new BASE_MenuItem();
                $item->setKey('menu_my_photos');
                $item->setLabel($language->text('photo', 'menu_my_photos'));
                $item->setUrl(OW::getRouter()->urlForRoute('photo.user_photos', array('user' => $userObj->username)));
                $item->setIconClass('ow_ic_picture');
                $item->setOrder(1);
                $item->setActive($ownerMode);
                $menuItems[] = $item;

                $this->addComponent('photoMenu', new BASE_CMP_ContentMenu($menuItems));
            }
        }
    }
}
