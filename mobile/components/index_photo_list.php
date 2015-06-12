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
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow.plugin.photo.mobile.components
 * @since 1.7.5
 */

class PHOTO_MCMP_IndexPhotoList extends OW_MobileComponent
{
    protected $visiblePhotoCount = 0;
    
    public function __construct( $params )
    {
        parent::__construct();

        $this->visiblePhotoCount = !empty($params['photoCount']) ? (int) $params['photoCount'] : 8;
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

        $latest = $photoService->findPhotoList('latest', 1, $this->visiblePhotoCount, NULL, PHOTO_BOL_PhotoService::TYPE_PREVIEW);
        $this->assign('latest', $latest);

        $featured = $photoService->findPhotoList('featured', 1, $this->visiblePhotoCount, NULL, PHOTO_BOL_PhotoService::TYPE_PREVIEW);
        $this->assign('featured', $featured);

        $toprated = $photoService->findPhotoList('toprated', 1, $this->visiblePhotoCount, NULL, PHOTO_BOL_PhotoService::TYPE_PREVIEW);
        $this->assign('toprated', $toprated);

        $items = array('latest', 'toprated');
        if ( $featured )
        {
            $items[] = 'featured';
        }
        
        $menuItems = $this->getMenuItems($items, $uniqId);
        $this->assign('items', $menuItems);

        $this->assign('wrapBox', $wrap);
        $this->assign('boxType', $boxType);
        $this->assign('showTitle', $showTitle);
        $this->assign('url', OW::getEventManager()->call('photo.getAddPhotoURL', array('')));
        $this->assign('uniqId', $uniqId);
        
        $this->setTemplate(OW::getPluginManager()->getPlugin('photo')->getMobileCmpViewDir() . 'index_photo_list.html');
    }

    public function getMenuItems( array $keys, $uniqId )
    {
        $lang = OW::getLanguage();
        $menuItems = array();
        
        $photoService = PHOTO_BOL_PhotoService::getInstance();
        
        if ( in_array('latest', $keys) )
        {
            $count = $photoService->countPhotos('latest');
            
            $menuItems['latest'] = array(
                'label' => $lang->text('photo', 'menu_latest'),
                'id' => 'photo-cmp-menu-latest-'.$uniqId,
                'contId' => 'photo-cmp-latest-'.$uniqId,
                'active' => true,
                'visibility' => ($count > $this->visiblePhotoCount) ? true : false
            );
        }

        if ( in_array('featured', $keys) )
        {
            $count = $photoService->countPhotos('featured');
            
            $menuItems['featured'] = array(
                'label' => $lang->text('photo', 'menu_featured'),
                'id' => 'photo-cmp-menu-featured-'.$uniqId,
                'contId' => 'photo-cmp-featured-'.$uniqId,
                'active' => false,
                'visibility' => ($count > $this->visiblePhotoCount) ? true : false
            );
        }

        if ( in_array('toprated', $keys) )
        {
            $count = $photoService->countPhotos('toprated');
            
            $menuItems['toprated'] = array(
                'label' => $lang->text('photo', 'menu_toprated'),
                'id' => 'photo-cmp-menu-toprated-'.$uniqId,
                'contId' => 'photo-cmp-toprated-'.$uniqId,
                'active' => false,
                'visibility' => ($count > $this->visiblePhotoCount) ? true : false
            );
        }

        return $menuItems;
    }
    
    public static function getToolbar($uniqId)
    {
        $lang = OW::getLanguage();
        
        $items = array('latest', 'featured', 'toprated');
        $toolbars = array();
        foreach ( $items as $tbItem )
        {
            $toolbars[$tbItem] = array(
                'href' => OW::getRouter()->urlForRoute('view_photo_list', array('listType' => $tbItem)),
                'label' => $lang->text('base', 'view_all'),
                'id' => "toolbar-photo-{$tbItem}-".$uniqId
            );
                
            if ( in_array($tbItem, array('featured', 'toprated')) )
            {
                $toolbars[$tbItem]['display'] = 'none';
            }
        }
        
        return $toolbars;
    }
}