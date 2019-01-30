/* eslint-disable react/jsx-max-depth */

import {createElement, Fragment} from '@plesk/ui-library';
import ListHome from "../../components/ListHome";

const Home = ({...props}) => (
    <Fragment>
        <div id="diskspace-usage-viewer">
            <ListHome {...props} />
        </div>
    </Fragment>
);

export default Home;
