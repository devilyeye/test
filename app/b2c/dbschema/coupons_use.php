<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */

/**
* @table coupons;
*
* @package dbschema
* @version $v1
* @copyright 2010 ShopEx
* @license Commercial
*/

$db['coupons_use']=array (
  'columns' =>
  array (
    'cpns_id' =>
    array (
      'type' => 'varchar(50)',
      'required' => true,
      'pkey' => true,
      'label' => app::get('b2c')->_('id'),
      'width' => 110,
      'comment' => app::get('b2c')->_('优惠券id'),
      'editable' => false,
    ),
    'last_modify' =>
    array (
      'type' => 'last_modify',
      'label' => app::get('b2c')->_('更新时间'),
      'width' => 110,
      'in_list' => true,
      'orderby' => true,
    ),
  ),
  'comment' => app::get('b2c')->_('B类优惠券表'),
);
