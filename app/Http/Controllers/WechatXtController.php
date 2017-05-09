<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Http\Service\WechatMsgService;

class WechatXtController extends Controller{

    //微信后台配置的token
    private $token = 'yourToken';
    //标志微信公众号的id
    private $wechatId = 1;
    //微信名，后面工厂需要
    private $wechatName = 'wechatXt';
    //消息加密EncodingAESKey
    private $encodingAESKey = 'yourAesKey';
    //微信的appid
    private $appId = 'yourAppId';
    //微信提供的appkey
    private $appSecret = 'yourAppKey';
    //service
    private $service;

    public function __construct(Request $request){
        //记录访问请求的日志
        $method = $request->method();
        $url = $request->fullUrl();
        $params = http_build_query($request->all());
        $message = file_get_contents('php://input');
        Log::info("[$method] $url $message");

        //初始化model
        $initArr = array(
            'token'=>$this->token,
            'wechatId'=>$this->wechatId,
            'encodingAESKey'=>$this->encodingAESKey,
            'appId'=>$this->appId,
            'appSecret'=>$this->appSecret,
            'wechatName'=>$this->wechatName
        );
        $this->service = new WechatMsgService($initArr);
    }

    public function index(Request $request){
        $params = $request->all();
        $params['xml'] = file_get_contents('php://input');
        $checkRes = $this->service->checkRequest($params);
        if($checkRes['code'] > 4000){
            exit(json_encode($checkRes));
        }
        if($checkRes['code'] == 2001){
            exit($params['echostr']);
        }
        if($checkRes['code'] == 2002){
            $this->service->saveRequest($checkRes['data']['xml']);
            $responseRes = $this->service->response();
            if($responseRes['code'] == 3001){
                exit($responseRes['data']['xml']);
            }
        }
    }

    public function createMenu(){
        $user = \App\Models\WechatUser::all();
        print_r($user);
    }

}