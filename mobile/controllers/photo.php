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
 * @author Egor Bulgakov <egor.bulgakov@gmail.com>, Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow.plugin.photo.mobile.controllers
 * @since 1.6.0
 */
class PHOTO_MCTRL_Photo extends OW_MobileActionController
{
    /**
     * @var PHOTO_BOL_PhotoService
     */
    private $photoService;

    /**
     * @var PHOTO_BOL_PhotoAlbumService
     */
    private $photoAlbumService;

    public function __construct()
    {
        parent::__construct();

        $this->photoService = PHOTO_BOL_PhotoService::getInstance();
        $this->photoAlbumService = PHOTO_BOL_PhotoAlbumService::getInstance();
    }

    /**
     * Delete album
     *
     * @param array $params
     */
    public function deleteAlbum($params)
    {
        $lang = OW::getLanguage();
        $albumId = $params['id'];
        $album = $this->photoAlbumService->findAlbumById($albumId);

        if ( $album && OW::getRequest()->isPost() )
        {
            if ( strcasecmp($album->name, OW::getLanguage()->text('photo', 'newsfeed_album')) === 0 )
            {
                $url = OW_Router::getInstance()->urlForRoute(
                    'photo_user_album',
                    array('user' => BOL_UserService::getInstance()->getUserName($album->userId), 'album' => $album->id)
                );


                OW::getFeedback()->error($lang->text('photo', 'delete_newsfeed_album_error'));
                $this->redirect($url);
            }

            $canEdit = $album->userId == OW::getUser()->getId();
            $canModerate = OW::getUser()->isAuthorized('photo');

            $authorized = $canEdit || $canModerate;

            if ( $authorized )
            {
                $delResult = $this->photoAlbumService->deleteAlbum($albumId);

                if ( $delResult )
                {
                    $url = OW_Router::getInstance()->urlForRoute(
                        'photo_user_albums',
                        array('user' => BOL_UserService::getInstance()->getUserName($album->userId))
                    );

                    OW::getFeedback()->info($lang->text('photo', 'album_deleted'));
                    $this->redirect($url);
                }
                else
                {
                    $url = OW_Router::getInstance()->urlForRoute(
                        'photo_user_album',
                        array('user' => BOL_UserService::getInstance()->getUserName($album->userId), 'album' => $album->id)
                    );

                    OW::getFeedback()->error($lang->text('photo', 'album_delete_not_allowed'));
                    $this->redirect($url);
                }
            }
            else
            {
                $url = OW_Router::getInstance()->urlForRoute(
                    'photo_user_album',
                    array('user' => BOL_UserService::getInstance()->getUserName($album->userId), 'album' => $album->id)
                );

                OW::getFeedback()->error($lang->text('photo', 'album_delete_not_allowed'));
                $this->redirect($url);
            }
        }

        OW::getFeedback()->error($lang->text('photo', 'album_delete_not_allowed'));
        $this->redirect(OW::getRouter()->urlForRoute('base_index'));
    }

