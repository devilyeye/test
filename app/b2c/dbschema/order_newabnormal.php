<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

$db['order_newabnormal']=array (
  'columns' =>
  array (
    'order_id' =>
    array (
      'type' => 'bigint unsigned',
      'required' => true,
      'default' => 0,
      'pkey' => true,
      'label' => app::get('b2c')->_('订单号'),
      'is_title' => true,
      'width' => 110,
      'searchtype' => 'has',
      'editable' => false,
      'filtertype' => 'custom',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
    ),
    'abnormal_type' =>
    array (
      'type' => 'varchar(200)',
      'label' => app::get('b2c')->_('错误类型'),
      'hidden' => true,
      'width' => 150,
      'editable' => false,
      'in_list' => true,
    ),
    'msg' =>
    array (
      'type' => 'varchar(200)',
      'label' => app::get('b2c')->_('错误信息'),
      'hidden' => true,
      'width' => 150,
      'editable' => false,
      'filterdefault' => true,
      'in_list' => true,
    ),
   'updatetime' =>
    array (
      'type' => 'time',
      'label' => app::get('b2c')->_('更新时间'),
      'width' => 110,
      'editable' => false,
      'filtertype' => 'time',
      'filterdefault' => true,
      'in_list' => true,
      'default_in_list' => true,
      'orderby' => true,
    ),
  ),

  'engine' => 'innodb',
  'version' => '$Rev: 42377 $',
  'comment' => app::get('b2c')->_('订单支付异常表'),
);

