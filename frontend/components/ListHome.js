// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

import {createElement, Component, PropTypes, Translate, Toaster, Tabs, Tab, Button, List, Dialog, Icon, Paragraph, Section, FormFieldCheckbox, FormFieldText, ContentLoader} from '@plesk/ui-library';
import axios from 'axios';

class ListHome extends Component {
    constructor(props)
    {
        super(props);

        this.state = {
            list: this.props.list,
            listData: [],
            loading: false,
            showCleanUpDialog: false,
            showDeleteDialog: false,
            showBiggestFilesDeleteDialog: false,
            showBiggestFilesRefreshDialog: false,
            toaster: [],
            cleanUpButtonLoading: '',
            deleteButtonLoading: '',
            deleteButtonBiggestFilesLoading: '',
            refreshButtonBiggestFilesLoading: '',
            selection: [],
            path: this.props.path,
            breadcrumbsPath: this.props.breadcrumbsPath,
            listColumnsBiggestFiles: [],
            biggestFiles: [],
            biggestFilesLoader: true,
            selectionBiggestFiles: [],
        };

        this.getItemsLink = '/modules/diskspace-usage-viewer/index.php/new/get-items?path=';
        this.getSizeLink = '/modules/diskspace-usage-viewer/index.php/new/get-dir-size?path=';
        this.getBreadcrumbsPathLink = '/modules/diskspace-usage-viewer/index.php/new/get-breadcrumbs-path?path=';
        this.autoCleanUp = '/modules/diskspace-usage-viewer/index.php/new/cleanup?settings=';
        this.deleteLink = '/modules/diskspace-usage-viewer/index.php/new/delete';
        this.getBiggestFilesLink = '/modules/diskspace-usage-viewer/index.php/new/get-biggest-files';

        this.listColumn();
        this.listDataOnLoad();

        this.listColumnBiggestFiles();
        this.listDataBiggestFiles = [];
        this.listBiggestFiles();
    }

    listColumn = () => {
        this.listColumns = [
            {
                key: 'col1',
                title: <Translate content="listName"/>,
                width: '50%',
            },
            {
                key: 'col2',
                title: <Translate content="listType"/>,
            },
            {
                key: 'col3',
                title: <Translate content="listSize"/>,
                width: '10%',
            },
        ];
    };

    listDataOnLoad = () => {
        Object.keys(this.state.list).map(key => (
            this.state.listData[key] = {
                listKey: key,
                key: this.state.list[key].path,
                col1: this.formatListButton(key),
                col2: this.getFileType(this.state.list[key].isDir),
                col3: this.formatBytes(this.state.list[key].size, true),
            }
        ));
    };

    listData = () => {
        let listData = [];

        Object.keys(this.state.list).map(key => (
            listData[key] = {
                listKey: key,
                key: this.state.list[key].path,
                col1: this.formatListButton(key),
                col2: this.getFileType(this.state.list[key].isDir),
                col3: this.formatBytes(this.state.list[key].size, true),
            }
        ));

        this.setState({
            listData: listData,
        })
    };

    listColumnBiggestFiles = () => {
        this.listColumnsBiggestFiles = [
            {
                key: 'col1',
                title: <Translate content="listBiggestFileName"/>,
            }, {
                key: 'col2',
                title: <Translate content="listBiggestFilePath"/>,
            }, {
                key: 'col3',
                title: <Translate content="listBiggestFileSize"/>,
                width: '10%',
            },
        ];
    };

    listBiggestFiles = () => {
        axios.get(this.getBiggestFilesLink)
            .then((response) => {
                if(response.data.success === true)
                {
                    this.listDataBiggestFiles = [];
                    this.biggestFiles = response.data.data;
                    this.createListDataBiggestFiles();

                    console.log(JSON.stringify(this.listDataBiggestFiles));

                    this.setState({
                        biggestFiles: response.data.data,
                        biggestFilesLoader: false,
                        listDataBiggestFiles: this.listDataBiggestFiles,
                    });
                }
            })
            .catch((error) => {
                this.toaster.add({
                    intent: 'danger',
                    message: this.messageErrorTranslate(error)
                });
            });
    };

    createListDataBiggestFiles = () => {
        Object.keys(this.biggestFiles).map(key => (
            this.listDataBiggestFiles[key] = {
                key: this.biggestFiles[key].id,
                col1: this.biggestFiles[key].filename,
                col2: this.biggestFiles[key].path,
                col3: this.formatBytes(this.biggestFiles[key].size, true),
            }
        ))
    };

