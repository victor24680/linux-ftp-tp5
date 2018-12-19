##### Thinkphp5.1 实现远程文件服务器上传
 
1. Linux下搭建FTP服务器
 - 搭建地址：[http://docs.myvictortst.com/%E6%9C%8D%E5%8A%A1%E6%90%AD%E5%BB%BA/Linux-FTP%E6%9C%8D%E5%8A%A1%E6%90%AD%E5%BB%BA/](http://docs.myvictortst.com/%E6%9C%8D%E5%8A%A1%E6%90%AD%E5%BB%BA/Linux-FTP%E6%9C%8D%E5%8A%A1%E6%90%AD%E5%BB%BA/)

2. 搭建Web运行环境 【Windows,推荐使用 PHPStudy 集成化工具】
3. 下载Thinkphp5.1 【查看文档】
4. 在config/app.php 下加载如下配置文件

```

    'upload_type_config' => [
        'host'     => '127.0.0.1', //服务器
        'port'     => 21, 			//端口
        'timeout'  => 90, 			//超时时间
        'username' => 'ftpuser', 	//用户名
        'password' => 'root', 		//密码
        'pasv'     => true, 		//是否开启被动模式,true开启,默认不开启
        'ssl'      => false, 		//ssl连接,默认不开启
    ]

 	'is_ftp_dirver' => 1, //开启FTP服务

```

###### 备注
 1. 默认使用的上传目录为:./Uploads/tempImg/test/,记得在文件服务器上添加此目录
 2. 自定义设定的根目录也必须先在文件服务上创建。
 3. 上传方法：/application/home/controller/Index.php
 4. 所设定的目录斜杠：请使用'/'斜杠，Linux上不识别 '\'