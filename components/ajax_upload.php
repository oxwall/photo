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
 * AJAX Upload photo component
 *
 * @author Kairat Bakitow <kainisoft@gmail.com>
 * @package ow.plugin.photo.components
 * @since 1.6.1
 */
class PHOTO_CMP_AjaxUpload extends OW_Component
{
    public function __construct( $albumId = NULL, $albumName = NULL, $albumDescription = null, $url = NULL )
    {
        if ( !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $this->setVisible(FALSE);
            
            return;
        }
        
        $userId = OW::getUser()->getId();
        $document = OW::getDocument();
        
        PHOTO_BOL_PhotoTemporaryService::getInstance()->deleteUserTemporaryPhotos($userId);

        $plugin = OW::getPluginManager()->getPlugin('photo');

        $document->addStyleSheet($plugin->getStaticCssUrl() . 'photo_upload.css');
        $document->addScript($plugin->getStaticJsUrl() . 'jQueryRotate.min.js');
        $document->addScript($plugin->getStaticJsUrl() . 'codemirror.min.js');
        $document->addScript($plugin->getStaticJsUrl() . 'upload.js');
        
        $document->addScriptDeclarationBeforeIncludes(
            UTIL_JsGenerator::composeJsString(';window.ajaxPhotoUploadParams = {};
                Object.defineProperties(ajaxPhotoUploadParams, {
                    actionUrl: {
                        value: {$url},
                        writable: false,
                        enumerable: true
                    },
                    maxFileSize: {
                        value: {$size},
                        writable: false,
                        enumerable: true
                    },
                    deleteAction: {
                        value: {$deleteAction},
                        writable: false,
                        enumerable: true
                    }
                });',
                array(
                    'url' => OW::getRouter()->urlForRoute('photo.ajax_upload'),
                    'size' => PHOTO_BOL_PhotoService::getInstance()->getMaxUploadFileSize(),
                    'deleteAction' => OW::getRouter()->urlForRoute('photo.ajax_upload_delete')
                )
            )
        );
        $document->addOnloadScript(';window.ajaxPhotoUploader.init();');
        
        $this->addForm(new PHOTO_CLASS_AjaxUploadForm('user', $userId, $albumId, $albumName, $albumDescription, $url));
        $newsfeedAlbum = PHOTO_BOL_PhotoAlbumService::getInstance()->getNewsfeedAlbum($userId);
        $this->assign('albumNameList', PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumNameListByUserId($userId, !empty($newsfeedAlbum) ? array($newsfeedAlbum->id) : array()));
        
        $language = OW::getLanguage();
        $language->addKeyForJs('photo', 'not_all_photos_uploaded');
        $language->addKeyForJs('photo', 'size_limit');
        $language->addKeyForJs('photo', 'type_error');
        $language->addKeyForJs('photo', 'dnd_support');
        $language->addKeyForJs('photo', 'dnd_not_support');
        $language->addKeyForJs('photo', 'drop_here');
        $language->addKeyForJs('photo', 'please_wait');
        $language->addKeyForJs('photo', 'create_album');
        $language->addKeyForJs('photo', 'album_name');
        $language->addKeyForJs('photo', 'album_desc');
        $language->addKeyForJs('photo', 'describe_photo');
        $language->addKeyForJs('photo', 'photo_upload_error');
    }
}
