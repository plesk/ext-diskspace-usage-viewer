// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

import {
    render,
    createElement,
} from '@plesk/ui-library';

import App from './components/App';

module.exports = (container, props) => {
    render(
        <App {...props} />,
        container,
    );
};
