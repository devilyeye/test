<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


$db['archive_order_pmt']=array (
  'columns' =>
  array (
    'pmt_id' =>
    array (
      'type' => 'int(8)',
      'required' => true,
      'label' => app::get('b2c')->_('促销规则id'),
      'virtual_pkey' => true,
      'editable' => false,
    ),
    'order_id' =>
    array (
      'type' => 'table:archive_orders:order_id',
      'required' => true,
      'virtual_pkey' => true,
      'label' => app::get('b2c')->_('订单id'),
      'editable' => false,
    ),
    'product_id' =>
    array (
      'type' => 'number',
      'label' => app::get('b2c')->_('商品ID'),//商品促销记录对应的货品ID
    ),
    'pmt_type' =>
    array (
      'type' =>
      array (
        'order' => app::get('b2c')->_('订单'),
        'goods' => app::get('b2c')->_('商品'),
        'coupon' => app::get('b2c')->_('优惠券'),
      ),
      'default' => 'goods',
      'required' => true,
      'comment' => app::get('b2c')->_('优惠规则类型'),
      'virtual_pkey' => true,
      'editable' => false,
      'label' => app::get('b2c')->_('优惠规则类型'),
    ),
    'pmt_amount' =>
    array (
      'type' => 'money',
      'default' => '0',
      'required' => true,
      'editable' => false,
    ),
    'pmt_tag' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'pmt_memo' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
    'pmt_describe' =>
    array (
      'type' => 'longtext',
      'editable' => false,
    ),
  ),
  'comment' => app::get('b2c')->_('订单与商品促销规则的关联表'),
  'version' => '$Rev: 48882 $',
);
