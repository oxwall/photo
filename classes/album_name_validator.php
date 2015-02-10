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
class PHOTO_CLASS_AlbumNameValidator extends OW_Validator
{
    private $checkDuplicate;
    private $userId;
    private $albumName;

    public function __construct( $checkDuplicate = TRUE, $userId = NULL, $albumName = NULL )
    {
        $this->errorMessage = OW::getLanguage()->text('photo', 'newsfeed_album_error_msg');
        $this->checkDuplicate = $checkDuplicate;
        $this->albumName = $albumName;
        
        if ( $userId !== NULL )
        {
            $this->userId = (int)$userId;
        }
        else
        {
            $this->userId = OW::getUser()->getId();
        }
    }

    public function isValid( $albumName )
    {
        if ( strcasecmp(trim($this->albumName), OW::getLanguage()->text('photo', 'newsfeed_album')) === 0 )
        {
            return TRUE;
        }
        
        if ( strcasecmp(trim($albumName), OW::getLanguage()->text('photo', 'newsfeed_album')) === 0 )
        {
            return FALSE;
        }
        elseif ( $this->checkDuplicate && strcasecmp($albumName, $this->albumName) !== 0 && PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumByName($albumName, $this->userId) !== NULL )
        {
            $this->setErrorMessage(OW::getLanguage()->text('photo', 'album_name_error'));
            
            return FALSE;
        }
        
        return TRUE;
    }

    public function getJsValidator()
    {
        return UTIL_JsGenerator::composeJsString('{
            validate : function( value )
            {
                if ( {$albumName} && {$albumName}.trim().toLowerCase() == {$newsfeedAdmin}.toString().trim().toLowerCase() )
                {
                    return true;
                }
                    
                if ( value.toString().trim().toLowerCase() == {$newsfeedAdmin}.toString().trim().toLowerCase() )
                {
                    throw "' . $this->errorMessage . '";
                }
            }
        }', array(
            'albumName' => $this->albumName,
            'newsfeedAdmin' => OW::getLanguage()->text('photo', 'newsfeed_album')
        ));
    }
}
