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
 * Mobile photo event handler
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.mobile.classes
 * @since 1.6.0
 */
class PHOTO_MCLASS_EventHandler
{
    /**
     * @var PHOTO_MCLASS_EventHandler
     */
    private static $classInstance;

    /**
     * @return PHOTO_MCLASS_EventHandler
     */
    public static function getInstance()
    {
        if ( self::$classInstance === null )
        {
            self::$classInstance = new self();
        }

        return self::$classInstance;
    }

    private function __construct() { }

    public function onAddProfileContentMenu( BASE_CLASS_EventCollector $event )
    {
        $params = $event->getParams();

        if ( empty($params['userId']) )
        {
            return;
        }

        $userId = (int) $params['userId'];

        $lang = OW::getLanguage();
        $linkId = uniqid('photo');

        $albumService = PHOTO_BOL_PhotoAlbumService::getInstance();

        if ( !$albumService->countUserAlbums($userId) )
        {
            return;
        }

        $albumList = $albumService->findUserAlbumList($userId, 1, 1);
        $cover = $albumList[0]['cover'];

        $username = BOL_UserService::getInstance()->getUserName($userId);
        $url = OW::getRouter()->urlForRoute('photo_user_albums', array('user' => $username));
        $resultArray = array(
            BASE_MCMP_ProfileContentMenu::DATA_KEY_LABEL => $lang->text('photo', 'user_photo_albums_widget'),
            BASE_MCMP_ProfileContentMenu::DATA_KEY_LINK_HREF => $url,
            BASE_MCMP_ProfileContentMenu::DATA_KEY_LINK_ID => $linkId,
            BASE_MCMP_ProfileContentMenu::DATA_KEY_LINK_CLASS => 'owm_profile_nav_photo',
            BASE_MCMP_ProfileContentMenu::DATA_KEY_THUMB => $cover
        );

        $event->add($resultArray);
    }

    public function onMobileTopMenuAddLink( BASE_CLASS_EventCollector $event )
    {
        $event->add(array(
            'prefix' => 'photo',
            'key' => 'mobile_photo',
            'url' => OW::getRouter()->urlForRoute('photo_upload')
        ));
    }

    public function onNotificationRender( OW_Event $e )
    {
        $params = $e->getParams();

        if ( $params['pluginKey'] != 'photo' || $params['entityType'] != 'photo_add_comment' )
        {
            return;
        }

        $data = $params['data'];

        if ( !isset($data['avatar']['urlInfo']['vars']['username']) )
        {
            return;
        }

        $userService = BOL_UserService::getInstance();
        $user = $userService->findByUsername($data['avatar']['urlInfo']['vars']['username']);
        if ( !$user )
        {
            return;
        }

        $commentId = $params['entityId'];
        $comment = BOL_CommentService::getInstance()->findComment($commentId);
        if ( !$comment )
        {
            return;
        }
        $commEntity = BOL_CommentService::getInstance()->findCommentEntityById($comment->commentEntityId);
        if ( !$commEntity )
        {
            return;
        }

        $langVars = array(
            'userUrl' => $userService->getUserUrl($user->id),
            'userName' => $userService->getDisplayName($user->id),
            'photoUrl' => OW::getRouter()->urlForRoute('view_photo', array('id' => $commEntity->entityId))
        );
        $data['string'] = array('key' => 'photo+email_notifications_comment', 'vars' => $langVars);

        $e->setData($data);
    }

    public function init()
    {
        PHOTO_CLASS_EventHandler::getInstance()->genericInit();

        $em = OW::getEventManager();

        $em->bind(BASE_MCMP_ProfileContentMenu::EVENT_NAME, array($this, 'onAddProfileContentMenu'));
        $em->bind('base.mobile_top_menu_add_options', array($this, 'onMobileTopMenuAddLink'));
        $em->bind('mobile.notifications.on_item_render', array($this, 'onNotificationRender'));
    }
}