<?php
return [
    'common' => [
        'search' => [
            'success' => 'Query successfully',
            'fail' => 'Query failed',
        ],
        'bind' => [
            'success' => 'Bind successful',
            'fail' => 'Bind failed',
        ],
        'create' => [
            'success' => 'Add successfully',
            'fail' => 'Add failed',
        ],
        'update' => [
            'success' => 'Update successfully',
            'fail' => 'Update failed',
        ],
        'delete' => [
            'success' => 'Delete successfully',
            'fail' => 'Delete failed',
            'fail_message' => 'Delete failed: :MESSAGE',
        ],
        'upload' => [
            'success' => 'Upload successfully',
            'fail' => 'Upload failed',
            'need_image' => 'Please upload image',
            'image_type_error' => 'Please upload image type',
            'need_file' => 'Please upload file',
            'file_type_error' => 'File type error',
            'file_cannot_empty' => 'File can\'t be empty',
            'file_does_not_exist' => 'File don\'t exist',
        ],
        'offset' => 'Starting number',
        'limit' => 'Number of pieces',
        'order' => 'Sort order',
        'sort' => 'Sort field',
        'start_at' => 'Start date',
        'end_at' => 'End date',
        'select_at_least_one' => 'Select at least one :data',
        'error' => [
            'json_error' => 'JSON error'
        ]
    ],
    'permission' => [
        'permission' => 'Directory / permissions',
        'pid' => 'Superior directory',
        'name' => 'Permission identification',
        'title' => 'Directory name',
        'icon' => 'Table of contents Icon',
        'path' => 'Directory path',
        'component' => 'Directory address',
        'guard_name' => 'Correspondence rules',
        'sort'    =>  'Catalog sorting',
        'hidden'    =>  'Directory display',
        'delete_pid'    =>  'Please delete the subordinate directory first',
        'type' => 'Operation type',
        'change' => 'Your permission has been changed',
    ],
    'role' => [
        'id' => 'Role',
        'name' => 'Role identification',
        'permissions' => 'Permissions / directory',
        'guard_id' => 'Corresponding rule ID',
        'change' => 'Your role has been changed',
    ],
    'admin' => [
        'id' => 'User ID',
        'name' => 'User name',
        'status' => 'Status',
    ],
    'activity' => [
        'log_name' => 'Log name',
        'description' => 'Describe',
        'subject_id' => 'Subject ID',
        'subject_type' => 'Subject type',
        'causer_id' => 'Causer ID',
        'causer_type' => 'Causer type',
        'properties' => 'properties',
    ],
    'exception' => [
        'message' => 'Error message',
        'id' => 'Error ID',
        'solve' => 'Repair value',
        'file' => 'File',
    ],
    'user' => [
        'name' => 'User name',
    ],
    'file' => [
        'file' => 'File',
        'name' => 'File Name',
        'directory' => 'Directory path',
        'not_found' => 'File Not Found'
    ],
    'notification' => [
        'message' => 'message',
        'is_read' => 'is_read',
    ],
    'nginx' => [
        'file' => 'File',
        'ip' => 'Ip',
        'method' => 'Method',
        'uri' => 'Request Uri',
        'http_code' => 'Code',
        'is_warning' => 'Warning',
        'is_error' => 'Error',
        'is_robot' => 'Robot',
        'is_mobile' => 'Mobile',
    ],
    'dict_type' => [
        'name' => 'Dictionary name',
        'type' => 'Dictionary type',
        'status' => 'Dictionary status',
        'remark' => 'Dictionary remark',
    ],
    'dict_data' => [
        'dict_type_id' => 'Dictionary ID',
        'sort' => 'Sort',
        'label' => 'Label',
        'value' => 'Value',
        'list_class' => 'Table echo style',
        'default' => 'Default',
        'status' => 'Status',
        'remark' => 'Remark',
        'list_class_type' => [
            'default' => 'Default',
            'primary' => 'Primary',
            'success' => 'Success',
            'info' => 'Info',
            'warning' => 'Warning',
            'danger' => 'Danger',
        ]
    ],
    'gen' => [
        'table' => 'Table',
        'name' => 'Name',
        'comment' => 'Comment',
        'pid' => 'Category',
        'engine' => 'Engine',
        'charset' => 'Charset',
        'collation' => 'Collation',
        'created_at_start' => 'Created At Start',
        'created_at_end' => 'Created At End',
        'updated_at_start' => 'Updated At Start',
        'updated_at_end' => 'Updated At End',
        'gen_table_columns' => 'Configuration Item',
        'top_nav' => 'Top Nav'
    ]
];
