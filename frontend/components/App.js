import { createElement, LocaleProvider, PropTypes } from '@plesk/ui-library';
import Home from '../containers/Home/Home';

const App = ({ locales, ...props }) => (
    <LocaleProvider messages={locales}>
        <Action {...props}/>
    </LocaleProvider>
);

const Action = ({ action, ...props }) => {
    switch (action) {
        case 'home':
            return <Home {...props} />;
    }
};

export default App;
