<?php

$db['waybill_extend']=array (
  'columns' => 
  array (
    'id' => 
    array (
      'type' => 'bigint(15)',
      'required' => true,
      'pkey' => true,
      'editable' => false,
      'extra' => 'auto_increment',
    ),
    'waybill_id' => 
    array (
      'type' => 'table:waybill@express',
      'required' => true,
      'editable' => false,
      'label' => '运单ID',
      'order' => 10,
    ),
    'mailno_barcode' =>
    array (
      'type' => 'varchar(40)',
      'editable' => false,
      'label' => app::get('express')->_('运单号条形码'),
      'width' => 150,
      'in_list' => true,
      'default_in_list' => true,
      'default' => '',
      'order' => 20,
    ),
    'qrcode' => 
    array (
      'type' => 'text',
      'default' => '',
      'editable' => false,
      'label' => app::get('express')->_('条形码'),
      'width' => 100,
      'default' => '',
      'order' => 30,
    ),
    'position' => array(
      'type' => 'varchar(40)',
      'required' => true,
      'editable' => false,
      'label' => app::get('express')->_('大头笔'),
      'width' => 150,
      'default' => '',
      'order' => 40,
    ),
    'position_no' => array(
      'type' => 'varchar(40)',
      'editable' => false,
      'label' => app::get('express')->_('大头笔编码'),
      'width' => 150,
      'default' => '',
      'order' => 40,
    ),
    'package_wdjc' => array(
      'type' => 'varchar(40)',
      'editable' => false,
      'label' => app::get('express')->_('集包地'),
      'width' => 150,
      'default' => '',
      'order' => 50,
    ),
    'package_wd' => array(
      'type' => 'varchar(40)',
      'editable' => false,
      'label' => app::get('express')->_('集包地编码'),
      'width' => 150,
      'default' => '',
      'order' => 50,
    ),
    'print_config' => array(
      'type' => 'longtext',
      'editable' => false,
      'label' => app::get('express')->_('菜鸟面单配置'),
      'width' => 150,
      'default' => '',
      'order' => 60,
    ),
    'json_packet' =>
    array (
      'type' => 'text',
      'editable' => false,
      'default' => '',
      'label' => app::get('express')->_('面单数据详情'),
      'width' => 80,
      'order' => 60,
    ),
  ),
  'comment' => '运单扩展表',
  'engine' => 'innodb',
  'version' => '$Rev:  $',
);