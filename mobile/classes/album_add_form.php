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

class PHOTO_MCLASS_AlbumAddForm extends Form
{
    public function __construct()
    {
        parent::__construct('add-album');

        $this->setEnctype(Form::ENCTYPE_MULTYPART_FORMDATA);

        $fileField = new FileField('photo');
        $fileField->addValidator( new PHOTO_MCLASS_PhotoRequiredValidator() );
        $this->addElement($fileField);

        $albumName = new TextField('album-name');
        $albumName->setRequired(true);
        $albumName->setHasInvitation(true);
        $albumName->setInvitation(OW::getLanguage()->text('photo', 'album_name'));
        $this->addElement($albumName);

        $desc = new Textarea('desc');
        $desc->setHasInvitation(true);
        $desc->setInvitation(OW::getLanguage()->text('photo', 'mobile_album_desc'));
        $this->addElement($desc);

        $photoDesc = new Textarea('photodesc');
        $photoDesc->setHasInvitation(true);
        $photoDesc->setInvitation(OW::getLanguage()->text('photo', 'mobile_photo_desc'));
        $this->addElement($photoDesc);

        $this->addElement(new Submit('add'));
    }

    /**
     * Process form values
     *
     * @param integer $userId
     * @param array $file
     * @return object
     */
    public function process($userId, array $file)
    {
        $values = $this->getValues();

        $tmpPhotoService = PHOTO_BOL_PhotoTemporaryService::getInstance();
        $photoService = PHOTO_BOL_PhotoService::getInstance();

        // upload file
        $tmpPhotoService->addTemporaryPhoto($file['tmp_name'], $userId, 1);
        $tmpList = $tmpPhotoService->findUserTemporaryPhotos($userId, 'order');
        $tmpPhoto = array_shift(array_reverse($tmpList));


        $albumService = PHOTO_BOL_PhotoAlbumService::getInstance();
        $albumName = htmlspecialchars(trim($values['album-name']));
        $album = $albumService->findAlbumByName($albumName, $userId);

        // check album exists
        if ( !$album )
        {
            $album = new PHOTO_BOL_PhotoAlbum();
            $album->name = $albumName;
            $album->description = htmlspecialchars(trim($values['desc']));
            $album->userId = $userId;
            $album->entityId = $userId;
            $album->entityType = 'user';
            $album->createDatetime = time();

            $albumService->addAlbum($album);
        }

        // upload photo
        $photo = $tmpPhotoService->moveTemporaryPhoto($tmpPhoto['dto']->id, $album->id, $values['photodesc']);

        if ( $photo )
        {
            BOL_AuthorizationService::getInstance()->trackAction('photo', 'upload');

            $photoService->createAlbumCover($album->id, array($photo));
            $photoService->triggerNewsfeedEventOnSinglePhotoAdd($album, $photo);

            $photoParams = array('addTimestamp' => $photo->addDatetime, 'photoId' => $photo->id, 'hash' => $photo->hash, 'description' => $photo->description);
            $event = new OW_Event(PHOTO_CLASS_EventHandler::EVENT_ON_PHOTO_ADD, array($photoParams));
            OW::getEventManager()->trigger($event);

            $photo = $photoService->findPhotoById($photo->id);

            return $photo;
        }
    }
}
