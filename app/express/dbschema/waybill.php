<?php
$db['waybill']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'waybill_number' => 
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'default' => '',
      'editable' => false,
      'label' => app::get('express')->_('运单号'),
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
      'label' => app::get('express')->_('来源渠道'),
      'width' => 150,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 20,
    ),
    'logistics_code' => 
    array (
      'type' => 'varchar(100)',
      'required' => true,
      'default' => '',
      'editable' => false,
      'label' => app::get('express')->_('物流公司'),
      'width' => 100,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 30,
    ),
    'status' =>
    array (
      'type' => 'tinyint unsigned',
      'editable' => false,
      'required' => true,
      'default' => 0,
      'label' => app::get('express')->_('使用状态'),
      'width' => 80,
      'in_list' => true,
      'default_in_list' => true,
      'order' => 40,
    ),
     'create_time' =>
    array (
      'type' => 'time',
      'label' => app::get('express')->_('发放时间'),
      'width' => '130',
      'in_list' => true,
    ),
  ),
  'index' =>
  array (
    'ind_waybill_channel' => array (
      'columns' => array (
        0 => 'waybill_number',
        1 => 'channel_id',
      ),
       'prefix' => 'unique',
    ),
    'ind_create_time' => array(
      'columns' => array('create_time'),
    ),
  ),
  'comment' => '面单号表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);