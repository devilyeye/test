<?php
/**
 * ShopEx licence
 *
 * @copyright  Copyright (c) 2005-2010 ShopEx Technologies Inc. (http://www.shopex.cn)
 * @license  http://ecos.shopex.cn/ ShopEx License
 */


class express_task
{

    public function post_install()
    {
        logger::info('Initial express');
        kernel::single('base_initial', 'express')->init();

        //初始化电子面单
        $this->initElectronTmpl();
    }//End Function

    public function initElectronTmpl(){
        $dlytpl_dir = ROOT_DIR."/app/express/initial/tpl/";
        if($handle = opendir($dlytpl_dir)){
            while(false !== ($dtp = readdir($handle))){
                $path_parts = pathinfo($dtp);
                if($path_parts['extension'] == 'dtp'){
                    $file['tmp_name'] = $dlytpl_dir.$dtp;
                    $file['name'] = $dtp;
                    $result = kernel::single('express_print_tmpl')->upload_tmpl($file);
                }
            }
            closedir($handle);
        }
    }
}//End Class
