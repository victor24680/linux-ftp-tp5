<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006~2018 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: liu21st <liu21st@gmail.com>
// +----------------------------------------------------------------------
namespace think;

use SplFileObject;
use think\upload\driver\Ftp;

class File extends SplFileObject
{
    /**
     * 错误信息
     * @var string
     */
    private $error = '';

    /**
     * 当前完整文件名
     * @var string
     */
    protected $filename;

    /**
     * 上传文件名
     * @var string
     */
    protected $saveName;

    /**
     * 上传文件命名规则
     * @var string
     */
    protected $rule = 'date';

    /**
     * 上传文件验证规则
     * @var array
     */
    protected $validate = [];

    /**
     * 是否单元测试
     * @var bool
     */
    protected $isTest;

    /**
     * 上传文件信息
     * @var array
     */
    protected $info = [];

    /**
     * 文件hash规则
     * @var array
     */
    protected $hash = [];

    //驱动设定参数;
    //设置驱动模式【是否设置驱动模式】
    protected $uploader;
    protected $rootPath    = './Uploads/tempImg/test/';//设置跟目录
    protected $subSavePath = 'top/';//上传子路径
    protected $renameFileName;//获取完整的上传之后的文件名
    protected $filePathName;//获取完整文件件路径;

    public function __construct($filename = '', $mode = 'r')
    {
        parent::__construct($filename, $mode);
        $this->filename = $this->getRealPath() ? : $this->getPathname();

        //是否启用FTP驱动
        $is_ftp_driver = config('is_ftp_dirver');
        if ($is_ftp_driver == 1) {
            $this->setDriver();
        }
    }

    /**
     * 设置上传驱动【主要FTP驱动】
     * @author:victor
     *
     * @param string $driver 驱动名称
     * @param array $config 驱动配置
     */
    private function setDriver()
    {
        $config         = config('upload_type_config');
        $this->uploader = new Ftp($config);
        if (!$this->uploader) {
            $this->error = "不存在上传驱动";
        }
    }

    //设定自定义-根路径
    public function setRootPath($path = '')
    {
        if (!empty($path)) {
            $this->rootPath = $path;
        }
    }

    /**
     * @author:victor
     * 设定自定义子路径：必须以“目录名/”格式，如:img/
     */
    public function setSubSavePath($path='')
    {
        if (!empty($path)) {
            $this->subSavePath = $path;
        }
    }

    /**
     * @author:victor
     * @移动文件-至文件服务器
     * @return bool
     */
    public function driverMove()
    {
        //捕捉上传错误代码
        if (!empty($this->info['error'])) {
            $this->error($this->info['error']);

            return false;
        }
        //检测合法性
        if (!$this->isValid()) {
            $this->error = 'upload illegal files';

            return false;
        }
        //验证尺寸大小规则
        if (!$this->check()) {
            return false;
        }
        /**
         * ["name"] => string(22) "xxxsaj.png"
         * ["type"] => string(9) "image/png"
         * ["tmp_name"] => string(22) "C:\Windows\phpEC09.tmp"
         * ["error"] => int(0)
         * ["size"] => int(15775)
         */
        $info = $this->info;
        //检查根目录是否存在
        if (!$this->uploader->checkRootPath($this->rootPath)) {
            $this->error = $this->uploader->getError();

            return false;
        }
        //检测子目录是否存在
        if (!$this->uploader->checkSavePath($this->subSavePath)) {
            $this->error = $this->uploader->getError();

            return false;
        }
        //创建子目录
        $sub_dir = $this->subSavePath . date('Ymd', time()) . '/';
        if (!$this->uploader->mkdir($sub_dir)) {
            $this->error = $this->uploader->getError();

            return false;
        }
        //重命名文件名
        $rename = $this->getReFileName($info['name']);
        $this->filePathName = $this->rootPath . $sub_dir . $rename;
        //移动文件
        $data = [
            'savepath' => $sub_dir,
            'savename' => $rename,
            'tmp_name' => $this->info['tmp_name'],
            'type'     => $this->info['type'],
        ];
        if (!$this->uploader->save($data)) {
            $this->error = '远程移动文件失败';

            return false;
        }

        return true;
    }

    /**
     * @author:victor;
     *
     * @param $name 原来文件名
     * @param string $self_name 自定义文件名-【规则：自定义】
     *
     * @return string
     */
    protected function getReFileName($name, $self_name = '')
    {
        //后去文件名的后缀
        $ext = pathinfo($name, PATHINFO_EXTENSION);
        if (!empty($self_name)) {
            $new_name = $self_name . '.' . $ext;
        } else {
            $new_name = md5(date('Ymd', time()) . rand(10000000, 99999999)) . '.' . $ext;
        }
        $this->renameFileName = $new_name;

        return $new_name;
    }

