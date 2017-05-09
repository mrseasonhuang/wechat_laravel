<?php
namespace App\WechatResponse;
/**
 * 这是一个书写消息响应的模板，里面枚举了所有的信息类型和事件类型
 * 可以根据该公众号的特征选择加入响应逻辑
 *
 */


class temp extends wechatResponse{
    //----------------------------------------------msgType处理方法 start----------------------------------------------//

    public function onText(){

    }


    public function onEvent() {
        $event = $this->requestObj->Event;
        $event = ucfirst(strtolower($event));
        $className = 'event'.$event;
        $xml = $this->$className();

    }

    public function onImage(){

    }

    public function onVoice(){

    }


    public function onVideo(){

    }

    public function onShortvideo(){

    }

    public function onLocation(){

    }


    public function onLink(){

    }


    //----------------------------------------------event处理方法 start----------------------------------------------//

    private function eventSubscribe(){

    }


    private function eventScan(){

    }


    private function eventLocation(){

    }

    private function eventClick(){

    }

    private function eventView(){

    }
}