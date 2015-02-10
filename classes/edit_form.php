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
 * @authors Egor Bulgakov <egor.bulgakov@gmail.com>, Kairat Bakitow <kainisoft@gmail.com>
 * @package ow_plugins.photo.classes
 * @since 1.3.2
 */
class PHOTO_CLASS_EditForm extends Form
{
    public function __construct( $photoId = NULL )
    {
        parent::__construct('photo-edit-form');
        
        $this->setAjax(TRUE);
        $this->setAction(OW::getRouter()->urlFor('PHOTO_CTRL_Photo', 'ajaxUpdatePhoto'));
        $this->bindJsFunction('success', 'function( data )
            {
                OW.trigger("photo.afterPhotoEdit", data);
            }');
        
        $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);
        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);
        
        $photoIdField = new HiddenField('photoId');
        $photoIdField->setRequired(TRUE);
        $photoIdField->setValue($photo->id);
        $photoIdField->addValidator(new PHOTO_CLASS_PhotoOwnerValidator());
        $this->addElement($photoIdField);

        $albumField = new TextField('album');
        $albumField->setId('ajax-upload-album');
        $albumField->setRequired();
        $albumField->setValue($album->name);
        $albumField->setLabel(OW::getLanguage()->text('photo', 'create_album'));
        $albumField->addAttribute('class', 'ow_dropdown_btn ow_inputready ow_cursor_pointer');
        $albumField->addAttribute('autocomplete', 'off');
        $albumField->addAttribute('readonly');
        $this->addElement($albumField);
        
        $albumNameField = new TextField('album-name');
        $albumNameField->setRequired();
        $albumNameField->setValue($album->name);
        $albumNameField->addValidator(new PHOTO_CLASS_AlbumNameValidator(FALSE, NULL, $album->name));
        $albumNameField->setHasInvitation(TRUE);
        $albumNameField->setInvitation(OW::getLanguage()->text('photo', 'album_name'));
        $albumNameField->addAttribute('class', 'ow_smallmargin invitation');
        $this->addElement($albumNameField);
        
        $desc = new Textarea('description');
        $desc->setHasInvitation(TRUE);
        $desc->setInvitation(OW::getLanguage()->text('photo', 'album_desc'));
        $this->addElement($desc);
        
        $photoDesc = new PHOTO_CLASS_HashtagFormElement('photo-desc');
        $photoDesc->setValue($photo->description);
        $photoDesc->setLabel(OW::getLanguage()->text('photo', 'album_desc'));
        $this->addElement($photoDesc);

        $submit = new Submit('edit');
        $submit->setValue(OW::getLanguage()->text('photo', 'btn_edit'));
        $this->addElement($submit);
    }
}
