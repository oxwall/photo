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
    const ALBUM_SHORT_DESC_LENGTH = 100;

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
     * Edit photo
     *
     * @param $params
     * @throws Exception
     * @return void
     */
    public function editPhoto($params)
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthorizationException();
        }

        $photoId = (int) $params['id'];
        $ownerId = $this->photoService->findPhotoOwner($photoId);

        if ( $ownerId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('photo', 'upload') )
        {
            throw new AuthorizationException();
        }

        $photo = $this->photoService->findPhotoById($photoId);
        $photoAlbum = PHOTO_BOL_PhotoAlbumService::getInstance()->findAlbumById($photo->albumId);

        $lang = OW::getLanguage();
        $form = new PHOTO_MCLASS_PhotoEditForm();

        $form->getElement('album')->setValue($photoAlbum->name);
        $form->getElement('description')->setValue($photo->description);

        $this->addForm($form);

        // validate form
        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $updatedPhoto = $form->process($photo->id);

            if ( $updatedPhoto->status != PHOTO_BOL_PhotoDao::STATUS_APPROVED )
            {
                OW::getFeedback()->info(OW::getLanguage()->text('photo', 'photo_uploaded_pending_approval'));
                $user = BOL_UserService::getInstance()->findUserById($ownerId);

                $this->redirect(OW::getRouter()->urlForRoute('photo_user_albums', array(
                    'user' => $user->getUsername()
                )));
            }

            OW::getFeedback()->info($lang->text('photo', 'photo_updated'));

            $this->redirect(OW::getRouter()->urlForRoute('view_photo', [
                'id' => $photo->id
            ]));
        }

        $description = strip_tags($photo->description);
        $description = mb_strlen($description) ? $description : $photo->id;

        OW::getDocument()->setTitle($lang->text('photo', 'mobile_meta_title_photo_edit', array('title' => $description)));
        OW::getDocument()->setHeading($lang->text('photo', 'tb_edit_photo'));

        $albums = PHOTO_BOL_PhotoAlbumService::getInstance()->findUserAlbumList($ownerId, 1, 100);

        if ( $albums )
        {
            $script =
                '$("#album_select").change(function(event){
                    $("#album_input").val($(this).val());
                });';
            OW::getDocument()->addOnloadScript($script);
        }

        $this->assign('albums', $albums);
        $this->assign('photo', $photo);
        $this->assign('selectedAlbum', $photoAlbum);
    }

    /**
     * Edit album
     *
     * @param $params
     * @throws Exception
     * @reutrn void
     */
    public function editAlbum($params)
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthorizationException();
        }

        $lang = OW::getLanguage();
        $albumId = $params['id'];
        $album = $this->photoAlbumService->findAlbumById($albumId);

        if ( !$album || ($album->userId != OW::getUser()->getId() && !OW::getUser()->isAuthorized('photo')) )
        {
            throw new Redirect404Exception();
        }

        $albumUser = BOL_UserService::getInstance()->findUserById($album->userId);
        $form = new PHOTO_MCLASS_AlbumEditForm();
        $this->addForm($form);

        $form->getElement('album-name')->setValue($album->name);
        $form->getElement('desc')->setValue($album->description);

        // validate form
        if ( OW::getRequest()->isPost() && $form->isValid($_POST) )
        {
            $form->process($albumId);

            OW::getFeedback()->info($lang->text('photo', 'photo_album_updated'));

            $this->redirect(OW::getRouter()->urlForRoute('photo_user_album', array(
                'user' => $albumUser->getUsername(),
                'album' => $albumId
            )));
        }

        $this->assign('album', $album);
        $this->assign('albumUserName', $albumUser->getUsername());

        $displayName = BOL_UserService::getInstance()->getDisplayName($album->userId);
        OW::getDocument()->setHeading($lang->text('photo', 'edit_album'));

        OW::getDocument()->setTitle(
            $lang->text('photo', 'meta_title_photo_useralbum_edit', array('displayName' => $displayName, 'albumName' => $album->name))
        );

        OW::getDocument()->setDescription(
            $lang->text('photo', 'meta_description_photo_useralbum_edit', array('displayName' => $displayName, 'albumName' => $album->name))
        );
    }

    /**
     * Set as avatar
     *
     * @param $params
     * @throws AuthorizationException
     */
    public function setAsAvatar($params)
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthorizationException();
        }

        $userId = OW::getUser()->getId();
        $lang = OW::getLanguage();
        $photoId = (int) $params['id'];
        $photo = $this->photoService->findPhotoById($photoId);

        if ( $photo && OW::getRequest()->isPost() )
        {
            $album = $this->photoAlbumService->findAlbumById($photo->albumId);

            if ( $album && $album->userId == $userId )
            {
                $avatarService = BOL_AvatarService::getInstance();
                $oldAvatar = $avatarService->findByUserId($userId);

                // apply new avatar
                OW::getEventManager()->trigger(new OW_Event('base.before_avatar_change', [
                    'userId' => $userId,
                    'avatarId' => $oldAvatar ? $oldAvatar->id : null,
                    'upload' => false,
                    'crop' => true
                ]));

                $avatarService->deleteUserAvatar($userId);
                $avatarService->clearCahche($userId);
                $avatarService->setUserAvatar($userId, $this->
                        photoService->getPhotoPath($photoId, $photo->hash, PHOTO_BOL_PhotoService::TYPE_ORIGINAL));

                // get new avatar dto
                $newAvatar = $avatarService->findByUserId($userId, false);

                OW::getEventManager()->trigger(new OW_Event('base.after_avatar_change', array(
                    'userId' => $userId,
                    'avatarId' => $newAvatar ? $newAvatar->id : null,
                    'upload' => false,
                    'crop' => true
                )));

                OW::getFeedback()->info($lang->text('photo', 'mobile_photo_avatar_set'));
                $this->redirect(OW::getRouter()->urlForRoute('view_photo', [
                    'id' => $photo->id
                ]));
            }
        }

        OW::getFeedback()->error($lang->text('photo', 'no_photo_found'));
        $this->redirect(OW::getRouter()->urlForRoute('base_index'));
    }

    /**
     * Change album cover
     *
     * @param array $params
     * @throws Exception
     * @return void
     */
    public function changeAlbumCover($params)
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthorizationException();
        }

        $photoId = (int) $params['id'];
        $photo = $this->photoService->findPhotoById($photoId);

        if ( $photo && OW::getRequest()->isPost() )
        {
            $album = $this->photoAlbumService->findAlbumById($photo->albumId);

            if ( $album && $album->userId == OW::getUser()->getId() )
            {
                PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->deleteCoverByAlbumId($album->id);

                if ( $this->photoService->createAlbumCover($album->id, array($photo)) )
                {
                    OW::getFeedback()->info(OW::getLanguage()->text('photo', 'mobile_album_cover_changed'));
                }
                else {
                    OW::getFeedback()->error(OW::getLanguage()->text('photo', 'mobile_album_cover_not_changed'));
                }

                $this->redirect(OW::getRouter()->urlForRoute('view_photo', array(
                    'id' => $photo->id
                )));
            }
        }

        $this->redirect(OW::getRouter()->urlForRoute('base_index'));
    }

    /**
     * Delete photo
     *
     * @param array $params
     * @throws Exception
     * @return void
     */
    public function deletePhoto($params)
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthorizationException();
        }

        $lang = OW::getLanguage();
        $photoId = $params['id'];

        $photo = $this->photoService->findPhotoById($photoId);

        if ( $photo && OW::getRequest()->isPost() )
        {
            $ownerId = $this->photoService->findPhotoOwner($photo->id);
            $isOwner = $ownerId == OW::getUser()->getId();
            $isModerator = OW::getUser()->isAuthorized('photo');

            if ( !$isOwner && !$isModerator )
            {
                OW::getFeedback()->error($lang->text('photo', 'photo_not_deleted'));

                $this->redirect(OW::getRouter()->urlForRoute('view_photo', [
                    'id' => $photo->id
                ]));
            }

            // delete photo
            if ( !$this->photoService->deletePhoto($photo->id) )
            {
                OW::getFeedback()->error($lang->text('photo', 'photo_not_deleted'));

                $this->redirect(OW::getRouter()->urlForRoute('view_photo', [
                    'id' => $photo->id
                ]));
            }

            // delete photo from album's cover
            $cover = PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->findByAlbumId($photo->albumId);

            if ( $cover === NULL || (int)$cover->auto )
            {
                PHOTO_BOL_PhotoAlbumCoverDao::getInstance()->deleteCoverByAlbumId($photo->albumId);

                $this->photoService->createAlbumCover($photo->albumId, array_reverse(PHOTO_BOL_PhotoDao::getInstance()->getAlbumAllPhotos($photo->albumId)));
            }

            OW::getFeedback()->info($lang->text('photo', 'photo_deleted'));
            $this->redirect(OW_Router::getInstance()->urlForRoute(
                'photo_user_albums',
                array('user' => BOL_UserService::getInstance()->getUserName($ownerId))
            ));
        }

        OW::getFeedback()->error($lang->text('photo', 'no_photo_found'));
        $this->redirect(OW::getRouter()->urlForRoute('base_index'));
    }

    /**
     * Delete album
     *
     * @param array $params
     * @throws Exception
     * @return void
     */
    public function deleteAlbum($params)
    {
        if ( !OW::getUser()->isAuthenticated() )
        {
            throw new AuthorizationException();
        }

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

        $menu = $this->getMenu();

        if ( !in_array($type, $validLists) || !$menu->getElement($type) )
        {
            $this->redirect(OW::getRouter()->urlForRoute('view_photo_list', array('listType' => 'latest')));
        }

        $el = $menu->getElement($type);
        
        $el->setActive(true);
        $this->addComponent('menu', $menu);

        $initialCmp = OW::getClassInstanceArray('PHOTO_MCMP_PhotoList', array($type, $limit, array()));
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
                    'href' => OW::getRouter()->urlForRoute('photo_upload') . '?albumOwnerId=' . $user->id,
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

        $initialCmp = OW::getClassInstanceArray('PHOTO_MCMP_PhotoList', array($type, $limit, array(), $albumId));
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

        $shortDesc = mb_strlen($album->description) > self::ALBUM_SHORT_DESC_LENGTH
            ? mb_substr($album->description, 0, self::ALBUM_SHORT_DESC_LENGTH) . '...'
            : $album->description;

        $longDesc = mb_strlen($album->description) > self::ALBUM_SHORT_DESC_LENGTH
            ? $album->description
            : '';

        $this->assign('albumShortDesc', $shortDesc);
        $this->assign('albumLongDesc', $longDesc);
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

        $isUploadPhotoAllowed = false;

        //--  add context menu --//
        if ( $ownerMode )
        {
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
        }

        $contextMenu = [];

        if ( $isUploadPhotoAllowed && $ownerMode )
        {
            $contextMenu[] = array(
                'group' => 'photo',
                'label' => OW::getLanguage()->text('photo', 'upload_photos'),
                'order' => 1,
                'class' => null,
                'href' => OW::getRouter()->urlForRoute('photo_upload') . '?albumId=' . $album->id,
                'id' => $uploadItemId
            );
        }

        if ( OW::getUser()->isAuthorized('photo') )
        {
            $contextMenu[] = array(
                'group' => 'photo',
                'label' => OW::getLanguage()->text('photo', 'edit_album'),
                'order' => 2,
                'class' => null,
                'href' => OW::getRouter()->urlForRoute('photo_user_edit_album', array(
                    'id' => $albumId
                ))
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

        OW::getDocument()->setTitle($lang->text('photo', 'meta_title_photo_view', array(
            'photo_id' => $photoIndex,
            'user_name' => $ownerName,
            'site_name' => OW::getConfig()->getValue('base', 'site_name')
        )));

        $contextMenu = [];

        if ( $ownerMode )
        {
            $changeCoverId = uniqid('change_album_cover');

            $contextMenu[] = array(
                'group' => 'photo',
                'label' => OW::getLanguage()->text('photo', 'save_as_cover'),
                'order' => 1,
                'class' => null,
                'href' => '#',
                'id' => $changeCoverId
            );

            OW::getDocument()->addScriptDeclaration(UTIL_JsGenerator::composeJsString(
                ';$("#" + {$btn}).on("click", function(e)
                {
                    e.preventDefault();

                    OWM.postRequest("' .  OW::getRouter()->urlForRoute('photo_user_change_album_cover', array('id' => $photoId)) . '", {});
                });',
                array(
                    'btn' => $changeCoverId
                )
            ));

            $saveAsAvatarId = uniqid('save_as_avatar');

            $contextMenu[] = array(
                'group' => 'photo',
                'label' => OW::getLanguage()->text('photo', 'save_as_avatar'),
                'order' => 2,
                'class' => null,
                'href' => OW::getRouter()->urlForRoute('photo_user_set_as_avatar', array('id' => $photo->id)),
                'id' => $saveAsAvatarId
            );

            OW::getDocument()->addScriptDeclaration(UTIL_JsGenerator::composeJsString(
                ';$("#" + {$btn}).on("click", function(e)
                {
                    e.preventDefault();
                    OWM.postRequest("' .  OW::getRouter()->urlForRoute('photo_user_set_as_avatar', array('id' => $photo->id)) . '", {});
                });',
                array(
                    'btn' => $saveAsAvatarId
                )
            ));
        }

        if ( $ownerMode || $modPermissions )
        {
            $contextMenu[] = array(
                'group' => 'photo',
                'label' => OW::getLanguage()->text('photo', 'tb_edit_photo'),
                'order' => 3,
                'class' => null,
                'href' => OW::getRouter()->urlForRoute('photo_user_edit_photo', array(
                    'id' => $photo->id
                ))
            );

            $deletePhotoId = uniqid('delete_photo');

            $contextMenu[] = array(
                'group' => 'photo',
                'label' => OW::getLanguage()->text('photo', 'delete_photo'),
                'order' => 4,
                'class' => null,
                'href' => OW::getRouter()->urlForRoute('photo_user_delete_photo', array('id' => $photo->id)),
                'click' => "return confirm('" . OW::getLanguage()->text('base', 'are_you_sure') . "');",
                'id' => $deletePhotoId
            );

            OW::getDocument()->addScriptDeclaration(UTIL_JsGenerator::composeJsString(
                ';$("#" + {$btn}).on("click", function(e)
                {
                    e.preventDefault();

                    if ( confirm("' . OW::getLanguage()->text('base', 'are_you_sure') . '") ) {
                        OWM.postRequest("' .  OW::getRouter()->urlForRoute('photo_user_delete_photo', array('id' => $photo->id)) . '", {});
                    }
                });',
                array(
                    'btn' => $deletePhotoId
                )
            ));
        }

        $this->addComponent('contextMenu', new BASE_MCMP_ContextAction($contextMenu));
        //--
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

