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
 * @authors Egor Bulgakov <egor.bulgakov@gmail.com>, Kairat Bakitow <kainisoft@gmail.com>
 * @package ow_plugins.photo.controllers
 * @since 1.0
 */
class PHOTO_CTRL_Admin extends ADMIN_CTRL_Abstract
{
    public function __construct()
    {
        parent::__construct();
        
        $this->setPageHeading(OW::getLanguage()->text('photo', 'admin_config'));
        
        $general = new BASE_MenuItem();
        $general->setLabel(OW::getLanguage()->text('photo', 'admin_menu_general'));
        $general->setUrl(OW::getRouter()->urlForRoute('photo_admin_config'));
        $general->setKey('general');
        $general->setIconClass('ow_ic_gear_wheel');
        $general->setOrder(0);
        
        $view = new BASE_MenuItem();
        $view->setLabel(OW::getLanguage()->text('photo', 'admin_menu_view'));
        $view->setUrl(OW::getRouter()->urlForRoute('photo_admin_view'));
        $view->setKey('view');
        $view->setIconClass('ow_ic_picture');
        $view->setOrder(1);
        
        $menu = new BASE_CMP_ContentMenu(array($general, $view));
        $this->addComponent('menu', $menu);
    }

    public function index()
    {
        $this->assign('iniValue', PHOTO_BOL_PhotoService::getInstance()->getMaxUploadFileSize(false));

        $configs = OW::getConfig()->getValues('photo');
        $configSaveForm = new ConfigSaveForm();

        $extendedSettings = array();
        $settingNameList = array();
        $event = new BASE_CLASS_EventCollector('photo.collect_extended_settings');
        OW::getEventManager()->trigger($event);

        if ( ($settings = $event->getData()) && is_array($settings) &&
            count(($settings = array_filter($settings, array($this, 'filterValidSection')))) )
        {
            foreach ( $settings as $setting )
            {
                if ( !array_key_exists($setting['section'], $extendedSettings) )
                {
                    $extendedSettings[$setting['section']] = array(
                        'section_lang' => $setting['section_lang'],
                        'icon' => isset($setting['icon']) ? $setting['icon'] : 'ow_ic_gear_wheel',
                        'settings' => array()
                    );
                }

                foreach ( $setting['settings'] as $name => $input )
                {
                    $settingNameList[$name] = $setting['section'];
                    $extendedSettings[$setting['section']]['settings'][$name] = $input;
                    $configSaveForm->addElement($input);
                }
            }
        }

        if ( OW::getRequest()->isPost() && $configSaveForm->isValid($_POST) )
        {
            $values = $configSaveForm->getValues();
            $config = OW::getConfig();

            $config->saveConfig('photo', 'accepted_filesize', $values['acceptedFilesize']);
            $config->saveConfig('photo', 'album_quota', $values['albumQuota']);
            $config->saveConfig('photo', 'user_quota', $values['userQuota']);
            $config->saveConfig('photo', 'download_accept', (bool)$values['downloadAccept']);
            $config->saveConfig('photo', 'store_fullsize', (bool)$values['storeFullsize']);

            $configs['accepted_filesize'] = $values['acceptedFilesize'];
            $configs['album_quota'] = $values['albumQuota'];
            $configs['user_quota'] = $values['userQuota'];
            $configs['download_accept'] = $values['downloadAccept'];
            $configs['store_fullsize'] = $values['storeFullsize'];

            $eventParams = array_intersect_key($values, $settingNameList);
            $event = new OW_Event('photo.save_extended_settings', $eventParams, $eventParams);
            OW::getEventManager()->trigger($event);
            $eventData = $event->getData();

            foreach ( $settingNameList as $name => $section )
            {
                $extendedSettings[$section]['settings'][$name]->setValue($eventData[$name]);
            }
            
            OW::getFeedback()->info(OW::getLanguage()->text('photo', 'settings_updated'));
        }
        
        $configSaveForm->getElement('acceptedFilesize')->setValue($configs['accepted_filesize']);
        $configSaveForm->getElement('albumQuota')->setValue($configs['album_quota']);
        $configSaveForm->getElement('userQuota')->setValue($configs['user_quota']);
        $configSaveForm->getElement('downloadAccept')->setValue($configs['download_accept']);

        $this->assign('extendedSettings', $extendedSettings);
        
        $this->addForm($configSaveForm);
    }

    private function filterValidSection( $section )
    {
        return count(array_diff(array('section', 'section_lang', 'settings'), array_keys($section))) === 0 &&
            is_array($section['settings']) &&
            count(array_filter($section['settings'], array($this, 'filterValidSettings'))) !== 0;
    }

    private function filterValidSettings( $setting )
    {
        return $setting instanceof FormElement;
    }
    
