<?php
namespace App\WechatResponse;

class wechatXt extends wechatResponse{

    //----------------------------------------------msgType处理方法 start----------------------------------------------//
    public function onText(){
        $msg = $this->requestObj->Content;
        if($msg == '卧槽'){
            $msg = '你擦什么！？';
        }
        return $this->responseText($msg);
    }


    //----------------------------------------------event处理方法 start----------------------------------------------//

    private function eventClick(){
        $clickKey = $this->requestObj->EventKey;
        if($clickKey == 'hello'){
            return $this->responseText('哇！你点击了我~你好啊~');
        }else{
            return $this->responseText('哇！你点击了我~~'.$clickKey);
        }
    }

    private function eventSubscribe(){
        return $this->responseText('哇！谢谢你关注我~');
    }
}