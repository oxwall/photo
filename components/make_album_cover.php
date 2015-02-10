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
class PHOTO_CMP_MakeAlbumCover extends OW_Component
{
    public function __construct( $albumId, $photoId = NULL, $userId = NULL )
    {
        parent::__construct();
        
        if ( empty($userId) )
        {
            $userId = OW::getUser()->getId();
        }
        
        if ( empty($userId) || ($album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId)) === null ||
            ($album->userId != $userId || !OW::getUser()->isAuthorized('photo', 'view')) )
        {
            $this->setVisible(FALSE);
            
            return;
        }
        
        if ( $photoId === NULL && !PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->isAlbumCoverExist($albumId) )
        {
            $this->setVisible(FALSE);
            
            return;
        }
        
        $storage = OW::getStorage();

        if ( empty($photoId) )
        {
            if ( $storage instanceof BASE_CLASS_FileStorage )
            {
                $photoPath = PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->getAlbumCoverPathByAlbumId($albumId);
            }
            else
            {
                $photoPath = PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->getAlbumCoverUrlByAlbumId($albumId, true);
            }

            $info = getimagesize($photoPath);

            if ( $info['0'] < 330 || $info['1'] < 330 )
            {
                $this->assign('imgError', OW::getLanguage()->text('photo', 'to_small_cover_img'));

                return;
            }

            $this->assign('coverUrl', PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->getAlbumCoverUrlByAlbumId($albumId, TRUE));
        }
        else
        {
            $photo = PHOTO_BOL_PhotoDao::getInstance()->findById($photoId);
            $this->assign('coverUrl', PHOTO_BOL_PhotoDao::getInstance()->getPhotoUrl($photo->id, $photo->hash, FALSE));

            if ( !empty($photo->dimension) )
            {
                $info = json_decode($photo->dimension, true);

                if ( $info[PHOTO_BOL_PhotoService::TYPE_ORIGINAL]['0'] < 330 || $info[PHOTO_BOL_PhotoService::TYPE_ORIGINAL]['1'] < 330 )
                {
                    $this->assign('imgError', OW::getLanguage()->text('photo', 'to_small_cover_img'));

                    return;
                }
            }
            else
            {
                if ( $storage instanceof BASE_CLASS_FileStorage )
                {
                    $photoPath = PHOTO_BOL_PhotoDao::getInstance()->getPhotoPath($photo->id, $photo->hash, PHOTO_BOL_PhotoService::TYPE_ORIGINAL);
                }
                else
                {
                    $photoPath = PHOTO_BOL_PhotoDao::getInstance()->getPhotoUrl($photo->id, $photo->hash, FALSE);
                }
        
                $info = getimagesize($photoPath);
                
                if ( $info['0'] < 330 || $info['1'] < 330 )
                {
                    $this->assign('imgError', OW::getLanguage()->text('photo', 'to_small_cover_img'));
            
                    return;
                }
            }
        }
        
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('base')->getStaticCssUrl() . 'jquery.Jcrop.css');
        OW::getDocument()->addScript(OW::getPluginManager()->getPlugin('base')->getStaticJsUrl() . 'jquery.Jcrop.js');
        
        $form = new PHOTO_CLASS_MakeAlbumCover();
        $form->getElement('albumId')->setValue($albumId);
        $form->getElement('photoId')->setValue($photoId);
        $this->addForm($form);
    }
}
