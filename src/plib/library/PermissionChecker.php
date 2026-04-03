<?php
// Copyright 1999-2024. WebPros International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

class PermissionChecker
{
    /**
     * @param \pm_Client $client
     * @param \pm_Domain $domain
     * @return bool
     */
    public function canManageFiles(\pm_Client $client, ?\pm_Domain $domain): bool
    {
        return $client->hasCorePermission('filesManagement', $domain);
    }
}