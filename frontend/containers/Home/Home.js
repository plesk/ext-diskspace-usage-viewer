// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

import {
    createElement,
    Fragment,
} from '@plesk/ui-library';

import HomeView from '../../components/HomeView';

const Home = ({ ...props }) => (
    <Fragment>
        <div id="diskspace-usage-viewer">
            <HomeView {...props} />
        </div>
    </Fragment>
);

export default Home;
