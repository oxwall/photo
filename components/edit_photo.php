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
 * Edit photo component
 *
 * @authors Egor Bulgakov <egor.bulgakov@gmail.com>, Kairat Bakitow <kainisoft@gmail.com>
 * @package ow.plugin.photo.components
 * @since 1.3.2
 */
class PHOTO_CMP_EditPhoto extends OW_Component
{
    public function __construct( $photoId )
    {
        parent::__construct();

        if ( ($photo = PHOTO_BOL_PhotoDao::getInstance()->findById($photoId)) === NULL ||
            ($album = PHOTO_BOL_PhotoAlbumDao::getInstance()->findById($photo->albumId)) === null ||
            !($album->userId == OW::getUser()->getId() || OW::getUser()->isAuthorized('photo')) )
        {
            $this->setVisible(FALSE);
            
            return;
        }
        
        $this->addForm(new PHOTO_CLASS_EditForm($photo->id));
        
        $newsfeedAlbum = PHOTO_BOL_PhotoAlbumService::getInstance()->getNewsfeedAlbum($album->userId);
        $exclude = array();
        
        if ( !empty($newsfeedAlbum) )
        {
            $exclude[] = $newsfeedAlbum->id;
        }

        $this->addComponent('albumNameList', OW::getClassInstance('PHOTO_CMP_AlbumNameList', OW::getUser()->getId(), $exclude));
        $language = OW::getLanguage();
        
        OW::getDocument()->addOnloadScript(
            UTIL_JsGenerator::composeJsString(';var panel = $(document.getElementById("photo_edit_form"));
                var albumList = $(".ow_dropdown_list", panel);
                var albumInput = $("input[name=\'album\']", panel);
                var album = {$album};
                var hideAlbumList = function()
                {
                    albumList.hide();
                    $(".upload_photo_spinner", panel).removeClass("ow_dropdown_arrow_up").addClass("ow_dropdown_arrow_down");
                };
                var showAlbumList = function()
                {
                    albumList.show();
                    $(".upload_photo_spinner", panel).removeClass("ow_dropdown_arrow_down").addClass("ow_dropdown_arrow_up");
                };

                $(".upload_photo_spinner", panel).add(albumInput).on("click", function( event )
                {
                    if ( albumList.is(":visible") )
                    {
                        hideAlbumList();
                    }
                    else
                    {
                        showAlbumList();
                    }

                    event.stopPropagation();
                });

                albumList.find("li").on("click", function()
                {
                    hideAlbumList();
                    owForms["photo-edit-form"].removeErrors();
                }).eq(0).on("click", function()
                {
                    albumInput.val({$create_album});
                    $(".new-album", panel).show();
                    $("input[name=\'album-name\']", panel).val({$album_name});
                    $("textarea", panel).val({$album_desc});
                }).end().slice(2).on("click", function()
                {
                    albumInput.val($(this).data("name"));
                    $(".new-album", panel).hide();
                    $("input[name=\'album-name\']", panel).val(albumInput.val());
                    $("textarea", panel).val("");
                });

                $(document).on("click", function( event )
                {
                    if ( event.target.id === "ajax-upload-album" )
                    {
                        event.stopPropagation();

                        return false;
                    }

                    hideAlbumList();
                });
                
                OW.bind("base.onFormReady.photo-edit-form", function()
                {
                    if ( album.name == {$newsfeedAlbumName} )
                    {
                        this.getElement("album-name").validators.length = 0;
                        this.getElement("album-name").addValidator({
                            validate : function( value ){
                            if(  $.isArray(value) ){ if(value.length == 0  ) throw {$required}; return;}
                            else if( !value || $.trim(value).length == 0 ){ throw {$required}; }
                            },
                            getErrorMessage : function(){ return {$required} }
                        });
                        this.bind("submit", function()
                        {
                            
                        });
                    }
                });
                '
            ,
            array(
                'create_album' => $language->text('photo', 'create_album'),
                'album_name' => $language->text('photo', 'album_name'),
                'album_desc' => $language->text('photo', 'album_desc'),
                'album' => get_object_vars($album),
                'newsfeedAlbumName' => OW::getLanguage()->text('photo', 'newsfeed_album'),
                'required' => OW::getLanguage()->text('base', 'form_validator_required_error_message')
            ))
        );
    }
}
