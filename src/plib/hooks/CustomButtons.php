<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

class Modules_DiskspaceUsageViewer_CustomButtons extends pm_Hook_CustomButtons
{
    public function getButtons()
    {
        return [
            [
                'place' => self::PLACE_ADMIN_NAVIGATION,
                'title' => pm_Locale::lmsg('menuTitle'),
                'description' => pm_Locale::lmsg('menuDescription'),
                'link' => pm_Context::getBaseUrl(),
                'icon' => pm_Context::getBaseUrl() . 'img/16x16.png',
            ],
            [
                'place' => self::PLACE_DOMAIN_PROPERTIES,
                'title' => pm_Locale::lmsg('menuTitle'),
                'description' => pm_Locale::lmsg('menuDescription'),
                'link' => pm_Context::getBaseUrl(),
                'icon' => pm_Context::getBaseUrl() . 'img/32x32.png',
            ],
        ];
    }
}
