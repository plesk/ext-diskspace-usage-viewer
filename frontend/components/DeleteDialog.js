// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

import {
    Button,
    Component,
    createElement,
    Dialog,
    Icon,
    Translate,
} from '@plesk/ui-library';

class DeleteDialog extends Component {
    constructor(props) {
        super(props);

        this.state = {

        };
    }

    render() {
        return (
            <Dialog
                isOpen={this.props.isOpen}
                onClose={() => this.props.onClose()}
                title={<Translate content="tab.usage.deleteDialog.title" />}
                size="sm"
                buttons={
                    <Button
                        intent="warning"
                        onClick={() => {
                            this.props.onExec();
                        }}
                    >
                        <Icon name="remove" />{' '}{<Translate content="tab.usage.deleteDialog.button" />}
                    </Button>
                }
            >
                {<Translate content="tab.usage.deleteDialog.description" />}
            </Dialog>
        );
    }
}

export default DeleteDialog;
