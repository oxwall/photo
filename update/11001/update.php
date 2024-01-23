<?php

$pluginKey = 'photo';
$cystomGroupName = 'customphotocomments';

try {
    OW::getAuthorization()->addGroup($cystomGroupName);

    $group = BOL_AuthorizationGroupDao::getInstance()->findByName($cystomGroupName);

    if (!empty($group)) {
        $moderators = BOL_AuthorizationModeratorDao::getInstance()->findAll();

        foreach ($moderators as $moderator) {
            $entity = new BOL_AuthorizationModeratorPermission();
            $entity->moderatorId = $moderator->id;
            $entity->groupId = $group->id;

            BOL_AuthorizationModeratorPermissionDao::getInstance()->save($entity);
        }
    }

} catch (Exception $exception) {}


$sqls = [
    "ALTER TABLE `" . OW_DB_PREFIX . "base_comment` ADD `status` ENUM('approved','approval') NOT NULL DEFAULT 'approved';",
];

foreach ($sqls as $sql) {
    try {
        OW::getDbo()->query($sql);
    } catch (Exception $exception) {

    }
}


Updater::getLanguageService()->importPrefixFromDir(__DIR__ . DS . 'langs');

