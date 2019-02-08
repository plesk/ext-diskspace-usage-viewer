// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

import {
    Component,
    createElement,
    Tab,
    Tabs,
    Toaster,
    Translate,
} from '@plesk/ui-library';

import FileList from './FileList';
import UsageList from './UsageList';

class HomeView extends Component {
    constructor(props) {
        super(props);

        let dir = '/';

        if (window.location.hash) {
            dir = window.location.hash.substr(1);
        }

        this.state = {
            dir,
        };
    }

    renderFilesTab = () => {
        if (!this.props.isAdmin) {
            return (null);
        }

        return (
            <Tab key={2} title={<Translate content="tab.files.title" />}>
                <FileList
                    dir={this.state.dir}
                    onError={this.handleError}
                />
            </Tab>
        );
    };

    handleError = message => {
        const intent = 'danger';
        const id = this.toaster.add({ intent, message });

        setTimeout(() => {
            this.toaster.remove(id);
        }, 10000);
    };

    handleSuccess = message => {
        const intent = 'success';
        const id = this.toaster.add({ intent, message });

        setTimeout(() => {
            this.toaster.remove(id);
        }, 10000);
    };

    render() {
        return (
            <div>
                <Toaster ref={ref => (this.toaster = ref)} />
                <Tabs active={this.props.openFiles ? 2 : 1}>
                    <Tab key={1} title={<Translate content="tab.usage.title" />}>
                        <UsageList
                            dir={this.state.dir}
                            defaultDaysToKeepBackups={this.props.defaultDaysToKeepBackups}
                            isAdmin={this.props.isAdmin}
                            transOthers={this.props.transOthers}
                            onSuccess={this.handleSuccess}
                            onError={this.handleError}
                        />
                    </Tab>
                    {this.renderFilesTab()}
                </Tabs>
            </div>
        );
    }
}

export default HomeView;
