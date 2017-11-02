<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */
 

class penker_task 
{
    function post_install()
    {
        logger::info('Initial penker');
        kernel::single('base_initial', 'penker')->init();
    }//End Function
}//End Class