    /**
     * @return mixed 返回完整的文件路径
     */
    public function getFilePathName()
    {
        return $this->filePathName;
    }

    /**
     * 返回新的文件名
     * @return mixed
     */
    public function getNewFileName()
    {
        return $this->renameFileName;
    }

    /**
     * 是否测试
     * @access public
     *
     * @param  bool $test 是否测试
     *
     * @return $this
     */
    public function isTest($test = false)
    {
        $this->isTest = $test;

        return $this;
    }

    /**
     * 设置上传信息
     * @access public
     *
     * @param  array $info 上传文件信息
     *
     * @return $this
     */
    public function setUploadInfo($info)
    {
        $this->info = $info;

        return $this;
    }

    /**
     * 获取上传文件的信息
     * @access public
     *
     * @param  string $name
     *
     * @return array|string
     */
    public function getInfo($name = '')
    {
        return isset($this->info[$name]) ? $this->info[$name] : $this->info;
    }

    /**
     * 获取上传文件的文件名
     * @access public
     * @return string
     */
    public function getSaveName()
    {
        return $this->saveName;
    }

    /**
     * 设置上传文件的保存文件名
     * @access public
     *
     * @param  string $saveName
     *
     * @return $this
     */
    public function setSaveName($saveName)
    {
        $this->saveName = $saveName;

        return $this;
    }

    /**
     * 获取文件的哈希散列值
     * @access public
     *
     * @param  string $type
     *
     * @return string
     */
    public function hash($type = 'sha1')
    {
        if (!isset($this->hash[$type])) {
            $this->hash[$type] = hash_file($type, $this->filename);
        }

        return $this->hash[$type];
    }

    /**
     * 检查目录是否可写
     * @access protected
     *
     * @param  string $path 目录
     *
     * @return boolean
     */
    protected function checkPath($path)
    {
        if (is_dir($path)) {
            return true;
        }
        if (mkdir($path, 0755, true)) {
            return true;
        }
        $this->error = ['directory {:path} creation failed', ['path' => $path]];

        return false;
    }

    /**
     * 获取文件类型信息
     * @access public
     * @return string
     */
    public function getMime()
    {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);

