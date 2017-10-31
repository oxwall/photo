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

class PHOTO_MCLASS_PhotoEditForm extends Form
{
    public function __construct()
    {
        parent::__construct('edit-photo');

        $language = OW::getLanguage();

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

        $this->addElement(new Submit('save'));
    }

    /**
     * Process form values
     *
     * @param integer $photoId
     * @return Object
     */
    public function process($photoId)
    {
        $values = $this->getValues();
        $userId = OW::getUser()->getId();

        $photo = PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);
        $album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);

        $isNewAlbum = false;

        if ( ($albumName = htmlspecialchars(trim($values['album']))) != $album->name )
        {
            if ( ($album = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumByName($albumName, $userId)) === null )
            {
                $album = new PHOTO_BOL_PhotoAlbum();
                $album->name = $albumName;
                $album->userId = $userId;
                $album->entityId = $userId;
                $album->entityType = 'user';
                $album->createDatetime = time();

                PHOTO_BOL_PhotoAlbumService::getInstance()->addAlbum($album);
                $isNewAlbum = true;
            }
        }

        if ( $photo->albumId != $album->id )
        {
            OW::getEventManager()->trigger(
                new OW_Event(PHOTO_CLASS_EventHandler::EVENT_BEFORE_PHOTO_MOVE,
                    array(
                        'fromAlbum' => $photo->albumId,
                        'toAlbum' => $album->id,
                        'photoIdList' => array($photo->id)
                    )
                )
            );

            if ( PHOTO_BOL_PhotoService::getInstance()->movePhotosToAlbum(array($photo->id), $album->id, $isNewAlbum) )
            {
                OW::getEventManager()->trigger(
                    new OW_Event(PHOTO_CLASS_EventHandler::EVENT_AFTER_PHOTO_MOVE,
                        array(
                            'fromAlbum' => $photo->albumId,
                            'toAlbum' => $album->id,
                            'photoIdList' => array($photo->id)
                        )
                    )
                );
            }
        }

        $description = htmlspecialchars(trim($values['description']));

        if ( $photo->description != $description )
        {
            $photo->description = $description;
            PHOTO_BOL_PhotoService::getInstance()->updatePhoto($photo);

            BOL_EntityTagDao::getInstance()->deleteItemsForEntityItem($photo->id, 'photo');
            BOL_TagService::getInstance()->updateEntityTags($photo->id, 'photo', PHOTO_BOL_PhotoService::getInstance()->descToHashtag($photo->description));
            PHOTO_BOL_SearchService::getInstance()->deleteSearchItem(PHOTO_BOL_SearchService::ENTITY_TYPE_PHOTO, $photo->id);
            PHOTO_BOL_SearchService::getInstance()->addSearchIndex(PHOTO_BOL_SearchService::ENTITY_TYPE_PHOTO, $photo->id, $photo->description);
        }

        return PHOTO_BOL_PhotoService::getInstance()->findPhotoById($photoId);
    }
}