    public function view( array $params = array() )
    {
        OW::getDocument()->addStyleSheet(OW::getPluginManager()->getPlugin('photo')->getStaticCssUrl() . 'admin.css');
        OW::getDocument()->addOnloadScript(';window.photoAdmin.init();');
        $form = new Form('view-mode');
        $form->setAjax(TRUE);
        $form->bindJsFunction('success', 'function(data)
        {
            if ( data && data.result )
            {
                OW.info(data.msg);
            }
        }');
        
        if ( OW::getRequest()->isPost() )
        {
            OW::getConfig()->saveConfig('photo', 'photo_list_view_classic', (bool)$_POST['photo-list-mode']);
            OW::getConfig()->saveConfig('photo', 'album_list_view_classic', (bool)$_POST['album-list-mode']);
            OW::getConfig()->saveConfig('photo', 'photo_view_classic', (bool)$_POST['photo-view-mode']);
            
            exit(json_encode(array('result' => true, 'msg' => OW::getLanguage()->text('photo', 'settings_updated'))));
        }
        
        $photoListMode = new HiddenField('photo-list-mode');
        $photoListMode->setValue(OW::getConfig()->getValue('photo', 'photo_list_view_classic'));
        $form->addElement($photoListMode);
        
        $albumListMode = new HiddenField('album-list-mode');
        $albumListMode->setValue(OW::getConfig()->getValue('photo', 'album_list_view_classic'));
        $form->addElement($albumListMode);
        
        $photoViewMode = new HiddenField('photo-view-mode');
        $photoViewMode->setValue(OW::getConfig()->getValue('photo', 'photo_view_classic'));
        $form->addElement($photoViewMode);
        
        $submit = new Submit('save');
        $submit->addAttribute('class', 'ow_ic_save ow_positive');
        $submit->setValue(OW::getLanguage()->text('photo', 'btn_edit'));
        $form->addElement($submit);
        
        $this->addForm($form);
    }

    public function uninstall()
    {
        if ( isset($_POST['action']) && $_POST['action'] == 'delete_content' )
        {
            OW::getConfig()->saveConfig('photo', 'uninstall_inprogress', 1);
            
            PHOTO_BOL_PhotoService::getInstance()->setMaintenanceMode(true);
            
            OW::getFeedback()->info(OW::getLanguage()->text('photo', 'plugin_set_for_uninstall'));
            $this->redirect();
        }
              
        $this->setPageHeading(OW::getLanguage()->text('photo', 'page_title_uninstall'));
        $this->setPageHeadingIconClass('ow_ic_delete');
        
        $this->assign('inprogress', (bool) OW::getConfig()->getValue('photo', 'uninstall_inprogress'));
        
        $js = new UTIL_JsGenerator();
        $js->jQueryEvent('#btn-delete-content', 'click', 'if ( !confirm("'.OW::getLanguage()->text('photo', 'confirm_delete_photos').'") ) return false;');
        
        OW::getDocument()->addOnloadScript($js);
    }
}

class ConfigSaveForm extends Form
{
    public function __construct()
    {
        parent::__construct('configSaveForm');

        $language = OW::getLanguage();

        $acceptedFilesizeField = new TextField('acceptedFilesize');
        $acceptedFilesizeField->setRequired(true);
        $maxSize = PHOTO_BOL_PhotoService::getInstance()->getMaxUploadFileSize(false);
        $last = strtolower($maxSize[strlen($maxSize) - 1]);
        $realSize = (int)$maxSize;

        switch ( $last )
        {
            case 'g': $realSize *= 1024;
        }

        $sValidator = new FloatValidator(0.5, $realSize);
        $sValidator->setErrorMessage($language->text('photo', 'file_size_validation_error'));
        $acceptedFilesizeField->addValidator($sValidator);
        $this->addElement($acceptedFilesizeField->setLabel($language->text('photo', 'accepted_filesize')));

        $albumQuotaField = new TextField('albumQuota');
        $albumQuotaField->setRequired(true);
        $aqValidator = new IntValidator(0, 1000);
        $albumQuotaField->addValidator($aqValidator);
        $this->addElement($albumQuotaField->setLabel($language->text('photo', 'album_quota')));

        $userQuotaField = new TextField('userQuota');
        $userQuotaField->setRequired(true);
        $uqValidator = new IntValidator(0, 10000);
        $userQuotaField->addValidator($uqValidator);
        $this->addElement($userQuotaField->setLabel($language->text('photo', 'user_quota')));
        
        $downloadAccept = new CheckboxField('downloadAccept');
        $downloadAccept->setLabel($language->text('photo', 'download_accept_label'));
        $downloadAccept->setValue(OW::getConfig()->getValue('photo', 'download_accept'));
        $this->addElement($downloadAccept);
        
        $storeFullsizeField = new CheckboxField('storeFullsize');
        $storeFullsizeField->setLabel($language->text('photo', 'store_full_size'));
        $storeFullsizeField->setValue((bool)OW::getConfig()->getValue('photo', 'store_fullsize'));
        $this->addElement($storeFullsizeField);

        $submit = new Submit('save');
        $submit->setValue($language->text('photo', 'btn_edit'));
        $this->addElement($submit);
    }
}
