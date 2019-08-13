// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

import {
    Button,
    Component,
    ContentLoader,
    createElement,
    List,
    Translate,
} from '@plesk/ui-library';

import {
    formatBytes,
    formatTimestamp,
    postParams,
    urlTo,
} from '../utils';

import axios from 'axios';
import DeleteDialog from './DeleteDialog';

class FileList extends Component {
    constructor(props) {
        super(props);

        this.state = {
            dir: this.props.dir,
            items: [],
            itemsLoading: true,
            selection: [],
            filesUpdating: false,
            showDeleteDialog: false,
            deletionInProgress: false,
        };

        this.cols = [{
            key: 'name',
            title: <Translate content="tab.files.col.name" />,
            sortable: true,
            width: '20%',
        }, {
            key: 'path',
            title: <Translate content="tab.files.col.path" />,
            sortable: true,
            width: '60%',
        }, {
            key: 'size',
            title: <Translate content="tab.files.col.size" />,
            sortable: true,
            width: '10%',
            style: {
                textAlign: 'right',
            },
            render: row => (
                <div style={{ textAlign: 'right' }}>
                    {formatBytes(row.size)}
                </div>
            ),
        }, {
            key: 'mtime',
            title: <Translate content="tab.files.col.mtime" />,
            sortable: true,
            width: '10%',
            render: row => (
                <div>
                    {formatTimestamp(row.mtime)}
                </div>
            ),
        }];

        this.load();
    }

    load = () => {
        this.setState({ itemsLoading: true });

        axios
            .get(urlTo('index', 'files'))
            .then(response => {
                this.setState({
                    items: response.data,
                    itemsLoading: false,
                });
            })
            .catch(() => {
                this.props.onError(<Translate content="message.requestFailed" />);
            });
    };

    updateFiles = () => {
        this.setState({ filesUpdating: true });

        axios
            .post(urlTo('index', 'update-files'), postParams({
                dir: this.state.dir,
            }))
            .then(() => {
                const progressWrapper = Jsw.getComponent('asyncProgressBarWrapper');

                if (typeof progressWrapper !== 'undefined') {
                    progressWrapper.update();
                }
            })
            .catch(() => {
                this.props.onError(<Translate content="message.requestFailed" />);
            });
    };

    render() {
        if (this.state.itemsLoading) {
            return (
                <ContentLoader />
            );
        }

        return (
            <div>
                <DeleteDialog
                    isOpen={this.state.showDeleteDialog}
                    onClose={() => this.setState({ showDeleteDialog: false })}
                    onExec={() => {
                        this.setState({
                            showDeleteDialog: false,
                            deletionInProgress: true,
                        });

                        axios
                            .post(urlTo('index', 'delete-by-id'), postParams({
                                json: JSON.stringify(this.state.selection),
                            }))
                            .then(response => {
                                this.setState({
                                    selection: [],
                                    deletionInProgress: false,
                                });

                                response.data.forEach(error => {
                                    this.props.onError(error);
                                });

                                this.load();
                            })
                            .catch(() => {
                                this.props.onError(<Translate content="message.requestFailed" />);
                            });
                    }}
                />
                <Button
                    intent="primary"
                    state={this.state.filesUpdating ? 'loading' : null}
                    onClick={() => this.updateFiles()}
                >
                    <Translate content="tab.files.button.refresh" />
                </Button>{' '}
                <Button
                    intent="primary"
                    state={this.state.deletionInProgress ? 'loading' : null}
                    disabled={this.state.selection.length === 0}
                    onClick={() => this.setState({ showDeleteDialog: true })}
                >
                    <Translate content="tab.files.button.delete" />
                </Button>
                <List
                    columns={this.cols}
                    data={this.state.items.map(item => ({
                        key: item.id.toString(),
                        name: item.name,
                        path: item.path,
                        size: item.size,
                        mtime: item.mtime,
                    }))}
                    sortColumn="size"
                    sortDirection="DESC"
                    selection={this.state.selection}
                    onSelectionChange={selection => this.setState({ selection })}
                />
            </div>
        );
    }
}

export default FileList;
