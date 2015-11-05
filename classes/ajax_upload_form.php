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
 * @package ow_plugins.photo.classes
 * @since 1.6.1
 */
class PHOTO_CLASS_AjaxUploadForm extends PHOTO_CLASS_AbstractPhotoForm
{
    const FORM_NAME = 'ajax-upload';
    const ELEMENT_ALBUM = 'album';
    const ELEMENT_ALBUM_NAME = 'album-name';
    const ELEMENT_DESCRIPTION = 'description';

    public function __construct( $entityType, $entityId, $albumId = null, $albumName = null, $albumDescription = null, $url = null, $data = null )
    {
        parent::__construct(self::FORM_NAME);
        
        $this->setAjax(true);
        $this->setAjaxResetOnSuccess(false);
        $this->setAction(OW::getRouter()->urlForRoute('photo.ajax_upload_submit'));
        $this->bindJsFunction(self::BIND_SUCCESS, UTIL_JsGenerator::composeJsString('function( data )
        {
            if ( data )
            {
                if ( !data.result )
                {
                    if ( data.msg )
                    {
                        OW.error(data.msg);
                    }
                    else
                    {
                        OW.getLanguageText("photo", "photo_upload_error");
                    }
                }
                else
                {
                    var url = {$url};

                    if ( url )
                    {
                        window.location.href = url;
                    }
                    else if ( data.url )
                    {
                        window.location.href = data.url;
                    }
                }
            }
            else
            {
                OW.error("Server error");
            }
        }', array(
            'url' => $url
        )));
        
        $language = OW::getLanguage();

        $albumField = new TextField(self::ELEMENT_ALBUM);
        $albumField->setRequired();
        $albumField->addAttribute(FormElement::ATTR_CLASS, 'ow_dropdown_btn ow_inputready ow_cursor_pointer');
        $albumField->addAttribute('autocomplete', 'off');
        $albumField->addAttribute(FormElement::ATTR_READONLY);
        
        $albumNameField = new TextField(self::ELEMENT_ALBUM_NAME);
        $albumNameField->setRequired();
        $albumNameField->addValidator(new PHOTO_CLASS_AlbumNameValidator(false));
        $albumNameField->addAttribute('placeholder', $language->text('photo', 'album_name'));
        $this->addElement($albumNameField);
        
        $desc = new Textarea(self::ELEMENT_DESCRIPTION);
        $desc->addAttribute('placeholder', $language->text('photo', 'album_desc'));
        $desc->setValue(!empty($albumDescription) ? $albumDescription : null);
        $this->addElement($desc);

        $userId = OW::getUser()->getId();
        $albumService = PHOTO_BOL_PhotoAlbumService::getInstance();

        if ( !empty($albumId) && ($album = $albumService->findAlbumById($albumId)) !== null && $album->userId == $userId && !$albumService->isNewsfeedAlbum($album) )
        {
            $albumField->setValue($album->name);
            $albumNameField->setValue($album->name);
        }
        elseif ( !empty($albumName) )
        {
            $albumField->setValue($albumName);
            $albumNameField->setValue($albumName);
        }
        else
        {
            $event = OW::getEventManager()->trigger(new BASE_CLASS_EventCollector(PHOTO_CLASS_EventHandler::EVENT_SUGGEST_DEFAULT_ALBUM, array(
                'userId' => $userId,
                'entityType' => $entityType,
                'entityId' => $entityId
            )));
            $eventData = $event->getData();

            if ( !empty($eventData) )
            {
                $value = array_shift($eventData);
                $albumField->setValue($value);
                $albumNameField->setValue($value);
            }
            else
            {
                $albumField->setValue($language->text('photo', 'choose_existing_or_create'));
            }
        }

        $this->addElement($albumField);
        
        $submit = new Submit('submit');
        $submit->addAttribute('class', 'ow_ic_submit ow_positive');
        $this->addElement($submit);

        $this->triggerReady(array(
            'entityType' => $entityType,
            'entityId' => $entityId,
            'albumId' => $albumId,
            'albumName' => $albumName,
            'albumDescription' => $albumDescription,
            'url' => $url,
            'data' => $data
        ));
    }

    public function getOwnElements()
    {
        return array(
            self::ELEMENT_ALBUM,
            self::ELEMENT_ALBUM_NAME,
            self::ELEMENT_DESCRIPTION
        );
    }
}
