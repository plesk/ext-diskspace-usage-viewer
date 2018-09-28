<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

class Modules_DiskspaceUsageViewer_ConfigDefaults extends pm_Hook_ConfigDefaults
{
    public function getDefaults()
    {
        return [
            'deleteEnabled' => false,
        ];
    }
}
