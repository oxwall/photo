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
 * @package ow.plugin.photo.classes
 * @since 1.6.1
 */
class PHOTO_CLASS_AlbumEditForm extends PHOTO_CLASS_AbstractPhotoForm
{
    const FORM_NAME = 'albumEditForm';
    const ELEMENT_ALBUM_ID = 'album-id';
    const ELEMENT_ALBUM_NAME = 'albumName';
    const ELEMENT_DESC = 'desc';

    public function __construct( $albumId )
    {
        parent::__construct(self::FORM_NAME);
        
        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($albumId);
        
        $this->setAction(OW::getRouter()->urlForRoute('photo.ajax_update_photo'));
        $this->setAjax(true);
        $this->setAjaxResetOnSuccess(false);

        $albumIdField = new HiddenField(self::ELEMENT_ALBUM_ID);
        $albumIdField->setValue($album->id);
        $albumIdField->setRequired();
        $albumIdField->addValidator(new PHOTO_CLASS_AlbumOwnerValidator());
        $this->addElement($albumIdField);
        
        $albumNameField = new TextField(self::ELEMENT_ALBUM_NAME);
        $albumNameField->setValue($album->name);
        $albumNameField->setRequired();
        
        if ( $album->name != trim(OW::getLanguage()->text('photo', 'newsfeed_album')) )
        {
            $albumNameField->addValidator(new PHOTO_CLASS_AlbumNameValidator(true, null, $album->name));
        }
        
        $albumNameField->addAttribute('class', 'ow_photo_album_name_input');
        $this->addElement($albumNameField);
        
        $desc = new Textarea(self::ELEMENT_DESC);
        $desc->setValue(!empty($album->description) ? $album->description : NULL);
        $desc->setHasInvitation(TRUE);
        $desc->setInvitation(OW::getLanguage()->text('photo', 'describe_photo'));
        $desc->addAttribute('class', 'ow_photo_album_description_textarea');
        $this->addElement($desc);

        $this->triggerReady(array(
            'albumId' => $albumId
        ));
    }

    public function getOwnElements()
    {
        return array(
            self::ELEMENT_ALBUM_ID,
            self::ELEMENT_ALBUM_NAME,
            self::ELEMENT_DESC
        );
    }
}
