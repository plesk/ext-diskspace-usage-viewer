// Copyright 1999-2019. Plesk International GmbH. All rights reserved.

export const formatBytes = bytes => {
    if (bytes === 0) {
        return '0 kB';
    }

    const units = ['kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB'];

    let i = -1;

    do {
        bytes = bytes / 1024;
        i++;
    } while (bytes > 1024);

    return `${Math.max(bytes, 0.1).toFixed(1)} ${units[i]}`;
};

export const formatTimestamp = stamp => {
    if (stamp <= 0) {
        return '';
    }

    const date = new Date(stamp * 1000);

    return new Intl.DateTimeFormat('default', {
        year: 'numeric',
        month: '2-digit',
        day: '2-digit',
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(date);
};

export const postParams = params => {
    const result = new URLSearchParams();

    for (const key in params) {
        result.append(key, params[key]);
    }

    return result;
};

export const urlTo = (controller, action) => `/modules/diskspace-usage-viewer/index.php/${controller}/${action}`;
