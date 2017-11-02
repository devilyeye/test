<?php 
$db['bind']=array (
  'columns' => 
  array (
    'shop_id' => 
    array (
        'type' => 'int(8)',
        'required' => true,
        'pkey' => true,
        'label' => 'id',
        'editable' => false,
        'extra' => 'auto_increment',
        'comment' => app::get('b2c')->_('绑定朋客'),
    ),
    'name' =>
    array (
      'type' => 'varchar(255)',
      'required' => true,
      'label' => app::get('b2c')->_('店铺名称'),
      'editable' => false,
    ),
    'node_id' =>
    array (
      'type' => 'varchar(32)',
      'label' => app::get('b2c')->_('nodeId系统标识'),
      'editable' => false,
    ),
    'secret_key' =>
    array (
      'type' => 'varchar(128)',
      'label' => app::get('b2c')->_('API 密钥'),
      'editable' => false,
    ),
    'status' => 
    array(
      'type' => 
      array (
        '1' => app::get('b2c')->_('绑定'),
        '0' => app::get('b2c')->_('未绑定'),
      ),
      'default' => '0',
      'label' => app::get('b2c')->_('绑定状态'),
      'editable' => false,
      'in_list' => true,
      'default_in_list' => true,
      'is_title' => true,
    ),
  ),
  'comment' => app::get('b2c')->_('绑定联通表'),
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);