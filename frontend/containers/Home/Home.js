// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

import {
    createElement,
    Fragment,
} from '@plesk/ui-library';

import ListHome from '../../components/ListHome';

const Home = ({ ...props }) => (
    <Fragment>
        <div id="diskspace-usage-viewer">
            <ListHome {...props} />
        </div>
    </Fragment>
);

export default Home;
