// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

import {
    createElement,
    LocaleProvider,
    PropTypes,
} from '@plesk/ui-library';

import Home from '../containers/Home/Home';

const App = ({ locales, ...props }) => (
    <LocaleProvider messages={locales}>
        <Action {...props} />
    </LocaleProvider>
);

const Action = ({ action, ...props }) => {
    switch (action) {
        case 'home':
        default:
            return <Home {...props} />;
    }
};

App.propTypes = {
    locales: PropTypes.array.isRequired,
};

export default App;
