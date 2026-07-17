<?php

declare(strict_types=1);

return [

    'navigation' => [
        'label' => 'Logs',
        'group' => 'System',
    ],

    'page' => [
        'title' => 'Logs',
        'heading' => 'Logs explorer',
        'subheading' => 'Browse, read and search your application log files, grouped by channel.',
    ],

    'actions' => [
        'refresh' => 'Refresh',
        'refresh_tooltip' => 'Re-scan the log files on disk',
    ],

    'channels' => [
        'untracked' => 'Other files',
        'file_count' => '{0} No file|{1} :count file|[2,*] :count files',
    ],

    'list' => [
        'empty' => [
            'heading' => 'No log files found',
            'description' => 'No readable log file was found for the configured channels.',
        ],
        'size' => 'Size',
        'modified' => 'Modified :time',
        'view' => 'View',
        'unreadable' => 'Unreadable',
    ],

    'viewer' => [
        'title' => 'Log file',
        'channel' => 'Channel',
        'size' => 'Size',
        'modified' => 'Modified',
        'position' => 'File :current of :total',
        'search_placeholder' => 'Search in this file…',
        'previous_file' => 'Previous file',
        'next_file' => 'Next file',
        'previous_match' => 'Previous match',
        'next_match' => 'Next match',
        'go_to_top' => 'Go to top',
        'go_to_bottom' => 'Go to bottom',
        'matches' => ':current / :total',
        'no_matches' => 'No match',
        'lines' => '{0} empty|{1} :count line|[2,*] :count lines',
        'truncated_tail' => 'Large file: showing the last :size (:lines). Download the file to read it all.',
        'truncated_head' => 'Large file: showing the first :size (:lines). Download the file to read it all.',
        'empty' => 'This file is empty.',
        'unreadable' => 'This file could not be read.',
        'copy' => 'Copy',
        'copied' => 'Copied!',
        'download' => 'Download',
        'delete' => 'Delete',
        'close' => 'Close',
        'keyboard_hint' => 'Shortcuts: / search · n / N next / previous match · g / G top / bottom',
    ],

    'delete' => [
        'modal_heading' => 'Delete this log file?',
        'modal_description' => 'Permanently delete ":name"? This cannot be undone.',
        'success_title' => 'File deleted',
        'success_body' => '":name" was deleted.',
        'failed_title' => 'Deletion failed',
        'failed_body' => 'Could not delete ":name".',
    ],

];
