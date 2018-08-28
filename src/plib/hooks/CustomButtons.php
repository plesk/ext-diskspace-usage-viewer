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
                'place' => self::PLACE_HOSTING_PANEL_NAVIGATION,
                'title' => pm_Locale::lmsg('menuTitle'),
                'description' => pm_Locale::lmsg('menuDescription'),
                'link' => pm_Context::getBaseUrl(),
                'icon' => pm_Context::getBaseUrl() . 'img/16x16.png',
                'visibility' => [$this, 'isHostingButtonVisible'],
            ],
            [
                'place' => self::PLACE_DOMAIN_PROPERTIES,
                'title' => pm_Locale::lmsg('menuTitle'),
                'description' => pm_Locale::lmsg('menuDescription'),
                'link' => pm_Context::getBaseUrl(),
                'icon' => pm_Context::getBaseUrl() . 'img/32x32.png',
                'contextParams' => true,
                'visibility' => [$this, 'isDomainButtonVisible'],
            ],
        ];
    }

    public function isHostingButtonVisible(array $params)
    {
        return pm_Session::getClient()->isAdmin();
    }

    public function isDomainButtonVisible(array $params)
    {
        if (isset($params['alias_id'])) {
            return false;
        }

        $siteId = isset($params['site_id']) ? $params['site_id'] : 0;

        return pm_Domain::getByDomainId($siteId)->hasHosting();
    }
}