    /**
     * Create album
     */
    public function createAlbum()
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthorizationException();
        }

        if ( !OW::getUser()->isAdmin() && !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');
            throw new AuthorizationException($status['msg']);
        }

        $lang = OW::getLanguage();
        $config = OW::getConfig();
        $userQuota = (int) $config->getValue('photo', 'user_quota');
        $userId = OW::getUser()->getId();
        $displayName = BOL_UserService::getInstance()->getDisplayName($userId);
        $user = BOL_UserService::getInstance()->findUserById($userId);

        if ( !($this->photoService->countUserPhotos($userId) <= $userQuota) )
        {
            $this->assign('auth_msg', $lang->text('photo', 'quota_exceeded', array('limit' => $userQuota)));
        }
        else {
            // init form
            $form = new PHOTO_MCLASS_AlbumAddForm();
            $this->addForm($form);

            // validate form
            if ( OW::getRequest()->isPost() && $form->isValid($_POST + $_FILES) )
            {
                $file = $_FILES['photo'];

                // delete old tmp file
                PHOTO_BOL_PhotoTemporaryService::getInstance()->deleteUserTemporaryPhotos($userId);
                $accepted = floatval($config->getValue('photo', 'accepted_filesize') * 1024 * 1024);

                // validate photo
                if ( !UTIL_File::validateImage($file['name']) || $file['size'] > $accepted )
                {
                    OW::getFeedback()->warning($lang->text('photo', 'no_photo_uploaded'));
                    $this->redirect();
                }

                $photo = $form->process($userId, $file);

                if ( !$photo )
                {
                    OW::getFeedback()->warning($lang->text('photo', 'no_photo_uploaded'));
                    $this->redirect();
                }

                if ( $photo->status != PHOTO_BOL_PhotoDao::STATUS_APPROVED )
                {
                    OW::getFeedback()->info(OW::getLanguage()->text('photo', 'photo_uploaded_pending_approval'));

                    $this->redirect(OW::getRouter()->urlForRoute('photo_user_albums', array(
                        'user' => $user->getUsername()
                    )));
                }

                OW::getFeedback()->info($lang->text('photo', 'photos_uploaded', array('count' => 1)));

                $this->redirect(OW::getRouter()->urlForRoute('photo_user_albums', array(
                    'user' => $user->getUsername()
                )));

                return;
            }

            $script = '$("#upload-file-field").change(function(){
                var img = $("#photo-file-prevew");
                var name = $(".owm_upload_img_name_label span");

                img.hide();
                name.text("");

                if (!this.files || !this.files[0]) return;

                if ( window.FileReader ) {
                    var reader = new FileReader();
                    reader.onload = function (e) {
                        img.show().attr("src", e.target.result);
                    }
                    reader.readAsDataURL(this.files[0]);
                } else {
                    name.text(this.files[0].name);
                }
                $(".owm_upload_photo_browse_wrap").addClass("owm_upload_photo_attach_wrap");
            });';
            OW::getDocument()->addOnloadScript($script);
        }

        OW::getDocument()->setHeading($lang->text('photo', 'create_album'));
        OW::getDocument()->setTitle($lang->text('photo', 'meta_title_photo_useralbums', array('displayName' => $displayName)));

        // init view vars
        $this->assign('userName' , $user->getUsername());
    }

    public function viewList($params)
    {
        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');

        if ( !$modPermissions && !OW::getUser()->isAuthorized('photo', 'view') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'view');
            $this->assign('authError', $status['msg']);

            return;
        }

        $type = !empty($params['listType']) ? $params['listType'] : 'latest' ;
        $limit = 12;
        
        $validLists = array('latest','toprated','featured');
        
        if ( !in_array($type, $validLists) )
        {
            $this->redirect(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'latest')));
        }
        
        $menu = $this->getMenu();
        $el = $menu->getElement($type);
        
        $el->setActive(true);
        $this->addComponent('menu', $menu);

        $initialCmp = new PHOTO_MCMP_PhotoList($type, $limit, array());
        $this->addComponent('photos', $initialCmp);

        $checkPrivacy = !OW::getUser()->isAuthorized('photo');
        $total = $this->photoService->countPhotos($type, $checkPrivacy);
        $this->assign('loadMore', $total > $limit);

        if ( OW::getUser()->isAuthenticated() && !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            $id = uniqid('photo_add');
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');

            OW::getDocument()->addScriptDeclaration(UTIL_JsGenerator::composeJsString(
                ';$("#" + {$btn}).on("click", function()
                {
                    OWM.authorizationLimitedFloatbox({$msg});
                });',
                array(
                    'btn' => $id,
                    'msg' => $status['msg']
                )
            ));

            $this->assign('id', $id);
        }
        else
        {
            $this->assign('uploadUrl', OW::getRouter()->urlForRoute('photo_upload'));
        }

        $script = '
        OWM.bind("photo.hide_load_more", function(){
            $("#btn-photo-load-more").hide();
        });

        $("#btn-photo-load-more").click(function(){
            var node = $(this);
            node.addClass("owm_preloader");
            var exclude = $("div.owm_photo_list_item").map(function(){ return $(this).data("ref"); }).get();
            OWM.loadComponent(
                "PHOTO_MCMP_PhotoList",
                {type: "' . $type . '", count:' . $limit . ', exclude: exclude},
                {
                    onReady: function(html){
                        $("#photo-list-cont").append(html);
                        node.removeClass("owm_preloader");
                    }
                }
            );
        });';

        OW::getDocument()->addOnloadScript($script);

        OW::getDocument()->setHeading(OW::getLanguage()->text('photo', 'page_title_browse_photos'));
        OW::getDocument()->setTitle(OW::getLanguage()->text('photo', 'meta_title_photo_'.$type));
        OW::getDocument()->setDescription(OW::getLanguage()->text('photo', 'meta_description_photo_'.$type));
    }

    public function albums( array $params )
    {
        if ( empty($params['user']) || !mb_strlen($username = trim($params['user'])) )
        {
            throw new Redirect404Exception();
        }

        $user = BOL_UserService::getInstance()->findByUsername($username);
        if ( !$user )
        {
            throw new Redirect404Exception();
        }

        $ownerMode = $user->id == OW::getUser()->getId();

        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');

        if ( !OW::getUser()->isAuthorized('photo', 'view') && !$modPermissions && !$ownerMode )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'view');
            $this->assign('authError', $status['msg']);

            return;
        }

        $lang = OW::getLanguage();
        $limit = 6;

        $initialCmp = new PHOTO_MCMP_AlbumList($user->id, $limit, array());
        $this->addComponent('albums',$initialCmp);

        $total = $this->photoAlbumService->countUserAlbums($user->id);
        $this->assign('loadMore', $total > $limit);

        $script = '
        OWM.bind("photo.hide_load_more", function(){
            $("#btn-photo-load-more").hide();
        });

        $("#btn-photo-load-more").click(function(){
            var node = $(this);
            node.addClass("owm_preloader");
            var exclude = $("li.owm_photo_album_list_item").map(function(){ return $(this).data("ref"); }).get();
            OWM.loadComponent(
                "PHOTO_MCMP_AlbumList",
                {userId: "' . $user->id . '", count:' . $limit . ', exclude: exclude},
                {
                    onReady: function(html){
                        $("#album-list-cont").append(html);
                        node.removeClass("owm_preloader");
                    }
                }
            );
        });';

        OW::getDocument()->addOnloadScript($script);

        // add page info
        $displayName = BOL_UserService::getInstance()->getDisplayName($user->id);
        OW::getDocument()->setHeading($ownerMode ? $lang->text('photo', 'my_albums') : $lang->text('photo', 'photo_albums'));
        OW::getDocument()->setTitle($lang->text('photo', 'meta_title_photo_useralbums', array('displayName' => $displayName)));
        $this->assign('displayName', $displayName);

        //--  add context menu --//
        if ( $ownerMode ) {
            $uploadItemId = uniqid('photo_add');
            $isUploadPhotoAllowed = true;

            if (!OW::getUser()->isAuthorized('photo', 'upload')) {
                $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');
                $isPromoted = !empty($status['status'])
                    && $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED;

                if (!$isPromoted) {
                    $isUploadPhotoAllowed = false;
                } else {
                    OW::getDocument()->addScriptDeclaration(UTIL_JsGenerator::composeJsString(
                        ';$("#" + {$btn}).on("click", function(e)
                    {
                        e.preventDefault();
                        OWM.authorizationLimitedFloatbox({$msg});
                    });',
                        array(
                            'btn' => $uploadItemId,
                            'msg' => $status['msg']
                        )
                    ));
                }
            }

            if ( $isUploadPhotoAllowed )
            {
                $contextMenu = [];

                $contextMenu[] = array(
                    'group' => 'photo',
                    'label' => OW::getLanguage()->text('photo', 'upload_photos'),
                    'order' => 1,
                    'class' => null,
                    'href' => OW::getRouter()->urlForRoute('photo_upload'),
                    'id' => $uploadItemId
                );


                $contextMenu[] = array(
                    'group' => 'photo',
                    'label' => OW::getLanguage()->text('photo', 'create_album'),
                    'order' => 2,
                    'class' => null,
                    'href' => OW::getRouter()->urlForRoute('photo_user_create_album')
                );

                $this->addComponent('contextMenu', new BASE_MCMP_ContextAction($contextMenu));
            }
        }

        //--

        $this->assign('username', $user->getUsername());
        $this->assign('totalAlbums', $total);
        $this->assign('isOwner', $ownerMode);
    }

    public function album( array $params )
    {
        if ( !isset($params['user']) || !strlen($username = trim($params['user'])) )
        {
            throw new Redirect404Exception();
        }

        if ( !isset($params['album']) || !($albumId = (int) $params['album']) )
        {
            throw new Redirect404Exception();
        }

        // is owner
        $userDto = BOL_UserService::getInstance()->findByUsername($username);

        if ( $userDto )
        {
            $ownerMode = $userDto->id == OW::getUser()->getId();
        }
        else
        {
            $ownerMode = false;
        }

        $lang = OW::getLanguage();

        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');

        if ( !OW::getUser()->isAuthorized('photo', 'view') && !$modPermissions && !$ownerMode )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'view');
            $this->assign('authError', $status['msg']);

            return;
        }

        $album = $this->photoAlbumService->findAlbumById($albumId);
        if ( !$album )
        {
            throw new Redirect404Exception();
        }

        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'photo_view_album', 'ownerId' => $album->userId, 'viewerId' => OW::getUser()->getId());
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }

        $type = 'user';
        $limit = 12;

        $initialCmp = new PHOTO_MCMP_PhotoList($type, $limit, array(), $albumId);
        $this->addComponent('photos', $initialCmp);

        $total = $this->photoAlbumService->countAlbumPhotos($albumId);
        $this->assign('loadMore', $total > $limit);

        $script = '
        OWM.bind("photo.hide_load_more", function(){
            $("#btn-photo-load-more").hide();
        });

        $("#btn-photo-load-more").click(function(){
            var node = $(this);
            node.addClass("owm_preloader");
            var exclude = $("div.owm_photo_list_item").map(function(){ return $(this).data("ref"); }).get();
            OWM.loadComponent(
                "PHOTO_MCMP_PhotoList",
                {type: "' . $type . '", count:' . $limit . ', exclude: exclude, albumId: ' . $albumId . '},
                {
                    onReady: function(html){
                        $("#photo-list-cont").append(html);
                        node.removeClass("owm_preloader");
                    }
                }
            );
        });';

        $this->assign('ownerMode', $ownerMode);
        $this->assign('userName', $userDto->getUsername());
        OW::getDocument()->addOnloadScript($script);

        $displayName = BOL_UserService::getInstance()->getDisplayName($album->userId);
        OW::getDocument()->setHeading(
            $album->name . ' - ' . $lang->text('photo', 'photos_in_album', array('total' => $total))
        );
        OW::getDocument()->setTitle(
            $lang->text('photo', 'meta_title_photo_useralbum', array('displayName' => $displayName, 'albumName' => $album->name))
        );
        OW::getDocument()->setDescription(
            $lang->text('photo', 'meta_description_photo_useralbum', array('displayName' => $displayName, 'number' => $total))
        );

        //--  add context menu --//
        if ( $ownerMode ) {
            $uploadItemId = uniqid('photo_add');
            $isUploadPhotoAllowed = true;

            if (!OW::getUser()->isAuthorized('photo', 'upload')) {
                $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'upload');
                $isPromoted = !empty($status['status'])
                    && $status['status'] == BOL_AuthorizationService::STATUS_PROMOTED;

                if (!$isPromoted) {
                    $isUploadPhotoAllowed = false;
                } else {
                    OW::getDocument()->addScriptDeclaration(UTIL_JsGenerator::composeJsString(
                        ';$("#" + {$btn}).on("click", function(e)
                    {
                        e.preventDefault();
                        OWM.authorizationLimitedFloatbox({$msg});
                    });',
                        array(
                            'btn' => $uploadItemId,
                            'msg' => $status['msg']
                        )
                    ));
                }
            }

            $contextMenu = [];

            if ( $isUploadPhotoAllowed )
            {
                $contextMenu[] = array(
                    'group' => 'photo',
                    'label' => OW::getLanguage()->text('photo', 'upload_photos'),
                    'order' => 1,
                    'class' => null,
                    'href' => OW::getRouter()->urlForRoute('photo_upload'),
                    'id' => $uploadItemId
                );
            }

            if ( $ownerMode )
            {
                $contextMenu[] = array(
                    'group' => 'photo',
                    'label' => OW::getLanguage()->text('photo', 'edit_album'),
                    'order' => 2,
                    'class' => null,
                    'href' => OW::getRouter()->urlForRoute('photo_upload')
                );

                $deleteAlbumId = uniqid('delete_album');

                $contextMenu[] = array(
                    'group' => 'photo',
                    'label' => OW::getLanguage()->text('photo', 'delete_album'),
                    'order' => 3,
                    'class' => null,
                    'href' => OW::getRouter()->urlForRoute('photo_upload'),
                    'click' => "return confirm('" . OW::getLanguage()->text('base', 'are_you_sure') . "');",
                    'id' => $deleteAlbumId
                );

                OW::getDocument()->addScriptDeclaration(UTIL_JsGenerator::composeJsString(
                    ';$("#" + {$btn}).on("click", function(e)
                    {
                        e.preventDefault();

                        if ( confirm("' . OW::getLanguage()->text('base', 'are_you_sure') . '") ) {
                            OWM.postRequest("' .  OW::getRouter()->urlForRoute('photo_user_delete_album', array('id' => $albumId)) . '", {});
                        }
                    });',
                    array(
                        'btn' => $deleteAlbumId
                    )
                ));
            }

            $this->addComponent('contextMenu', new BASE_MCMP_ContextAction($contextMenu));
        }

        //--
    }

    public function view( array $params )
    {
        if ( !isset($params['id']) || !($photoId = (int) $params['id']) )
        {
            throw new Redirect404Exception();
        }

        $lang = OW::getLanguage();

        $photo = $this->photoService->findPhotoById($photoId);
        if ( !$photo )
        {
            throw new Redirect404Exception();
        }

        $album = $this->photoAlbumService->findAlbumById($photo->albumId);
        $this->assign('album', $album);

        $ownerName = BOL_UserService::getInstance()->getUserName($album->userId);
        $albumUrl = OW::getRouter()->urlForRoute('photo_user_album', array('album' => $album->id, 'user' => $ownerName));
        $this->assign('albumUrl', $albumUrl);

        // is owner
        $contentOwner = $this->photoService->findPhotoOwner($photo->id);
        $userId = OW::getUser()->getId();
        $ownerMode = $contentOwner == $userId;
        $this->assign('ownerMode', $ownerMode);

        // is moderator
        $modPermissions = OW::getUser()->isAuthorized('photo');
        $this->assign('moderatorMode', $modPermissions);

        $this->assign('url', $this->photoService->getPhotoUrl($photo->id, false, $photo->hash));

        if ( !$ownerMode && !$modPermissions && !OW::getUser()->isAuthorized('photo', 'view') )
        {
            $status = BOL_AuthorizationService::getInstance()->getActionStatus('photo', 'view');
            $this->assign('authError', $status['msg']);

            return;
        }

        // permissions check
        if ( !$ownerMode && !$modPermissions )
        {
            $privacyParams = array('action' => 'photo_view_album', 'ownerId' => $contentOwner, 'viewerId' => $userId);
            $event = new OW_Event('privacy_check_permission', $privacyParams);
            OW::getEventManager()->trigger($event);
        }

        $photo->description = UTIL_HtmlTag::autoLink($photo->description);
        $this->assign('photo', $photo);

        $fullsizeUrl = (int) OW::getConfig()->getValue('photo', 'store_fullsize') && $photo->hasFullsize
            ? $this->photoService->getPhotoFullsizeUrl($photo->id, $photo->hash)
            : null;
        $this->assign('fullsizeUrl', $fullsizeUrl);

        $this->assign('nextPhoto', $this->photoService->getNextPhotoId($photo->albumId, $photo->id));
        $this->assign('previousPhoto', $this->photoService->getPreviousPhotoId($photo->albumId, $photo->id));

        $photoCount = $this->photoAlbumService->countAlbumPhotos($photo->albumId);
        $this->assign('photoCount', $photoCount);

        $photoIndex = $this->photoService->getPhotoIndex($photo->albumId, $photo->id);
        $this->assign('photoIndex', $photoIndex);

        $avatar = BOL_AvatarService::getInstance()->getDataForUserAvatars(array($contentOwner), true, true, true, false);
        $this->assign('avatar', $avatar[$contentOwner]);
        $this->assign('isOwner', $ownerMode);

        $cmtParams = new BASE_CommentsParams('photo', 'photo_comments');
        $cmtParams->setEntityId($photo->id);
        $cmtParams->setOwnerId($contentOwner);
        $cmtParams->setDisplayType(BASE_CommentsParams::DISPLAY_TYPE_BOTTOM_FORM_WITH_FULL_LIST);

        $photoCmts = new BASE_MCMP_Comments($cmtParams);
        $this->addComponent('comments', $photoCmts);

        OW::getDocument()->setHeading($album->name);

        $description = strip_tags($photo->description);
        $description = mb_strlen($description) ? $description : $photo->id;

        OW::getDocument()->setTitle($lang->text('photo', 'meta_title_photo_view', array('title' => $description)));
    }

    private function getMenu()
    {
        $menuItems = array();
        $lang = OW::getLanguage();

        $item = new BASE_MenuItem();
        $item->setLabel($lang->text('photo', 'menu_latest'));
        $item->setUrl(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'latest')));
        $item->setKey('latest');
        $item->setOrder(1);
        array_push($menuItems, $item);
        
        if ( PHOTO_BOL_PhotoService::getInstance()->countPhotos('featured') )
        {
            $item = new BASE_MenuItem();
            $item->setLabel($lang->text('photo', 'menu_featured'));
            $item->setUrl(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'featured')));
            $item->setKey('featured');
            $item->setOrder(2);
            array_push($menuItems, $item);
        }
        
        $item = new BASE_MenuItem();
        $item->setLabel($lang->text('photo', 'menu_toprated'));
        $item->setUrl(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'toprated')));
        $item->setKey('toprated');
        $item->setOrder(3);
        array_push($menuItems, $item);

        return new BASE_MCMP_ContentMenu($menuItems);
    }
}

