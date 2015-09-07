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
 * Index photo list component
 *
 * @authors Kairat Bakitow <kainisoft@gmail.com>
 * @package ow.plugin.photo.components
 * @since 1.7.6
 */
class PHOTO_CMP_IndexPhotoList extends OW_Component
{
    public function __construct( $params )
    {
        parent::__construct();

        $photoCount = !empty($params['photoCount']) ? (int) $params['photoCount'] : 8;
        $menu = isset($params['showMenu']) ? (bool) $params['showMenu'] : true;
        $showToolbar = isset($params['showToolbar']) ? (bool) $params['showToolbar'] : true;
        $checkAuth = isset($params['checkAuth']) ? (bool) $params['checkAuth'] : true;
        $wrap = isset($params['wrapBox']) ? (bool) $params['wrapBox'] : true;
        $boxType = isset($params['boxType']) ? $params['boxType'] : '';
        $showTitle = isset($params['showTitle']) ? (bool) $params['showTitle'] : true;
        $uniqId = isset($params['uniqId']) ? $params['uniqId'] : uniqid();

        if ( $checkAuth && !OW::getUser()->isAuthorized('photo', 'view') )
        {
            $this->setVisible(false);

            return;
        }

        $photoService = PHOTO_BOL_PhotoService::getInstance();

        $latest = $photoService->findPhotoList('latest', 1, $photoCount, NULL, PHOTO_BOL_PhotoService::TYPE_PREVIEW);
        $featured = $photoService->findPhotoList('featured', 1, $photoCount, NULL, PHOTO_BOL_PhotoService::TYPE_PREVIEW);
        $topRated = $photoService->findPhotoList('toprated', 1, $photoCount, NULL, PHOTO_BOL_PhotoService::TYPE_PREVIEW);

        $event = OW::getEventManager()->trigger(
            new OW_Event('photo.onIndexWidgetListReady', array(
                'latest' => $latest,
                'featured' => $featured,
                'topRated' => $topRated
            ))
        );
        $data = $event->getData();

        if ( is_array($data) )
        {
            $latest = $data['latest'];
            $featured = $data['featured'];
            $topRated = $data['topRated'];
        }

        $this->assign('latest', $latest);
        $this->assign('featured', $featured);
        $this->assign('toprated', $topRated);

        $items = array('latest', 'toprated');

        if ( $featured )
        {
            $items[] = 'featured';
        }
        $menuItems = self::getMenuItems($items, $uniqId);
        $this->assign('items', $menuItems);

        if ( $menu )
        {
            $this->addComponent('menu', new BASE_CMP_WidgetMenu($menuItems));
        }

        if ( !$latest && !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $this->setVisible(false);

            return;
        }

        $toolbars = $showToolbar ? self::getToolbar() : array('latest' => null);

        $this->assign('wrapBox', $wrap);
        $this->assign('boxType', $boxType);
        $this->assign('showTitle', $showTitle);
        $this->assign('showToolbar', $showToolbar);
        $this->assign('toolbars', $toolbars);
        $this->assign('url', OW::getEventManager()->call('photo.getAddPhotoURL', array('')));
        
        $event = new OW_Event(PHOTO_CLASS_EventHandler::EVENT_INIT_FLOATBOX);
        OW::getEventManager()->trigger($event);
        
        $arr = array();
        $dimension = array();
        
        foreach ( array_merge($latest, $topRated, $featured) as $photo )
        {
            if ( in_array($photo['id'], $arr) )
            {
                continue;
            }
            
            if ( !empty($photo['dimension']) )
            {
                $dimension[$photo['id']] = json_decode($photo['dimension']);
            }
            
            $arr[] = $photo['id'];
        }
        
        OW::getDocument()->addOnloadScript(UTIL_JsGenerator::composeJsString(';
            $(".ow_lp_photos a.ow_lp_wrapper").on("click", function(e)
            {
                e.preventDefault();
                var dimension = {$dimension}, _data = {};
                var photoId = $(this).attr("rel");
                var listType = $(this).attr("list-type");
                var img = new Image();
                var photos = {$photos};
                img.src = $(this).find("div").attr("data-url");
                
                if ( dimension.hasOwnProperty(photoId) && dimension[photoId].main )
                {
                    _data.main = dimension[photoId].main;
                }
                else
                {
                    _data.main = [img.naturalWidth, img.naturalHeight];
                }

                _data.mainUrl = img.src;

                var photoList = photos[listType], photo;

                for ( var i = 0, j = photoList.length; i < j; i++ )
                {
                    var tmpPhoto = photoList[i];

                    if ( tmpPhoto.id == photoId )
                    {
                        photo = tmpPhoto;

                        break;
                    }
                }
                
                photoView.setId(photoId, listType, null, _data, photo);
            });', array(
                'dimension' => $dimension,
                'photos' => array(
                    'latest' => $latest,
                    'featured' => $featured,
                    'toprated' => $topRated
                )
            )
        ));

