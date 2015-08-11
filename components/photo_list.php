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
 * @since 1.6.1
 */
class PHOTO_CMP_PhotoList extends OW_Component
{
    public function __construct( array $params )
    {
        parent::__construct();
        
        $plugin = OW::getPluginManager()->getPlugin('photo');
        
        $hasSideBar = OW::getThemeManager()->getCurrentTheme()->getDto()->getSidebarPosition() != 'none';
        $photoParams = array(
            'classicMode' => (bool)OW::getConfig()->getValue('photo', 'photo_list_view_classic')
        );
        $contParams = array(
            'isClassic' => $photoParams['classicMode'],
            'isModerator' => OW::getUser()->isAuthorized('photo')
        );
        
        switch ( $params['type'] )
        {
            case 'albums':
                $photoParams = array(
                    'userId' => $params['userId'],
                    'action' => 'getAlbumList',
                    'level' => ($hasSideBar ? 3 : 4),
                    'classicMode' => (bool)OW::getConfig()->getValue('photo', 'album_list_view_classic'),
                    'isOwner' => $params['userId'] == OW::getUser()->getId() || OW::getUser()->isAuthorized('photo')
                );
                $contParams['isOwner'] = $photoParams['isOwner'];
                $contParams['isClassic'] = $photoParams['classicMode'];
                break;
            case 'albumPhotos':
                $photoParams['albumId'] = $params['albumId'];
                $photoParams['isOwner'] = PHOTO_BOL_PhotoAlbumService::getInstance()->isAlbumOwner($params['albumId'], OW::getUser()->getId());
                $photoParams['level'] = ($photoParams['classicMode'] ? ($hasSideBar ? 4 : 5) : 4);
                
                $contParams['isOwner'] = $photoParams['isOwner'];
                $contParams['albumId'] = $params['albumId'];
                break;
            case 'userPhotos':
                $photoParams['userId'] = $params['userId'];
                $photoParams['isOwner'] = $params['userId'] == OW::getUser()->getId();
                $photoParams['level'] = ($photoParams['classicMode'] ? ($hasSideBar ? 4 : 5) : 4);
                
                $contParams['isOwner'] = $photoParams['isOwner'];
                break;
            case 'tag':
                $photoParams['searchVal'] = $params['tag'];
            default:
                $photoParams['level'] = ($photoParams['classicMode'] ? ($hasSideBar ? 4 : 5) : 4);
                break;
        }
        
        $photoDefault = array(
            'getPhotoURL' => OW::getRouter()->urlFor('PHOTO_CTRL_Photo', 'ajaxResponder'),
            'listType' => $params['type'],
            'rateUserId' => OW::getUser()->getId(),
            'urlHome' => OW_URL_HOME,
            'tagUrl' => OW::getRouter()->urlForRoute('view_tagged_photo_list', array('tag' => '-tag-'))
        );
        
        $contDefault = array(
            'downloadAccept' => (bool)OW::getConfig()->getValue('photo', 'download_accept'),
            'downloadUrl' => OW_URL_HOME . 'photo/download-photo/:id',
            'actionUrl' => $photoDefault['getPhotoURL'],
            'listType' => $params['type']
        );
        
        $document = OW::getDocument();
        
        $document->addScriptDeclarationBeforeIncludes(
            UTIL_JsGenerator::composeJsString(';window.browsePhotoParams = Object.freeze({$params});', array(
                'params' => array_merge($photoDefault, $photoParams)
            ))
        );
        $document->addOnloadScript(';window.browsePhoto.init();');

        $document->addScriptDeclarationBeforeIncludes(
            UTIL_JsGenerator::composeJsString(';window.photoContextActionParams = Object.freeze({$params});', array(
                'params' => array_merge($contDefault, $contParams)
            ))
        );
        $document->addOnloadScript(';window.photoContextAction.init();');
        
        $this->assign('isClassicMode', $photoParams['classicMode']);
        $this->assign('hasSideBar', $hasSideBar);
        $this->assign('type', $params['type']);
        
        $document->addStyleSheet($plugin->getStaticCssUrl() . 'browse_photo.css');
        $document->addScript($plugin->getStaticJsUrl() . 'utils.js');
        $document->addScript($plugin->getStaticJsUrl() . 'browse_photo.js');
        
        $language = OW::getLanguage();
        
        if ( $params['type'] != 'albums' )
        {
            $event = new OW_Event(PHOTO_CLASS_EventHandler::EVENT_INIT_FLOATBOX, $photoParams);
            OW::getEventManager()->trigger($event);

            $language->addKeyForJs('photo', 'tb_edit_photo');
            $language->addKeyForJs('photo', 'confirm_delete');
            $language->addKeyForJs('photo', 'mark_featured');
            $language->addKeyForJs('photo', 'remove_from_featured');
            $language->addKeyForJs('photo', 'no_photo');

            $language->addKeyForJs('photo', 'rating_total');
            $language->addKeyForJs('photo', 'rating_your');
            $language->addKeyForJs('base', 'rate_cmp_owner_cant_rate_error_message');

            $language->addKeyForJs('photo', 'download_photo');
            $language->addKeyForJs('photo', 'delete_photo');
            $language->addKeyForJs('photo', 'save_as_avatar');
            $language->addKeyForJs('photo', 'save_as_cover');
            
            $language->addKeyForJs('photo', 'search_invitation');
            $language->addKeyForJs('photo', 'set_as_album_cover');
            $language->addKeyForJs('photo', 'search_result_empty');
        }
        else
        {
            $language->addKeyForJs('photo', 'edit_album');
            $language->addKeyForJs('photo', 'delete_album');
            $language->addKeyForJs('photo', 'album_delete_not_allowed');
            $language->addKeyForJs('photo', 'newsfeed_album');
            $language->addKeyForJs('photo', 'are_you_sure');
            $language->addKeyForJs('photo', 'album_description');
        }
        
        $language->addKeyForJs('photo', 'no_items');
    }
}
