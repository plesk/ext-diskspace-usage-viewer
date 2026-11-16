// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

import { createElement } from '@plesk/ui-library';

import HomeView from '../../components/HomeView';

const Home = ({ ...props }) => (
    <div id="diskspace-usage-viewer">
        <HomeView {...props} />
    </div>
);

export default Home;
