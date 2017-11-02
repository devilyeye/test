<?php
$db['waybill_log']=array (
  'columns' => 
  array (
    'log_id' => 
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'label' => app::get('express')->_('日志编号'),
      'width' => 130,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 10,
    ),
    'channel_id' =>
    array (
      'type' => 'table:channel@express',
      'required' => true,
      'editable' => false,
      'label' => app::get('express')->_('请求来源'),
      'width' => 150,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'order' => 20,
    ),
    'status' =>
    array (
      'type' => 
        array (
          'running' => '运行中',
          'success' => '成功',
          'fail' => '失败',
        ),
      'required' => true,
      'default' => 'running',
      'editable' => false,
      'label' => app::get('express')->_('状态'),
      'width' => 60,
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'yes',
      'filterdefault' => true,
      'order' => 30,
    ),
    'retry' =>
    array (
      'type' => 'number',
      'required' => true,
      'default' => 0,
      'edtiable' => false,
      'label' => app::get('express')->_('重试次数'),
      'width' => 60,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 40,
    ),
    'create_time' =>
    array (
      'type' => 'time',
      'editable' => false,
      'label' => app::get('express')->_('创建时间'),
      'width' => '130',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 50,
    ),
    'last_modified' =>
    array (
      'type' => 'last_modify',
      'editable' => false,
      'label' => app::get('express')->_('最后重试时间'),
      'width' => 130,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 60,
    ),
    'params' =>
    array (
      'type' => 'serialize',
      'editable' => false,
      'label' => app::get('express')->_('请求参数'),
    ),
  ),
  'comment' => '请求面单号日志表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);