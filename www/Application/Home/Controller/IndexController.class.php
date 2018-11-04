<?php
// +----------------------------------------------------------------------
// | OneThink [ WE CAN DO IT JUST THINK IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2013 http://www.onethink.cn All rights reserved.
// +----------------------------------------------------------------------
// | Author: 麦当苗儿 <zuojiazi@vip.qq.com> <http://www.zjzit.cn>
// +----------------------------------------------------------------------

namespace Home\Controller;
use Home\Logic\Snoopy;

/**
 * 前台首页控制器
 * 主要获取首页聚合数据
 */
class IndexController extends HomeController {

	//系统首页
    public function index(){
    	if(IS_CLI){
            $data = M('Content')->field("id,content")->select();
            foreach ($data as $value) {
                $value['content'] = ubb($value['content']);
                M('Content')->save($value);
            }

        } else {
            $category = D('Category')->getTree();
            $lists    = D('Document')->lists(null);

            $this->assign('category',$category);//栏目
            $this->assign('lists',$lists);//列表
            $this->assign('page',D('Document')->page);//分页

            $this->display();
        }
    }

    public function upload(){
    	if(IS_POST){
            //又拍云
            // $config = array(
            //     'host'     => 'http://v0.api.upyun.com', //又拍云服务器
            //     'username' => 'zuojiazi', //又拍云用户
            //     'password' => 'thinkphp2013', //又拍云密码
            //     'bucket'   => 'thinkphp-static', //空间名称
            // );
            // $upload = new \COM\Upload(array('rootPath' => 'image/'), 'Upyun', $config);
            //百度云存储
            $config = array(
                'AccessKey'  =>'3321f2709bffb9b7af32982b1bb3179f',
                'SecretKey'  =>'67485cd6f033ffaa0c4872c9936f8207',
                'bucket'     =>'test-upload',
                'size'      =>'104857600'
            );
    		$upload = new \COM\Upload(array('rootPath' => './Uploads/bcs'), 'Bcs', $config);
    		$info   = $upload->upload($_FILES);
    	} else {
    		$this->display();
    	}
    }

    public function upyun(){
        $policydoc = array(
            "bucket"             => "thinkphp-static", /// 空间名
            "expiration"         => NOW_TIME + 600, /// 该次授权过期时间
            "save-key"            => "/{year}/{mon}/{random}{.suffix}",
            "allow-file-type"      => "jpg,jpeg,gif,png", /// 仅允许上传图片
            "content-length-range" => "0,102400", /// 文件在 100K 以下
        );

        $policy = base64_encode(json_encode($policydoc));
        $sign = md5($policy.'&'.'56YE3Ne//xc+JQLEAlhQvLjLALM=');

        $this->assign('policy', $policy);
        $this->assign('sign', $sign);
        $this->display();
    }

    public function test(){
        $table = new \OT\DataDictionary;
        echo "<pre>".PHP_EOL;
        $out = $table->generateAll();
        echo "</pre>";
        // print_r($out);
    }

