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
 * Mobile init
 *
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>
 * @package ow.plugin.photo.mobile
 * @since 1.6.0
 */
OW::getRouter()->addRoute(new OW_Route('photo_user_change_album_cover', 'photo/change-album-cover/:id/', 'PHOTO_MCTRL_Photo', 'changeAlbumCover'));
OW::getRouter()->addRoute(new OW_Route('photo_user_edit_album', 'photo/edit-album/:id/', 'PHOTO_MCTRL_Photo', 'editAlbum'));
OW::getRouter()->addRoute(new OW_Route('photo_user_delete_album', 'photo/delete-album/:id/', 'PHOTO_MCTRL_Photo', 'deleteAlbum'));
OW::getRouter()->addRoute(new OW_Route('photo_user_create_album', 'photo/create-album', 'PHOTO_MCTRL_Photo', 'createAlbum'));
OW::getRouter()->addRoute(new OW_Route('photo_user_albums', 'photo/useralbums/:user/', 'PHOTO_MCTRL_Photo', 'albums'));
OW::getRouter()->addRoute(new OW_Route('photo_user_album', 'photo/useralbum/:user/:album', 'PHOTO_MCTRL_Photo', 'album'));
OW::getRouter()->addRoute(new OW_Route('photo_list_index', 'photo/', 'PHOTO_MCTRL_Photo', 'viewList'));
OW::getRouter()->addRoute(new OW_Route('view_photo_list', 'photo/viewlist/:listType', 'PHOTO_MCTRL_Photo', 'viewList'));
OW::getRouter()->addRoute(new OW_Route('photo_upload', 'photo/upload', 'PHOTO_MCTRL_Upload', 'photo'));
OW::getRouter()->addRoute(new OW_Route('photo_upload_album', 'photo/upload/:album', 'PHOTO_MCTRL_Upload', 'photo'));
OW::getRouter()->addRoute(new OW_Route('view_photo', 'photo/view/:id', 'PHOTO_MCTRL_Photo', 'view'));
OW::getRouter()->addRoute(new OW_Route('view_photo_type', 'photo/view/:id/:listType', 'PHOTO_MCTRL_Photo', 'view', array('listType' => array('default' => 'latest'))));

PHOTO_MCLASS_EventHandler::getInstance()->init();