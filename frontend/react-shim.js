// Copyright 1999-2026. WebPros International GmbH. All rights reserved.

// Resolves "react" and "react-dom" to the React instance that the Plesk UI library exports.
// Hooks in bundled libraries fail when they run on a second copy of React.
import * as ui from '@plesk/ui-library';

export * from '@plesk/ui-library';
export default ui;