    formatListButton = (key) => {
        if(this.state.list[key].isDir === true)
        {
            return (
                <span className="cursor-pointer" onClick={() => this.getItems(this.state.list[key].path)}>
                    <Icon name="folder-open" size="16"/>{' '}{this.state.list[key].displayName}
                </span>
            )
        }

        return (
            <span>
                <Icon name="site-page" size="16"/>{' '}{this.state.list[key].displayName}
            </span>
        )
    };

    formatBytes = (bytes, initialLoad) => {
        if(bytes === 0)
        {
            if(initialLoad)
            {
                return (
                    <div className="lds-spinner">
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                        <div></div>
                    </div>
                )
            }

            return (
                <div>0 B</div>
            )
        }

        var k = 1024;
        var sizes = ['B', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));

        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    };

    componentDidMount()
    {
        Object.keys(this.state.listData).forEach(key => (
            this.getSizeDynamically(key)
        ));
    }

    getSizeDynamically = (key) => {
        const listEntry = this.state.listData[key].listKey;

        if(this.state.list[listEntry].size === 0)
        {
            axios.get(this.getSizeLink + this.state.list[listEntry].path)
                .then((response) => {
                    if(response.status === 200)
                    {
                        const listData = this.state.listData;
                        listData[key].col3 = this.formatBytes(response.data, false);

                        this.setState({
                            listData: listData,
                        });
                    }
                })
                .catch((error) => {
                });
        }
    };

    getFileType = (isDir) => {
        if(isDir === true)
        {
            return (
                <Translate content="isDir"/>
            )
        }

        return (
            <Translate content="isFile"/>
        )
    };

    cleanUp = (values) => {
        this.setState({
            cleanUpButtonLoading: 'loading',
        });

        axios.get(this.autoCleanUp + JSON.stringify(values))
            .then((response) => {
                if(response.data.success === true)
                {
                    this.toaster.add({
                        intent: 'success',
                        message: response.data.message,
                    });
                }
                else
                {
                    this.toaster.add({
                        intent: 'warning',
                        message: response.data.message,
                    });
                }

                setTimeout(() => {
                    this.toaster.clear();
                }, 10000);

                this.setState({
                    cleanUpButtonLoading: '',
                });
            })
            .catch((error) => {
            });
    };

    delete = () => {
        let selectedIds = this.state.selection;

        this.setState({
            deleteButtonLoading: 'loading',
            selection: [],
        });

        axios.post(this.deleteLink, {
                paths: selectedIds,
            })
            .then((response) => {
                if(response.data.success === true)
                {
                    this.toaster.add({
                        intent: 'success',
                        message: response.data.message,
                    });
                }
                else
                {
                    this.toaster.add({
                        intent: 'warning',
                        message: response.data.message,
                    });
                }

                setTimeout(() => {
                    this.toaster.clear();
                }, 10000);

                this.getItems(this.state.path);

                this.setState({
                    deleteButtonLoading: '',
                });
            })
            .catch((error) => {
            });
    };

    getItems = (path) => {
        axios.get(this.getItemsLink + path)
            .then((response) => {
                if(response.status === 200)
                {
                    this.setState({
                        list: response.data,
                        path: path,
                    });

                    this.listData();
                    this.getBreadcrumbs(path);

                    Object.keys(this.state.listData).forEach(key => (
                        this.getSizeDynamically(key)
                    ));
                }
            })
            .catch((error) => {
            });
    };

    getBreadcrumbs = (path) => {
        axios.get(this.getBreadcrumbsPathLink + path)
            .then((response) => {
                if(response.status === 200)
                {
                    this.setState({
                        breadcrumbsPath: response.data,
                    });
                }
            })
            .catch((error) => {
            });
    };

    addBreadcrumbs = () => {
        return (
            <div id="pathbar-diskspace-usage-viewer" className="breadcrumbs pathbar clearfix">
                <ul id="pathbar-content-area">
                    {this.state.breadcrumbsPath.map(({name, path}) => (
                        <li className="cursor-pointer">
                        <span onClick={() => this.getItems(path)}>
                            {name}
                        </span><b>&gt;</b>
                        </li>
                    ))}
                </ul>
            </div>
        )
    };

    addDeleteButton()
    {
        return (
            <Button
                intent="primary"
                onClick={() => this.setState({
                    showDeleteDialog: true
                })}
                state={this.state.deleteButtonLoading}
                // TODO Add disabled state
            >
                <Translate content="actionButtonDelete"/>
            </Button>
        )
    };

    addBiggestFilesDeleteButton()
    {
        return (
            <div>
                <Button
                    intent="primary"
                    onClick={() => this.setState({
                        showBiggestFilesRefreshDialog: true
                        // TODO Add dialog or start long task directly
                    })}
                    state={this.state.refreshButtonBiggestFilesLoading}
                >
                    <Translate content="actionButtonRefresh"/>
                </Button>{' '}
                <Button
                    intent="primary"
                    onClick={() => this.setState({
                        showBiggestFilesDeleteDialog: true
                        // TODO Add dialog
                    })}
                    state={this.state.deleteButtonBiggestFilesLoading}
                    // TODO Add disabled state
                >
                    <Translate content="actionButtonDelete"/>
                </Button>
            </div>
        )
    };

    addCleanUpButton()
    {
        return (
            <Button
                intent="primary"
                onClick={() => this.setState({
                    showCleanUpDialog: true
                })}
                state={this.state.cleanUpButtonLoading}
            >
                <Translate content="actionButtonCleanUp"/>
            </Button>
        )
    };

    addDialogScreens()
    {
        return (
            <div>
                <Dialog
                    isOpen={this.state.showCleanUpDialog === true}
                    onClose={() => this.setState({showCleanUpDialog: false})}
                    title={<Translate content="dialogCleanUpTitle"/>}
                    size="sm"
                    form={{
                        onSubmit: (values) => {
                            this.setState({showCleanUpDialog: false});
                            this.cleanUp(values);
                        },
                        submitButton: {children: <span><Icon name="remove" size="16"/>{' '}{<Translate content="dialogCleanUpButton"/>}</span>},
                        values: {
                            cleanUpSelectionCache: true,
                            cleanUpSelectionBackup: true,
                            cleanUpBackupDays: 90,
                        }
                    }}
                >
                    <Paragraph>
                        <Translate content="dialogCleanUpDescription"/>
                    </Paragraph>
                    <Section title={<Translate content="dialogCleanUpSettingsTitle"/>}>
                        <FormFieldCheckbox
                            label={<Translate content="dialogCleanUpSettingsCache"/>}
                            name="cleanUpSelectionCache"
                        />
                        <FormFieldCheckbox
                            label={<Translate content="dialogCleanUpSettingsBackup"/>}
                            name="cleanUpSelectionBackup"
                        />
                        <FormFieldText
                            name="cleanUpBackupDays"
                            label={<Translate content="dialogCleanUpSettingsBackupDays"/>}
                        />
                    </Section>
                </Dialog>
                <Dialog
                    isOpen={this.state.showDeleteDialog === true}
                    onClose={() => this.setState({showDeleteDialog: false})}
                    title={<Translate content="dialogDeleteTitle"/>}
                    size="sm"
                    buttons={
                        <Button onClick={() => {
                            this.setState({showDeleteDialog: false});
                            this.delete();
                        }} intent="warning">
                            <Icon name="remove" size="16"/>{' '}{<Translate content="dialogDeleteButton"/>}
                        </Button>}
                >
                    {<Translate content="dialogDeleteDescription"/>}
                </Dialog>
            </div>
        )
    };

    showBiggestFilesList = () => {
        if(this.state.biggestFilesLoader === true)
        {
            return (
                <ContentLoader/>
            )
        }

        return (
            <List
                columns={this.listColumnsBiggestFiles}
                data={this.state.listDataBiggestFiles}
                selection={this.state.selectionBiggestFiles}
                onSelectionChange={selection => this.setState({selection})}
            />
        )
    };

    messageErrorTranslate(error)
    {
        return (
            <span>
                <Translate content='requestMessageError'/>{' '}
                {error}
            </span>
        );
    };

    render()
    {
        return (
            <Tabs>
                <Tab title={<Translate content='overviewTabMain'/>}>
                    <Toaster ref={ref => (this.toaster = ref)}/>
                    {this.addDialogScreens()}
                    {this.addCleanUpButton()}{' '}
                    {this.addDeleteButton()}
                    {this.addBreadcrumbs()}
                    <List
                        columns={this.listColumns}
                        data={this.state.listData}
                        sortColumn="col1"
                        selection={this.state.selection}
                        onSelectionChange={selection => this.setState({selection})}
                    />
                </Tab>
                <Tab title={<Translate content='overviewTabBiggestFiles'/>}>
                    {this.addBiggestFilesDeleteButton()}
                    {this.showBiggestFilesList()}
                </Tab>
            </Tabs>
        )
    }
}

export default ListHome;