        return finfo_file($finfo, $this->filename);
    }

    /**
     * 设置文件的命名规则
     * @access public
     *
     * @param  string $rule 文件命名规则
     *
     * @return $this
     */
    public function rule($rule)
    {
        $this->rule = $rule;

        return $this;
    }

    /**
     * 设置上传文件的验证规则
     * @access public
     *
     * @param  array $rule 验证规则
     *
     * @return $this
     */
    public function validate($rule = [])
    {
        $this->validate = $rule;

        return $this;
    }

    /**
     * 检测是否合法的上传文件
     * @access public
     * @return bool
     */
    public function isValid()
    {
        if ($this->isTest) {
            return is_file($this->filename);
        }

        return is_uploaded_file($this->filename);
    }

    /**
     * 检测上传文件
     * @access public
     *
     * @param  array $rule 验证规则
     *
     * @return bool
     */
    public function check($rule = [])
    {
        $rule = $rule ? : $this->validate;
        if ((isset($rule['size']) && !$this->checkSize($rule['size']))
            || (isset($rule['type']) && !$this->checkMime($rule['type']))
            || (isset($rule['ext']) && !$this->checkExt($rule['ext']))
            || !$this->checkImg()
        ) {
            return false;
        }

        return true;
    }

    /**
     * 检测上传文件后缀
     * @access public
     *
     * @param  array|string $ext 允许后缀
     *
     * @return bool
     */
    public function checkExt($ext)
    {
        if (is_string($ext)) {
            $ext = explode(',', $ext);
        }
        $extension = strtolower(pathinfo($this->getInfo('name'), PATHINFO_EXTENSION));
        if (!in_array($extension, $ext)) {
            $this->error = 'extensions to upload is not allowed';

            return false;
        }

        return true;
    }

    /**
     * 检测图像文件
     * @access public
     * @return bool
     */
    public function checkImg()
    {
        $extension = strtolower(pathinfo($this->getInfo('name'), PATHINFO_EXTENSION));
        /* 对图像文件进行严格检测 */
        if (in_array($extension, ['gif', 'jpg', 'jpeg', 'bmp', 'png', 'swf']) && !in_array($this->getImageType($this->filename), [1, 2, 3, 4, 6, 13])) {
            $this->error = 'illegal image files';

            return false;
        }

        return true;
    }

    // 判断图像类型
    protected function getImageType($image)
    {
        if (function_exists('exif_imagetype')) {
            return exif_imagetype($image);
        }
        try {
            $info = getimagesize($image);

            return $info ? $info[2] : false;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * 检测上传文件大小
     * @access public
     *
     * @param  integer $size 最大大小
     *
     * @return bool
     */
    public function checkSize($size)
    {
        if ($this->getSize() > $size) {
            $this->error = 'filesize not match';

            return false;
        }

        return true;
    }

    /**
     * 检测上传文件类型
     * @access public
     *
     * @param  array|string $mime 允许类型
     *
     * @return bool
     */
    public function checkMime($mime)
    {
        if (is_string($mime)) {
            $mime = explode(',', $mime);
        }
        if (!in_array(strtolower($this->getMime()), $mime)) {
            $this->error = 'mimetype to upload is not allowed';

            return false;
        }

        return true;
    }

    /**
     * 移动文件
     * @access public
     *
     * @param  string $path 保存路径
     * @param  string|bool $savename 保存的文件名 默认自动生成
     * @param  boolean $replace 同名文件是否覆盖
     * @param  bool $autoAppendExt 自动补充扩展名
     *
     * @return false|File       false-失败 否则返回File实例
     */
    public function move($path, $savename = true, $replace = true, $autoAppendExt = true)
    {
        // 文件上传失败，捕获错误代码
        if (!empty($this->info['error'])) {
            $this->error($this->info['error']);

            return false;
        }
        // 检测合法性
        if (!$this->isValid()) {
            $this->error = 'upload illegal files';

            return false;
        }
        // 验证上传
        if (!$this->check()) {
            return false;
        }
        $path = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        // 文件保存命名规则
        $saveName = $this->buildSaveName($savename, $autoAppendExt);
        $filename = $path . $saveName;
        // 检测目录
        if (false === $this->checkPath(dirname($filename))) {
            return false;
        }
        /* 不覆盖同名文件 */
        if (!$replace && is_file($filename)) {
            $this->error = ['has the same filename: {:filename}', ['filename' => $filename]];

            return false;
        }
        /* 移动文件 */
        if ($this->isTest) {
            rename($this->filename, $filename);
        } elseif (!move_uploaded_file($this->filename, $filename)) {
            $this->error = 'upload write error';

            return false;
        }
        // 返回 File对象实例
        $file = new self($filename);
        $file->setSaveName($saveName);
        $file->setUploadInfo($this->info);

        return $file;
    }

    /**
     * 获取保存文件名
     * @access protected
     *
     * @param  string|bool $savename 保存的文件名 默认自动生成
     * @param  bool $autoAppendExt 自动补充扩展名
     *
     * @return string
     */
    protected function buildSaveName($savename, $autoAppendExt = true)
    {
        if (true === $savename) {
            // 自动生成文件名
            $savename = $this->autoBuildName();
        } elseif ('' === $savename || false === $savename) {
            // 保留原文件名
            $savename = $this->getInfo('name');
        }
        if ($autoAppendExt && false === strpos($savename, '.')) {
            $savename .= '.' . pathinfo($this->getInfo('name'), PATHINFO_EXTENSION);
        }

        return $savename;
    }

    /**
     * 自动生成文件名
     * @access protected
     * @return string
     */
    protected function autoBuildName()
    {
        if ($this->rule instanceof \Closure) {
            $savename = call_user_func_array($this->rule, [$this]);
        } else {
            switch ($this->rule) {
                case 'date':
                    $savename = date('Ymd') . DIRECTORY_SEPARATOR . md5(microtime(true));
                    break;
                default:
                    if (in_array($this->rule, hash_algos())) {
                        $hash     = $this->hash($this->rule);
                        $savename = substr($hash, 0, 2) . DIRECTORY_SEPARATOR . substr($hash, 2);
                    } elseif (is_callable($this->rule)) {
                        $savename = call_user_func($this->rule);
                    } else {
                        $savename = date('Ymd') . DIRECTORY_SEPARATOR . md5(microtime(true));
                    }
            }
        }

        return $savename;
    }

    /**
     * 获取错误代码信息
     * @access private
     *
     * @param  int $errorNo 错误号
     */
    private function error($errorNo)
    {
        switch ($errorNo) {
            case 1:
            case 2:
                $this->error = 'upload File size exceeds the maximum value';
                break;
            case 3:
                $this->error = 'only the portion of file is uploaded';
                break;
            case 4:
                $this->error = 'no file to uploaded';
                break;
            case 6:
                $this->error = 'upload temp dir not found';
                break;
            case 7:
                $this->error = 'file write error';
                break;
            default:
                $this->error = 'unknown upload error';
        }
    }

    /**
     * 获取错误信息（支持多语言）
     * @access public
     * @return string
     */
    public function getError()
    {
        $lang = Container::get('lang');
        if (is_array($this->error)) {
            list($msg, $vars) = $this->error;
        } else {
            $msg  = $this->error;
            $vars = [];
        }

        return $lang->has($msg) ? $lang->get($msg, $vars) : $msg;
    }

    public function __call($method, $args)
    {
        return $this->hash($method);
    }
}
