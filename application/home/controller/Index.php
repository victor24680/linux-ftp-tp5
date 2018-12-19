<?php

namespace app\home\controller;

use think\Controller;
use think\facade\Request;

class Index extends Controller
{
    public function upload()
    {
        $file = Request::file('file');

        //自定义-根路径：文件服务器上需要本身存在此目录【无法自动创建】
        $file->setRootPath('./Uploads/tempImg/test/');
        //自定义-子路径
        $file->setSubSavePath('top/');
        //移动文件-至远程文件服务器；
        $info = $file->driverMove();
        if($info){
            //获取完整的文件路径：./Uploads/tempImg/test/top/20181219/3e8089762b811dc8bbdf7d5b1935ba5f.png
            $file->getFilePathName();
            //返回新的文件名
            $file->getNewFileName();
        }

        //备注：以上只使用于ftp文件服务器上传,本地上传请使用自带的上传程序；
    }
}
