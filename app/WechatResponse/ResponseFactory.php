<?php
namespace App\WechatResponse;
/**
 * 工厂入口文件，处理不同公众号
 * 具体产品模板参见同目录下的 temp.php
 * @auth season.huang
 */


/**
 * 参数初始化，定义基本消息回复格式
 * Class wechatResponse
 * @package App\WechatResponse
 */
abstract class wechatResponse{
    protected $appId,$appSecret,$requestObj;


    public function __construct($appId,$appSecret,$requestObj){
        $this->appId = $appId;
        $this->appSecret = $appSecret;
        $this->requestObj = $requestObj;
    }

    //----------------------------------------------------回复消息的xml格式化 start--------------------------------------/

    /**
     * 回复文本格式信息
     * @param $msg
     * @return string
     */
    protected final function responseText($msg){
        $xml = '<xml>
<ToUserName><![CDATA[%s]]></ToUserName>
<FromUserName><![CDATA[%s]]></FromUserName>
<CreateTime>%s</CreateTime>
<MsgType><![CDATA[text]]></MsgType>
<Content><![CDATA[%s]]></Content>
</xml>';
        $from_xml = sprintf($xml,$this->requestObj->FromUserName,$this->requestObj->ToUserName,time(),$msg);
        return $from_xml;
    }

    //----------------------------------------------------回复消息的xml格式化 end--------------------------------------/

    /**
     * 接收到到msgType是event类型之后
     * 根据event的类型，分发到不同的方处理
     * 公众号有特殊需求可以在子类中重写
     * @return mixed
     */
    public function onEvent(){
        $event = $this->requestObj->Event;
        $event = ucfirst(strtolower($event));
        $className = 'event'.$event;
        return $this->$className;
    }

    //在下面可以定义必须要实现的类
    //abstract protected function onText();



}


class ResponseFactory{
    //简单单例保存实例化后的对象
    public static $_instance;

    /**
     * 初始工厂类
     * 微信由于不同的公众号有不同处理信息的逻辑
     * 所以用工厂模式简单封装公用方法，并将不同
     * 的公众号处理解耦
     * @param $wechatName
     * @param $appId
     * @param $appSecret
     * @param $requestObj
     * @return mixed
     */
    public static function instance($wechatName,$appId,$appSecret,$requestObj){
        $className = "App\\WechatResponse\\".$wechatName;
        if(empty(self::$_instance)){
            self::$_instance = new $className($appId,$appSecret,$requestObj);
        }
        return self::$_instance;
    }
}