<?php
$db['channel_extend']=array (
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
    'channel_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'editable' => false,
      'label' => app::get('express')->_('渠道ID'),
    ),
  'province' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('express')->_('省'),
    ), 
    'city' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('express')->_('市'),
    ), 
     'area' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('express')->_('地区'),
    ), 
     'address_detail' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('express')->_('具体地址'),
    ), 
      'waybill_address_id' =>
    array (
      'label' => app::get('express')->_('地址ID'),
      'type' => 'varchar(20)',
      'editable' => false,
    ),
    'cancel_quantity' =>
    array (
      'label' => app::get('express')->_('取消数量'),
      'type' => 'int unsigned',
      'editable' => false,
    ),
    'allocated_quantity' =>
    array (
      'label' => app::get('express')->_('可用数量'),
      'type' => 'int unsigned',
      'editable' => false,
    ),
    'print_quantity' =>
    array (
      'label' => app::get('express')->_('打印数量'),
      'type' => 'int unsigned',
      'editable' => false,
    ),
    'seller_id' =>
    array (
      'label' => app::get('express')->_('用户ID'),
      'type' => 'varchar(32)',
      'editable' => false,
    ),
    'default_sender' =>
    array (
      'type' => 'varchar(255)',
      'editable' => false,
    ),
    'mobile' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'tel' =>
    array (
      'type' => 'varchar(30)',
      'editable' => false,
    ),
    'shop_name' =>
    array (
      'type' => 'varchar(255)',
      ),
      'zip' =>
    array (
      'type' => 'varchar(20)',
      'editable' => false,
    ),

  ),
  'comment' => '面单来源表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);