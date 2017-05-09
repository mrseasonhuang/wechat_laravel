<?php
namespace App\Http\Service;
/**
 * 微信model:处理微信主动调用服务器的请求
 * 统一返回控制器Model处理结果的格式为：['code'=>xx,'msg'=>'xxx','data'=>'xxx']
 * @auth season.huang
 */

use App\Libs\wechatEAD\WXBizMsgCrypt;
use Illuminate\Support\Facades\Log;
use App\WechatResponse\ResponseFactory;

class WechatMsgService{
    const CHECK_SIGNATURE_FAILED = 4001; //验证签名失败
    const XML_OR_SIGN_LOST = 4002; //缺少参数
    const DECRYPT_MSG_FAILED = 4003; //解密失败
    const XML_TO_OBJ_FAILED = 4004; //xml信息未解析成对象
    const VERIFY_PHASE = 2001; //处于微信服务器认证阶段
    const MSG_PHASE = 2002; //处于消息交互阶段
    const DEAL_XML_SUCCESSFULLY = 3001; //xml成功处理并返回


    private $token,$encodingAESKey,$appId,$appSecret,$nonce,$wechatName;

    private $pc,$responseClass;

    private $requestObj;

    //初始化参数
    public function __construct($initArr){
        $this->token = $initArr['token'];
        $this->encodingAESKey = $initArr['encodingAESKey'];
        $this->appId = $initArr['appId'];
        $this->appSecret = $initArr['appSecret'];
        $this->wechatName = $initArr['wechatName'];
        $this->pc = new WXBizMsgCrypt($this->token, $this->encodingAESKey, $this->appId);
    }

    /**
     * 最主要的请求响应检测类
     * 1.请求签名验证
     * 2.检测当前是服务器认证阶段还是信息交互阶段，
     * 根据不同阶段返回不同信息
     * 3.信息AES解密检验
     * @param $request
     * @return array
     */
    public function checkRequest($request){
        $timestamp = $request['timestamp'] ?? '';
        $nonce = $request['nonce'] ?? '';
        $this->nonce = $nonce;
        $signature = $request['signature'] ?? '';

        //1.请求签名验证
        if(!$this->checkSignature($timestamp,$nonce,$signature)){
            return ['code'=>self::CHECK_SIGNATURE_FAILED,'msg'=>'验证签名失败','data'=>''];
        }

        //2.检测当前是服务器认证阶段还是信息交互阶段，根据不同阶段返回不同信息
        if(isset($request['echostr']) && !empty($request['echostr'])){
            return ['code'=>self::VERIFY_PHASE,'msg'=>'微信服务器认证echostr','data'=>$request['echostr']];
        }
        $xml = $request['xml'] ?? '';
        $msgSign = $request['msg_signature'] ?? '';
        $msg = '';
        if(empty($xml) || empty($msgSign)){
            return ['code'=>self::XML_OR_SIGN_LOST,'msg'=>'缺少参数','data'=>''];
        }

        //3.信息AES解密检验
        $errCode = $this->pc->decryptMsg($msgSign, $timestamp, $nonce, $xml, $msg);
        if($errCode != 0){
            return ['code'=>self::DECRYPT_MSG_FAILED,'msg'=>'失败码:'.$errCode,'data'=>''];
        }
        //记录解密后的信息，方便bug定位
        Log::info($msg);
        return ['code'=>self::MSG_PHASE,'msg'=>''.$errCode,'data'=>['xml'=>$msg]];

    }

    /**
     * 处理XML成对象，并保存
     * @param $xml
     */
    public function saveRequest($xml){
        $xmlObj = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $this->requestObj = $xmlObj;
    }

    /**
     * 1.依据msgType，x响应用户行为
     * 统一用使用on$className 如msgType为text
     * 则内部处理方法交由相应工厂类中的onText()处理
     * 2.处理后需要通知用户的信息AES加密，返还给微信服务器
     * @return array
     */
    public function response(){
        if(empty($this->requestObj) && !is_object($this->requestObj)){
            return ['code'=>self::XML_TO_OBJ_FAILED,'msg'=>'xml信息未解析成对象','data'=>''];
        }
        $this->responseClass = ResponseFactory::instance($this->wechatName,$this->appId,$this->appSecret,$this->requestObj);
        $msgType = $this->requestObj->MsgType;
        $msgType = ucfirst(strtolower($msgType));
        $className = 'on'.$msgType;
        $xml = $this->responseClass->$className();
        $encryptXml = '';
        $this->pc->encryptMsg($xml,time(),$this->nonce,$encryptXml);
        return ['code'=>self::DEAL_XML_SUCCESSFULLY,'msg'=>'','data'=>['xml'=>$encryptXml]];
    }


    /**
     * 签名认证
     * @param $timestamp
     * @param $nonce
     * @param $signature
     * @return bool
     */
    private function checkSignature($timestamp,$nonce,$signature){

        if(empty($timestamp) || empty($nonce) || empty($signature)){
            return false;
        }

        $signatureArray = array($this->token, $timestamp, $nonce);
        sort($signatureArray, SORT_STRING);

        return (sha1(implode($signatureArray)) == $signature);
    }



}