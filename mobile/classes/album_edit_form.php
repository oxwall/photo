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

class PHOTO_MCLASS_AlbumEditForm extends Form
{
    public function __construct()
    {
        parent::__construct('edit-album');

        $albumName = new TextField('album-name');
        $albumName->setRequired(true);
        $albumName->setHasInvitation(true);
        $albumName->setInvitation(OW::getLanguage()->text('photo', 'album_name'));
        $this->addElement($albumName);

        $desc = new Textarea('desc');
        $desc->setHasInvitation(true);
        $desc->setInvitation(OW::getLanguage()->text('photo', 'mobile_album_desc'));
        $this->addElement($desc);

        $this->addElement(new Submit('save'));
    }

    /**
     * Process form values
     *
     * @param integer $albumId
     * @return void
     */
    public function process($albumId)
    {
        $values = $this->getValues();
        $photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();
        $album = $photoAlbumService->findAlbumById($albumId);

        if ( $album->name != trim(OW::getLanguage()->text('photo', 'newsfeed_album')) )
        {
            $album->name = htmlspecialchars(trim($values['album-name']));
        }

        $album->description = htmlspecialchars(trim($values['desc']));

        if ( !empty($values['privacy']) && OW::getPluginManager()->isPluginActive('privacy') )
        {
            $album->privacy = in_array($values['privacy'], array('everybody', 'friends_only', 'only_for_me')) ? $values['privacy'] : 'everybody';
        }

        if ( $photoAlbumService->updateAlbum($album) )
        {
            OW::getEventManager()->trigger(
                new OW_Event(PHOTO_CLASS_EventHandler::EVENT_ON_FORM_COMPLETE, array('form' => $this), array('album' => $album))
            );
        }
    }
}
