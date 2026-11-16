<?php
// Copyright 1999-2026. WebPros International GmbH. All rights reserved.

// Plesk defines this class at runtime; the SDK stubs do not have it.
if (!class_exists('PleskUtilException')) {
    class PleskUtilException extends Exception
    {
    }
}
