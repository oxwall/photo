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
class PHOTO_CLASS_AjaxUploadForm extends Form
{
    public function __construct( $entityType, $entityId, $albumId = NULL, $albumName = NULL, $albumDescription = NULL, $url = NULL )
    {
        parent::__construct('ajax-upload');
        
        $this->setAjax(TRUE);
        $this->setAjaxResetOnSuccess(FALSE);
        $this->setAction(OW::getRouter()->urlForRoute('photo.ajax_upload_submit'));
        $this->bindJsFunction('success', 
            UTIL_JsGenerator::composeJsString('function( data )
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
            ))
        );
        
        $language = OW::getLanguage();

        $albumField = new TextField('album');
        $albumField->setRequired();
        $albumField->addAttribute('class', 'ow_dropdown_btn ow_inputready ow_cursor_pointer');
        $albumField->addAttribute('autocomplete', 'off');
        $albumField->addAttribute('readonly');
        
        $albumNameField = new TextField('album-name');
        $albumNameField->setRequired();
        $albumNameField->addValidator(new PHOTO_CLASS_AlbumNameValidator(FALSE));
        $albumNameField->addAttribute('class', 'ow_smallmargin invitation');
        $this->addElement($albumNameField);
        
        $desc = new Textarea('description');
        $desc->addAttribute('class', 'invitation');
        
        if ( !empty($albumDescription) )
        {
            $desc->setValue($albumDescription);
        }
        else
        {
            $desc->setValue($language->text('photo', 'album_desc'));
        }
        
        $this->addElement($desc);

        $userId = OW::getUser()->getId();

        if ( !empty($albumId) && ($album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId, $userId)) !== NULL && $album->userId == $userId && $album->name != trim(OW::getLanguage()->text('photo', 'newsfeed_album')) )
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
            $event = new BASE_CLASS_EventCollector(PHOTO_CLASS_EventHandler::EVENT_SUGGEST_DEFAULT_ALBUM, array(
                'userId' => $userId,
                'entityType' => $entityType,
                'entityId' => $entityId
            ));
            OW::getEventManager()->trigger($event);
            $data = $event->getData();

            if ( !empty($data) )
            {
                $albumField->setValue($data[0]);
                $albumNameField->setValue($data[0]);
            }
            else
            {
                $albumField->setValue($language->text('photo', 'choose_existing_or_create'));
                $albumNameField->setValue($language->text('photo', 'album_name'));
            }
        }

        $this->addElement($albumField);
        
        $submit = new Submit('submit');
        $submit->addAttribute('class', 'ow_ic_submit ow_positive');
        $this->addElement($submit);
    }
}
