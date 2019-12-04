// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

import {
    Button,
    Component,
    ContentLoader,
    createElement,
    Dialog,
    FormFieldCheckbox,
    FormFieldText,
    Icon,
    List,
    Paragraph,
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
import Loading from 'react-loading-bar';

import {
    Cell,
    Pie,
    PieChart,
    Tooltip,
} from 'recharts';

class UsageList extends Component {
    constructor(props) {
        super(props);

        this.state = {
            dir: this.props.dir,
            items: [],
            itemsLoading: true,
            breadcrumbs: [],
            selection: [],
            showDeleteDialog: false,
            deletionInProgress: false,
            showCleanupDialog: false,
            cleanupInProgress: false,
            chartData: [],
            sizesLoaded: 0,
        };

        this.cols = [{
            key: 'name',
            title: <Translate content="tab.usage.col.name" />,
            sortable: true,
            width: '70%',
            render: row => {
                if (row.isDir) {
                    return (
                        <span style={{ cursor: 'pointer' }} onClick={() => this.load(row.path, true)}>
                            <Icon name="folder-open" />{' '}{row.name}
                        </span>
                    );
                }

                return (
                    <span>
                        <Icon name="site-page" />{' '}{row.name}
                    </span>
                );
            },
        }, {
            key: 'isDir',
            title: <Translate content="tab.usage.col.type" />,
            sortable: true,
            width: '10%',
            render: row => row.isDir ? <Translate content="tab.usage.type.dir" /> : <Translate content="tab.usage.type.file" />,
        }, {
            key: 'size',
            title: <Translate content="tab.usage.col.size" />,
            sortable: true,
            width: '10%',
            style: {
                textAlign: 'right',
            },
            render: row => {
                if (this.state.items[row.itemKey].sizeLoading) {
                    return (
                        <div style={{ textAlign: 'right' }}>
                            <Icon src="/modules/diskspace-usage-viewer/img/loading.gif" />
                        </div>
                    );
                }

                return (
                    <div style={{ textAlign: 'right' }}>
                        {formatBytes(row.size)}
                    </div>
                );
            },
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

        this.colors = ['#0088FE', '#00C49F', '#FFBB28', '#FF8042', '#B8B5E8', '#C0D5D9'];

        this.reload();
    }

    componentDidMount() {
        window.addEventListener('popstate', this.reRender, false);
    }

    reRender = event => {
        if (event.state) {
            this.load(event.state.dir);
        }
    };

    updateBreadcrumbs = dir => {
        const breadcrumbs = [{
            name: '/',
            dir: '/',
        }];

        const curPath = [];
        const segments = dir.split('/');

        segments.forEach(segment => {
            if (segment === '') {
                return;
            }

            curPath.push(segment);

            breadcrumbs.push({
                name: segment,
                dir: `/${curPath.join('/')}`,
            });
        });

        this.setState({ breadcrumbs });
    };

    updateSize = (key, item) => {
        axios
            .get(urlTo('index', 'size'), {
                params: {
                    path: item.path,
                },
            })
            .then(response => {
                const { items } = this.state;
                let { sizesLoaded } = this.state;

                items[key].size = response.data.size;
                items[key].sizeLoading = false;

                sizesLoaded++;

                this.setState({
                    items,
                    sizesLoaded,
                });

                this.updateChartData();
            })
            .catch(() => {
                // Exception intentionally silenced
            });
    };

    batchUpdateSize = files => {
        const chunkSize = (files.length > 500) ? 50 : 10;

        while (files.length) {
            const chunk = files.splice(0, chunkSize);
            const data = [];

            chunk.forEach(file => {
                data.push({
                    key: file.key,
                    path: file.item.path,
                });
            });

            axios
                .get(urlTo('index', 'batch-size'), {
                    params: {
                        json: JSON.stringify(data),
                    },
                })
                .then(response => {
                    const { items } = this.state;
                    let { sizesLoaded } = this.state;

                    response.data.forEach(data => {
                        items[data.key].size = data.size;
                        items[data.key].sizeLoading = false;

                        sizesLoaded++;
                    });

                    this.setState({
                        items,
                        sizesLoaded,
                    });

                    this.updateChartData();
                })
                .catch(() => {
                    // Exception intentionally silenced
                });
        }
    };

    updateChartData = () => {
        const clone = this.state.items.slice(0);

        clone.sort((a, b) => {
            if (a.size > b.size) {
                return -1;
            } else if (a.size < b.size) {
                return 1;
            }

            return 0;
        });

        const data = [];

        clone.slice(0, 5).forEach(item => {
            const name = item.isDir ? `/${item.name}` : item.name;

            data.push({
                name,
                value: item.size,
            });
        });

        if (clone.length > 5) {
            let otherSize = 0;

            clone.slice(5).forEach(item => {
                otherSize += item.size;
            });

            data.push({
                name: this.props.transOthers,
                value: otherSize,
            });
        }

        this.setState({ chartData: data });
    };

    load = (dir, changeState) => {
        if (changeState) {
            history.pushState({ dir }, null, `#${dir}`);
        }

        this.setState({
            dir,
            itemsLoading: true,
            sizesLoaded: 0,
        });

        axios
            .get(urlTo('index', 'usage'), {
                params: {
                    dir,
                },
            })
            .then(response => {
                this.updateBreadcrumbs(dir);

                const items = response.data;

                this.setState({
                    items,
                    itemsLoading: false,
                });

                const files = [];

                for (const key in items) {
                    if (items.hasOwnProperty(key)) {
                        const item = items[key];

                        if (item.isDir) {
                            this.updateSize(key, item);
                        } else {
                            files.push({
                                key,
                                item,
                            });
                        }
                    }
                }

                this.batchUpdateSize(files);
            })
            .catch(() => {
                this.props.onError(<Translate content="message.requestFailed" />);
            });
    };

    reload = () => {
        this.load(this.state.dir);
    };

    cleanup = values => {
        this.setState({
            showCleanupDialog: false,
            cleanupInProgress: true,
        });

        axios
            .post(urlTo('index', 'cleanup'), postParams({
                cleanupCache: values.cleanupCache ? 1 : 0,
                cleanupBackup: values.cleanupBackup ? 1 : 0,
                cleanupBackupDays: values.cleanupBackupDays,
            }))
            .then(() => {
                this.setState({ cleanupInProgress: false });

                this.props.onSuccess(<Translate content="message.cleanupFinished" />);

                this.reload();
            })
            .catch(() => {
                this.props.onError(<Translate content="message.requestFailed" />);
            });
    };

    renderCleanupButton = () => {
        if (!this.props.isAdmin) {
            return (null);
        }

        return (
            <span>
                <Button
                    intent="primary"
                    onClick={() => this.setState({ showCleanupDialog: true })}
                    state={this.state.cleanupInProgress ? 'loading' : null}
                >
                    <Translate content="tab.usage.button.cleanup" />
                </Button>{' '}
            </span>
        );
    };

    showLoadingBar = () => {
        if (this.state.sizesLoaded < this.state.items.length) {
            return true;
        }

        return false;
    };

    render() {
        if (this.state.itemsLoading) {
            return (
                <ContentLoader />
            );
        }

        return (
            <div>
                <Dialog
                    isOpen={this.state.showCleanupDialog}
                    onClose={() => this.setState({ showCleanupDialog: false })}
                    title={<Translate content="tab.usage.cleanupDialog.title" />}
                    size="sm"
                    form={{
                        onSubmit: values => {
                            this.cleanup(values);
                        },
                        submitButton: { children: <span><Icon name="remove" />{' '}{<Translate content="tab.usage.cleanupDialog.button" />}</span> },
                        values: {
                            cleanupCache: true,
                            cleanupBackup: true,
                            cleanupBackupDays: this.props.defaultDaysToKeepBackups,
                        },
                    }}
                >
                    <Paragraph>
                        <Translate content="tab.usage.cleanupDialog.description" />
                    </Paragraph>
                    <Paragraph>
                        <FormFieldCheckbox
                            label={<Translate content="tab.usage.cleanupDialog.cache" />}
                            name="cleanupCache"
                        />
                        <FormFieldCheckbox
                            label={<Translate content="tab.usage.cleanupDialog.backups" />}
                            name="cleanupBackup"
                        />
                        <FormFieldText
                            name="cleanupBackupDays"
                            label={<Translate content="tab.usage.cleanupDialog.backupDays" />}
                        />
                    </Paragraph>
                </Dialog>
                <DeleteDialog
                    isOpen={this.state.showDeleteDialog}
                    onClose={() => this.setState({ showDeleteDialog: false })}
                    onExec={() => {
                        this.setState({
                            showDeleteDialog: false,
                            deletionInProgress: true,
                        });

                        axios
                            .post(urlTo('index', 'delete-by-path'), postParams({
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

                                this.reload();
                            })
                            .catch(() => {
                                this.props.onError(<Translate content="message.requestFailed" />);
                            });
                    }}
                />
                <Loading
                    show={this.showLoadingBar()}
                    color="red"
                    showSpinner={false}
                />
                {this.renderCleanupButton()}
                <Button
                    intent="primary"
                    onClick={() => this.setState({ showDeleteDialog: true })}
                    state={this.state.deletionInProgress ? 'loading' : null}
                    disabled={this.state.selection.length === 0}
                >
                    <Translate content="tab.usage.button.delete" />
                </Button>
                <div id="pathbar-diskspace-usage-viewer" className="pathbar clearfix" style={{ marginTop: '10px' }}>
                    <ul id="pathbar-content-area">
                        {this.state.breadcrumbs.map(({ name, dir }) => (
                            <li key={name} style={{ cursor: 'pointer' }}>
                                <span onClick={() => this.load(dir, true)}>
                                    {name}
                                </span>
                            </li>
                        ))}
                    </ul>
                </div>
                <List
                    columns={this.cols}
                    data={this.state.items.map((item, key) => ({
                        key: item.path,
                        name: item.name,
                        isDir: item.isDir,
                        mtime: item.mtime,
                        size: item.size,
                        path: item.path,
                        itemKey: key,
                    }))}
                    sortColumn="size"
                    sortDirection="DESC"
                    selection={this.state.selection}
                    onSelectionChange={selection => this.setState({ selection })}
                />
                <PieChart
                    width={600}
                    height={400}
                >
                    <Pie
                        dataKey="value"
                        nameKey="name"
                        data={this.state.chartData}
                        cx={300}
                        cy={200}
                        outerRadius={150}
                        fill="#8884d8"
                        isAnimationActive
                        label={data => `${data.name}`}
                    >
                        {
                            this.state.chartData.map((entry, index) => <Cell key={entry.name} fill={this.colors[index % this.colors.length]} />)
                        }
                    </Pie>
                    <Tooltip formatter={value => `${formatBytes(value)}`} />
                </PieChart>
            </div>
        );
    }
}

export default UsageList;