    public function fetch(){
//        $snoopy = new Snoopy;
//        $url = "http://www.17k.com/chapter/2892845/35901430.html";
//        $snoopy->fetchlinks($url); //获取所有内容   fetch
//        $contents =  $snoopy->results; //显示结果
//        echo $contents;


        $snoopy=new Snoopy();
        //登陆论坛
        $submit_url = "http://www.phpchina.com/bbs/logging.php?action=login";
        $submit_vars["loginmode"] = "normal";
        $submit_vars["styleid"] = "1";
        $submit_vars["cookietime"] = "315360000";
        $submit_vars["loginfield"] = "username";
        $submit_vars["username"] = "yankyle"; //你的用户名
        $submit_vars["password"] = "2519885kai"; //你的密码
        $submit_vars["questionid"] = "0";
        $submit_vars["answer"] = "";
        $submit_vars["loginsubmit"] = "提 交";
        $snoopy->submit($submit_url,$submit_vars);
        if ($snoopy->results){
            //获取连接地址
            $snoopy->fetchlinks("http://www.phpchina.com/bbs");
            $url=array();
            $url=$snoopy->results;
            //print_r($url);
            foreach ($url as $key=>$value)  {
                //匹配http://www.phpchina.com/bbs/forumdisplay.php?fid=156&sid=VfcqTR地址即论坛板块地址
                if (!preg_match("/^(http:\/\/www\.phpchina\.com\/bbs\/forumdisplay\.php\?fid=)[0-9]*&sid=[a-zA-Z]{6}/i",$value)){
                    unset($url[$key]);
                }
            }
            //print_r($url);
            //获取到板块数组$url，循环访问，此处获取第一个模块第一页的数据
            $i=0;
            foreach ($url as $key=>$value)  {
                if ($i>=1){
                    //测试限制
                    break;
                }else{
                    //访问该模块，提取帖子的连接地址，正式访问里需要提取帖子分页的数据，然后根据分页数据提取帖子数据
                    $snoopy=new Snoopy();
                    $snoopy->fetchlinks($value);
                    $tie=array();
                    $tie[$i]=$snoopy->results;
                    //print_r($tie);
                    //转换数组
                    foreach ($tie[$i] as $key=>$value)    {
                        //匹配http://www.phpchina.com/bbs/viewthread.php?tid=68127&extra=page%3D1&page=1&sid=iBLZfK
                        if (!preg_match("/^(http:\/\/www\.phpchina\.com\/bbs\/viewthread\.php\?tid=)[0-9]*&extra=page\%3D1&page=[0-9]*&sid=[a-zA-Z]{6}/i",$value))    {
                            unset($tie[$i][$key]);
                        }
                    }
                    //print_r($tie[$i]);
                    //归类数组，将同一个帖子不同页面的内容放一个数组里
                    $left='';//连接左边公用地址
                    $j=0;
                    $page=array();
                    foreach ($tie[$i] as $key=>$value)    {
                        $left=substr($value,0,52);
                        $m=0;
                        foreach ($tie[$i] as $pkey=>$pvalue)    {
                            //重组数组
                            if (substr($pvalue,0,52)==$left)    {
                                $page[$j][$m]=$pvalue;
                                $m++;
                            }
                        }
                        $j++;
                    }
                    //去除重复项开始
                    //$page=array_unique($page);只能用于一维数组
                    $paget[0]=$page[0];
                    $nums=count($page);
                    for ($n=1;$n<$nums;$n++)   {
                        $paget[$n]=array_diff($page[$n],$page[$n-1]);
                    }
                    //去除多维数组重复值结束
                    //去除数组空值
                    unset($page);
                    $page=array();//重新定义page数组
                    $page=array_filter($paget);
                    //print_r($page);
                    $u=0;
                    $title=array();
                    $content=array();
                    $temp='';
                    $tt=array();
                    foreach ($page as $key=>$value)   {
                        //外围循环，针对一个帖子
                        if (is_array($value))    {
                            foreach ($value as $k1=>$v1)    {
                                //页内循环，针对一个帖子的N页
                                $snoopy=new Snoopy();
                                $snoopy->fetch($v1);
                                $temp=$snoopy->results;
                                //读取标题
                                if (!preg_match_all("/<h2>(.*)<\/h2>/i",$temp,$tt))    {
                                    echo "no title";
                                    exit;
                                }    else    {
                                    $title[$u]=$tt[1][1];
                                }
                                unset($tt);
                                //读取内容
                                if (!preg_match_all("/<div id=\"postmessage_[0-9]{1,8}\" class=\"t_msgfont\">(.*)<\/div>/i",$temp,$tt))    {
                                    print_r($tt);
                                    echo "no content1";
                                    exit;
                                }    else   {
                                    foreach ($tt[1] as $c=>$c2)    {
                                        $content[$u].=$c2;
                                    }
                                }
                            }
                        } else {
                            //直接取页内容
                            $snoopy=new Snoopy();
                            $snoopy->fetch($value);
                            $temp=$snoopy->results;
                            //读取标题
                            if (!preg_match_all("/<h2>(.*)<\/h2>/i",$temp,$tt))   {
                                echo "no title";
                                exit;
                            } else  {
                                $title[$u]=$tt[1][1];
                            }
                            unset($tt);
                            //读取内容
                            if (!preg_match_all("/<div id=\"postmessage_[0-9]*\" class=\"t_msgfont\">(.*)<\/div>/i",$temp,$tt)){
                                echo "no content2";
                                exit;
                            } else    {
                                foreach ($tt[1] as $c=>$c2)    {
                                    $content[$u].=$c2;
                                }
                            }
                        }
                        $u++;
                    }
                    print_r($content);
                }
                $i++;
            }
        } else  {
            echo "login failed";
            exit;
        }
    }

}
