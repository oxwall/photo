<?php

/**
 * Credits Photo Service Class.
 *
 * @author Sergey Filipovich <psiloscop@gmail.com>
 * @package ow.plugin.photo.bol
 * @since 1.8.4
 */
class PHOTO_BOL_CreditService
{
    use OW_Singleton;

    public function addCredits()
    {
        if( !OW::getPluginManager()->isPluginActive('moderation') )
        {
            BOL_AuthorizationService::getInstance()->trackAction('photo', 'upload', NULL, array('checkInterval' => FALSE));
        }
    }
}