<?php
// Copyright 1999-2018. Plesk International GmbH. All rights reserved.

namespace PleskExt\DiskspaceUsageViewer;

use PleskExt\DiskspaceUsageViewer\Helper;

class UsageList extends \pm_View_List_Simple
{
    private $currentPath;

    private function getFullPath($folderName)
    {
        if (substr($this->currentPath, -1) == '/') {
            return $this->currentPath . $folderName;
        } else {
            return $this->currentPath . '/' . $folderName;
        }
    }

    public function __construct(\Zend_View $view, \Zend_Controller_Request_Abstract $request, $currentPath, array $usage)
    {
        $options = [
            'defaultSortField' => 'size',
            'defaultSortDirection' => self::SORT_DIR_DOWN,
        ];

        parent::__construct($view, $request, $options);

        $this->currentPath = $currentPath;

        $this->setColumns([
            self::COLUMN_SELECTION,
            'path' => [
                'title' => \pm_Locale::lmsg('columnPath'),
                'noEscape' => true,
                'sortable' => true,
                'searchable' => true,
            ],
            'size' => [
                'title' => \pm_Locale::lmsg('columnSize'),
                'noEscape' => true,
                'sortable' => true,
                'cls' => 'number t-r',
            ],
        ]);

        $data = [];

        foreach ($usage as $item) {
            $data[] = [
                'id' => $this->getFullPath($item['name']),
                'path' => $item['displayName'],
                'size' => $item['size'],
                'isDir' => $item['isDir'],
            ];
        }

        $this->setData($data);

        $this->setDataUrl(Helper::getActionUrl('index-data', ['path' => $currentPath]));

        $tools = [
            [
                'title' => \pm_Locale::lmsg('buttonRefresh'),
                'class' => 'sb-refresh',
                'link' => 'javascript:extDiskspaceUsageViewerRefresh(' . json_encode($currentPath) . ')',
            ],
        ];

        if (Helper::isDeleteEnabled()) {
            $tools[] = [
                'title' => \pm_Locale::lmsg('buttonDelete'),
                'execGroupOperation' => [
                    'skipConfirmation' => false,
                    'subtype' => 'delete',
                    'locale' => ['confirmOnGroupOperation' => \pm_Locale::lmsg('confirmDelete')],
                    'url' => Helper::getActionUrl('delete-selected'),
                ],
                'class' => 'sb-delete-selected',
            ];
        }

        $this->setTools($tools);
    }

    public function fetchData()
    {
        $data = parent::fetchData();

        foreach ($data['data'] as &$row) {
            if ($row['isDir']) {
                $row['path'] = '<a href="' . htmlspecialchars(Helper::getActionUrl('index', ['path' => $row['id']])) . '">' . htmlspecialchars($row['path']) . '</a>';
            } else {
                $row['path'] = htmlspecialchars($row['path']);
            }

            $row['size'] = htmlspecialchars(Helper::formatSize($row['size']));
        }

        return $data;
    }
}
