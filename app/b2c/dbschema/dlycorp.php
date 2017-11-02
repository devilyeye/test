<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 
$db['dlycorp']=array (
  'columns' => 
  array (
    'corp_id' => 
    array (
      'type' => 'number',
      'required' => true,
      'pkey' => true,
      'extra' => 'auto_increment',
      'label' => app::get('b2c')->_('物流公司ID'),
      'width' => 110,
      'editable' => false,
      'hidden' => true,
    ),
    'type' => 
    array (
      'type' => 'varchar(6)',
      'editable' => false,
      'is_title' => true,
      'comment' => app::get('b2c')->_('类型'),
    ),
	'corp_code' => 
    array (
      'type' => 'varchar(200)',
      'label' => app::get('b2c')->_('物流公司代码'),
      'width' => 180,
      'editable' => false,
      'default_in_list' => false,
      'in_list' => true,
    ),
    'name' => 
    array (
      'type' => 'varchar(200)',
      'label' => app::get('b2c')->_('物流公司'),
      'width' => 180,
      'editable' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'ordernum' => 
    array (
      'type' => 'smallint(4) unsigned',
      'label' => app::get('b2c')->_('排序'),
      'width' => 180,
      'editable' => true,
      'in_list' => true,
    ),
    'website' => 
    array (
      'type' => 'varchar(200)',
      'label' => app::get('b2c')->_('物流公司网址'),
      'width' => 180,
      'editable' => true,
      'default_in_list' => true,
      'in_list' => true,
    ),
    'request_url' => 
    array (
      'type' => 'varchar(200)',
      'label' => app::get('b2c')->_('查询接口网址'),
      'width' => 180,
      'hidden'=>false,
      'editable' => true,
      'in_list' => true,
    ),
    'channel_id' =>
    array (
      'type' => 'table:channel@express',
      'default' => '0',
      'editable' => false,
      'label' => app::get('b2c')->_('面单来源'),
      'width' => 130,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'tmpl_type' =>
    array (
      'type' => array(
        'normal' => '普通面单',
        'electron' => '电子面单',
      ),
      'editable' => false,
      'required' => true,
      'default' => 'normal',
      'label' => app::get('b2c')->_('快递模板类型'),
      'in_list' => true,
      'default_in_list' => true,
      'filtertype' => 'normal',
      'filterdefault' => true,
    ),
    'prt_tmpl_id' =>
    array (
      'type' => 'table:print_tmpl@express',
      'default' => '0',
      'editable' => false,
    ),
    'disabled' => 
    array (
      'type' => 'bool',
      'default' => 'false',
      'editable' => false,
      'comment' => app::get('b2c')->_('失效'),
    ),
  ),
  'index' => 
  array (
    'ind_type' => 
    array (
      'columns' => 
      array (
        0 => 'type',
      ),
    ),
    'ind_disabled' => 
    array (
      'columns' => 
      array (
        0 => 'disabled',
      ),
    ),
    'ind_ordernum' => 
    array (
      'columns' => 
      array (
        0 => 'ordernum',
      ),
    ),
  ),
  'version' => '$Rev$',
  'comment' => app::get('b2c')->_('物流公司表'),
);
