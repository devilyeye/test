<?php
$db['channel']=array (
  'columns' => 
  array (
    'channel_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'pkey' => true,
      'label' => app::get('express')->_('渠道ID'),
      'extra' => 'auto_increment',
    ),
    'name' =>
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'editable' => false,
      'label' => app::get('express')->_('来源名称'),
      'width' => '180',
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
      'order' => 10,
    ),
    'shop_id' =>
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'editable' => false,
      'label' => app::get('express')->_('主店铺'),
    ),
    'channel_type' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'default' => 'wlb',
      'label' => app::get('express')->_('渠道类型'),
    ),
    'logistics_code' =>
    array (
      'type' => 'varchar(32)',
      'required' => true,
      'default' => '',
      'label' => app::get('express')->_('物流公司'),
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
    'bind_status' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'false',
      'editable' => false,
      'label' => app::get('express')->_('绑定状态'),
    ),
    'status' =>
    array (
      'type' => 'bool',
      'required' => true,
      'default' => 'true',
      'editable' => false,
      'label' => app::get('express')->_('启用状态'),
      'width' => '80',
      'in_list' => true,
      'default_in_list' => true,
      'order' => 60,
    ),
  ),
  'comment' => '面单来源表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);