// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

import {
    Button,
    createElement,
    Dialog,
    Icon,
    Translate,
} from '@plesk/ui-library';

const DeleteDialog = props => (
    <Dialog
        isOpen={props.isOpen}
        onClose={() => props.onClose()}
        title={<Translate content="tab.usage.deleteDialog.title" />}
        size="sm"
        buttons={
            <Button
                intent="warning"
                onClick={() => {
                    props.onExec();
                }}
            >
                <Icon name="remove" />{' '}{<Translate content="tab.usage.deleteDialog.button" />}
            </Button>
        }
    >
        {<Translate content="tab.usage.deleteDialog.description" />}
    </Dialog>
);

export default DeleteDialog;
