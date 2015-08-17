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
 * Photo user photo albums widget
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.components
 * @since 1.0
 */
class PHOTO_CMP_UserPhotoAlbumsWidget extends BASE_CLASS_Widget
{
    public function __construct( BASE_CLASS_WidgetParameter $paramObj )
    {
        parent::__construct();

        $photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();

        $userId = $paramObj->additionalParamList['entityId'];

        $user = BOL_UserService::getInstance()->getUserName($userId);
        $this->assign('user', $user);

        $num = isset($paramObj->customParamList['albumsCount']) ? $paramObj->customParamList['albumsCount'] : 4;

        $albums = $photoAlbumService->findUserAlbumList($userId, 1, $num);
        
        if ( $albums )
        {
            $event = OW::getEventManager()->trigger(
                new OW_Event('photo.albumsWidgetReady', array(), $albums)
            );
            $this->assign('albums', $event->getData());

            $albumsCount = $photoAlbumService->countUserAlbums($userId);

            $this->assign('albumsCount', $albumsCount);
        }
        else
        {
            if ( !$paramObj->customizeMode )
            {
                $this->setVisible(false);
            }

            $this->assign('albums', null);
            $this->assign('albumsCount', 0);
            $albumsCount = 0;
        }
        
        // privacy check
        $viewerId = OW::getUser()->getId();
        $ownerMode = $userId == $viewerId;
        $modPermissions = OW::getUser()->isAuthorized('photo');
        
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'photo_view_album', 'ownerId' => $userId, 'viewerId' => $viewerId);
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            
            try {
                OW::getEventManager()->trigger($event);
            }
            catch ( RedirectException $e )
            {
                $this->setVisible(false);
            }
        }

        $showTitles = isset($paramObj->customParamList['showTitles']) ? $paramObj->customParamList['showTitles'] : false;
        $this->assign('showTitles', $showTitles);

        $lang = OW::getLanguage();

        $this->setSettingValue(self::SETTING_TOOLBAR, array(
            array('label' => $lang->text('photo', 'total_albums', array('total' => $albumsCount))),
            array('label' => $lang->text('base', 'view_all'), 'href' => OW::getRouter()->urlForRoute('photo_user_albums', array('user' => $user)))
        ));
    }

    public static function getSettingList()
    {
        $lang = OW::getLanguage();

        $settingList = array();
        $settingList['albumsCount'] = array(
            'presentation' => self::PRESENTATION_SELECT,
            'label' => $lang->text('photo', 'cmp_widget_photo_albums_count'),
            'optionList' => array('1' => 1, '2' => 2, '3' => 3, '4' => 4, '5' => 5, '6' => 6, '7' => 7, '8' => 8, '9' => 9, '10' => 10),
            'value' => 3
        );

        $settingList['showTitles'] = array(
            'presentation' => self::PRESENTATION_CHECKBOX,
            'label' => $lang->text('photo', 'cmp_widget_photo_albums_show_titles'),
            'value' => true
        );

        return $settingList;
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_TITLE => OW::getLanguage()->text('photo', 'user_photo_albums_widget'),
            self::SETTING_ICON => self::ICON_PICTURE,
            self::SETTING_SHOW_TITLE => true,
            self::SETTING_WRAP_IN_BOX => true
        );
    }
}