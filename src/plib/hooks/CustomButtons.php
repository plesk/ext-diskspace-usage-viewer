<?php
// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

class Modules_DiskspaceUsageViewer_CustomButtons extends pm_Hook_CustomButtons
{
    public function getButtons()
    {
        return [
            [
                'place' => self::PLACE_ADMIN_TOOLS_AND_SETTINGS,
                'section' => self::SECTION_ADMIN_TOOLS_TOOLS_AND_RESOURCES,
                'title' => \pm_Locale::lmsg('buttonHook.title'),
                'description' => \pm_Locale::lmsg('buttonHook.description'),
                'link' => \pm_Context::getBaseUrl(),
                'icon' => \pm_Context::getBaseUrl() . 'img/32x32.png',
            ],
            [
                'place' => self::PLACE_DOMAIN_PROPERTIES,
                'title' => \pm_Locale::lmsg('buttonHook.title'),
                'description' => \pm_Locale::lmsg('buttonHook.description'),
                'link' => \pm_Context::getBaseUrl(),
                'icon' => \pm_Context::getBaseUrl() . 'img/32x32.png',
                'contextParams' => true,
                'visibility' => [$this, 'isDomainPropertiesButtonVisible'],
            ],
        ];
    }

    public function isDomainPropertiesButtonVisible(array $params)
    {
        if (isset($params['alias_id'])) {
            return false;
        }

        if (!isset($params['site_id'])) {
            return false;
        }

        return \pm_Domain::getByDomainId($params['site_id'])->hasHosting();
    }
}