        $this->assign('uniqId', $uniqId);

        $script =
        'var $tb_container = $("#photo_list_cmp'.$uniqId.'").closest(".ow_box, .ow_box_empty").find(".ow_box_toolbar_cont");
        $("#photo-cmp-menu-featured-'.$uniqId.'").click(function(){
            $tb_container.html($("div#photo-cmp-toolbar-featured-'.$uniqId.'").html());
        });

        $("#photo-cmp-menu-latest-'.$uniqId.'").click(function(){
            $tb_container.html($("div#photo-cmp-toolbar-latest-'.$uniqId.'").html());
        });

        $("#photo-cmp-menu-top-rated-'.$uniqId.'").click(function(){
            $tb_container.html($("div#photo-cmp-toolbar-top-rated-'.$uniqId.'").html());
        });
        ';
        OW::getDocument()->addOnloadScript($script);
    }

    public static function getToolbar()
    {
        $lang = OW::getLanguage();

        $items = array('latest', 'featured', 'toprated');
        $url = OW::getEventManager()->call('photo.getAddPhotoURL');
        $toolbars = array();
        foreach ( $items as $tbItem )
        {
            if ( OW::getUser()->isAuthenticated() )
            {
                if ( $url !== false )
                {
                    $toolbars[$tbItem][] = array(
                        'href' => 'javascript://',
                        'click' => "$url()",
                        'label' => $lang->text('photo', 'add_new')
                    );
                }
            }
            $toolbars[$tbItem][] = array(
                'href' => OW::getRouter()->urlForRoute('view_photo_list', array('listType' => $tbItem)),
                'label' => $lang->text('base', 'view_all')
            );
        }

        return $toolbars;
    }

    public static function getMenuItems( array $keys, $uniqId )
    {
        $lang = OW::getLanguage();
        $menuItems = array();

        if ( in_array('latest', $keys) )
        {
            $menuItems['latest'] = array(
                'label' => $lang->text('photo', 'menu_latest'),
                'id' => 'photo-cmp-menu-latest-'.$uniqId,
                'contId' => 'photo-cmp-latest-'.$uniqId,
                'active' => true
            );
        }

        if ( in_array('featured', $keys) )
        {
            $menuItems['featured'] = array(
                'label' => $lang->text('photo', 'menu_featured'),
                'id' => 'photo-cmp-menu-featured-'.$uniqId,
                'contId' => 'photo-cmp-featured-'.$uniqId,
            );
        }

        if ( in_array('toprated', $keys) )
        {
            $menuItems['toprated'] = array(
                'label' => $lang->text('photo', 'menu_toprated'),
                'id' => 'photo-cmp-menu-top-rated-'.$uniqId,
                'contId' => 'photo-cmp-top-rated-'.$uniqId,
            );
        }

        return $menuItems;
    }
}