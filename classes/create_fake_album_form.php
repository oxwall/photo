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
 * @since 1.7.6
 */
class PHOTO_CLASS_CreateFakeAlbumForm extends PHOTO_CLASS_AbstractPhotoForm
{
    const FORM_NAME = 'create_fake_album';
    const ELEMENT_ALBUM_NAME = 'album_name';
    const ELEMENT_ALBUM_DESC = 'album_desc';
    const SUBMIT_SUBMIT = 'submit';

    public function __construct()
    {
        parent::__construct(self::FORM_NAME);

        $language = OW::getLanguage();

        $this->setAjax();
        $this->setAjaxResetOnSuccess(false);
        $this->setAction(OW::getRouter()->urlFor('PHOTO_CTRL_AjaxUpload', 'checkFakeAlbumData'));
        $this->bindJsFunction(self::BIND_SUCCESS, UTIL_JsGenerator::composeJsString('function( data )
        {
            if ( !data.result )
            {
                var form = owForms[this.name];

                Object.keys(data.errors).forEach(function( item )
                {
                    var arr = data.errors[item];

                    if ( arr.length !== 0 )
                    {
                        form.getElement(item).showError(arr.shift());
                    }
                });

                return;
            }

            if ( OW.getActiveFloatBox() ) OW.getActiveFloatBox().close();

            var formData = data.data;
            var params = {
                albumId: 0,
                albumName: formData[{$album_name}],
                albumDescription: formData[{$album_desc}],
                url: "",
                data: formData
            };
            var ajaxUploadPhotoFB = OW.ajaxFloatBox("PHOTO_CMP_AjaxUpload", params, {
                title: {$title},
                width: "746px",
                onLoad: function()
                {
                    OW.trigger("photo.ready_fake_album", [formData]);
                }
            });

            ajaxUploadPhotoFB.bind("close", function()
            {
                if ( ajaxPhotoUploader.isHasData() )
                {
                    if ( confirm({$confirm}) )
                    {
                        OW.trigger("photo.onCloseUploaderFloatBox");

                        return true;
                    }

                    return false;
                }
                else
                {
                    OW.trigger("photo.onCloseUploaderFloatBox");
                }
            });
        }', array(
            'album_name' => self::ELEMENT_ALBUM_NAME,
            'album_desc' => self::ELEMENT_ALBUM_DESC,
            'title' => $language->text('photo', 'upload_photos'),
            'confirm' => $language->text('photo', 'close_alert')
        )));

        $albumNameInput = new TextField(self::ELEMENT_ALBUM_NAME);
        $albumNameInput->setRequired();
        $albumNameInput->addValidator(new PHOTO_CLASS_AlbumNameValidator(false));
        $albumNameInput->setHasInvitation(true);
        $albumNameInput->setInvitation($language->text('photo', 'album_name'));
        $this->addElement($albumNameInput);

        $albumDescInput = new Textarea(self::ELEMENT_ALBUM_DESC);
        $albumDescInput->setHasInvitation(true);
        $albumDescInput->setInvitation($language->text('photo', 'album_desc'));
        $this->addElement($albumDescInput);

        $submit = new Submit(self::SUBMIT_SUBMIT);
        $submit->setValue($language->text('photo', 'add_photos'));
        $this->addElement($submit);

        $this->triggerReady();
    }

    public function getOwnElements()
    {
        return array(
            self::ELEMENT_ALBUM_NAME,
            self::ELEMENT_ALBUM_DESC,
            self::SUBMIT_SUBMIT
        );
    }
}
