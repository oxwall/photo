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
 * @author Podyachev Evgeny <joker.OW2@gmail.com>
 * @package ow.plugin.photo.mobile.components
 * @since 1.7.5
 */
class PHOTO_MCMP_PhotoListWidget extends BASE_CLASS_Widget
{
    /**
     * @param BASE_CLASS_WidgetParameter $paramObj
     */
    public function __construct( BASE_CLASS_WidgetParameter $paramObj )
    {
        parent::__construct();

        $photoService = PHOTO_BOL_PhotoService::getInstance();
        $num = !empty($paramObj->customParamList['photoCount']) ? $paramObj->customParamList['photoCount'] : 8;

        $cmpParams = array(
            'photoCount' => $num,
            'checkAuth' => false,
            'wrapBox' => false,
            'uniqId' => uniqid(),
            'showTitle' => false,
            'showToolbar' => true
        );
        
        $uniqId = $cmpParams['uniqId'];

        $cmp = new PHOTO_MCMP_IndexPhotoList($cmpParams);
        $this->addComponent('cmp', $cmp);
        
        $items = array('latest', 'toprated');
        
        if ( $photoService->countPhotos('featured') )
        {
            $items[] = 'featured';
        }
        
        $menuItems = $cmp->getMenuItems($items, $uniqId);
        $menuCmp = new BASE_MCMP_WidgetMenu($menuItems);
        $paramObj->standartParamList->capContent = $menuCmp->render();
        
        $this->setSettingValue(self::SETTING_TOOLBAR, PHOTO_MCMP_IndexPhotoList::getToolbar($uniqId));
        $script = '';
        
        foreach($menuItems as $key => $item)
        {
            if ( !empty($item["visibility"]) )
            {
                $script .= '
                    $("#photo-cmp-menu-'.$key.'-'.$uniqId.'").click(function(){
                        var list = $("#toolbar-photo-'.$key.'-'.$uniqId.'").parents("div:eq(0)").find("a");
                        list.hide();
                        list.css("visibility","hidden");
                        $("#toolbar-photo-'.$key.'-'.$uniqId.'").css("visibility","visible").show();
                    }); ';
            }
            else
            {
                $script .= '
                    $("#photo-cmp-menu-'.$key.'-'.$uniqId.'").click(function(){
                        var list = $("#toolbar-photo-'.$key.'-'.$uniqId.'").parents("div:eq(0)").find("a");
                        list.hide();
                        list.css("visibility","hidden");
                        $("#toolbar-photo-'.$key.'-'.$uniqId.'").show();
                    }); ';
            }
        }
        
        OW::getDocument()->addOnloadScript($script);

        $this->setTemplate(OW::getPluginManager()->getPlugin('photo')->getMobileCmpViewDir() . 'photo_list_widget.html');
    }

    public static function getSettingList()
    {
        $settingList = array();

        $settingList['photoCount'] = array(
            'presentation' => self::PRESENTATION_NUMBER,
            'label' => OW::getLanguage()->text('photo', 'cmp_widget_photo_count'),
            'value' => 8
        );

        return $settingList;
    }

    public static function validateSettingList( $settingList )
    {
        parent::validateSettingList($settingList);

        $validationMessage = OW::getLanguage()->text('photo', 'cmp_widget_photo_count_msg');

        if ( !preg_match('/^\d+$/', $settingList['photoCount']) )
        {
            throw new WidgetSettingValidateException($validationMessage, 'photoCount');
        }
        if ( $settingList['photoCount'] > 100 )
        {
            throw new WidgetSettingValidateException($validationMessage, 'photoCount');
        }
    }

    public static function getAccess()
    {
        return self::ACCESS_ALL;
    }

    public static function getStandardSettingValueList()
    {
        return array(
            self::SETTING_TITLE => OW::getLanguage()->text('photo', 'photo_list_widget'),
            self::SETTING_ICON => self::ICON_PICTURE,
            self::SETTING_SHOW_TITLE => true
        );
    }
}
