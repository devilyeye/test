<?php
$db['order_bill']=array (
  'columns' =>
  array (
    'b_id' =>
    array (
      'type' => 'int unsigned',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'order_id' =>
    array (
      'type' => 'table:orders@b2c',
      'required' => true,
      'default' => 0,
      'editable' => false,
      'comment' => app::get('b2c')->_('订单ID'),
    ),
    'logi_id' =>
    array (
      'type' => 'varchar(50)',
      'comment' => app::get('b2c')->_('物流公司ID'),
      'editable' => false,
      'label' => app::get('b2c')->_('物流公司ID'),
      'in_list' => false,
    ),
    'logi_no' =>
    array (
      'type' => 'varchar(50)',
      'label' => app::get('express')->_('物流单号'),
      'editable' => false,
      'width' =>110,
      'filtertype' => 'normal',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'type' =>
    array (
      'type' => 'tinyint(1)',
      'default' => 1,
      'width' => 150,
      'required' => true,
      'label' => app::get('express')->_('物流单主次之分'),
      'editable' => false,
    ),
    'status' =>
    array (
      'type' => 'tinyint(1)',
      'default' => 0,
      'width' => 150,
      'required' => true,
      'editable' => false,
      'label' => app::get('express')->_('处理状态'),
    ),
    'create_time' =>
    array (
      'type' => 'time',
      'label' => app::get('express')->_('创建时间'),
      'editable' => false,
      'filtertype' => 'time',
      'width' =>110,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'last_modify' =>
    array (
      'type' => 'last_modify',
      'label' => app::get('express')->_('最后修改时间'),
      'width' => 110,
      'editable' => false,
      'in_list' => true,
    ),
  ),
  'index' =>
  array (
    'index_logi_no' =>
    array (
      'columns' =>
      array (
        0 => 'logi_no',
      ),
    ),
  ),
  'comment' => '订单面单号表',
  'engine' => 'innodb',
  'version' => '$Rev: 41996 $',
);