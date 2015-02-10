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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.mobile.controllers
 * @since 1.6.0
 */
class PHOTO_MCLASS_UploadForm extends Form
{
    public function __construct( )
    {
        parent::__construct('upload-form');

        $language = OW::getLanguage();

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $fileField = new FileField('photo');
        //$fileField->setRequired(true);
        $this->addElement($fileField);

        // album Field
        $albumField = new TextField('album');
        $albumField->setRequired(true);
        $albumField->setHasInvitation(true);
        $albumField->setId('album_input');
        $albumField->setInvitation($language->text('photo', 'create_album'));
        $this->addElement($albumField);

        // description Field
        $descField = new Textarea('description');
        $descField->setHasInvitation(true);
        $descField->setInvitation($language->text('photo', 'describe_photo'));
        $this->addElement($descField);

        $cancel = new Submit('cancel', false);
        $cancel->setValue($language->text('base', 'cancel_button'));
        $this->addElement($cancel);

        $submit = new Submit('submit', false);
        $this->addElement($submit);
    }
}