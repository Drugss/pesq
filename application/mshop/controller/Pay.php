<?php
namespace app\mshop\controller;
vendor('wxpay.lib.WxPayApi');
vendor('wxpay.JsApiPay');
class Pay extends Index
{
    private $postDdata;
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $user = session('user_wechat_login');
        $this->assign('user',$user);
    }

    /**
     * 组合统一下单接口数据
     * @return mixed
     */
    public function index(){
        $tools = new \JsApiPay();
        $openId = $tools->GetOpenid();
        $order = cookie('last_order');
        //②、统一下单
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("test");
        $input->SetAttach("test");
        $input->SetOut_trade_no(\WxPayConfig::MCHID.date("YmdHis"));
        $input->SetTotal_fee("1");
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("test");
        $input->SetNotify_url("http://".request()->host().url('mshop/notify/index'));
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);

        //获取共享收货地址js函数参数
        $editAddress = $tools->GetEditAddressParameters();
        $this->assign('jsApiParameters',$jsApiParameters);
        $this->assign('editAddress',$editAddress);
        return $this->fetch();
    }

    /**
     * 创建单一订单
     */
    public function create_order(){
        $data= input('post.');
        $user = get_uerinfo();
        if(!$user){
            $this->result('please login',403,'请您先登录授权');
        }
        $order = [
            'out_trade_on' => orderNo(),
            'openid'    =>  $user['openid'],
            'goods_id' => $data['goods_id'],
            'spec_id'   =>  $data['spec'],
            'dateline' =>time(),
            'fee'   =>  $data['price'],
            'num' => $data['num'],
            'total_fee' => $data['price'] * $data['num'],
            'trade_state' =>0
        ];
        $status = db('shop_order')->insertGetId($order);
        if($status){
            cookie('last_order',$order,1800);
            $this->result($status,200,'订单创建成功');
        }else{
            $this->result('error',500,'订单创建失败！');
        }

    }

    /**
     * 多个订单组合
     */
    public function create_many_order(){
        $data = input('post.');
        $user = get_uerinfo();
        $goods_id = [];
        $spec_id = [];
        $num = [];
        $id=[];
        foreach ($data as $item=>$value){
            if(!isset($value['id'])){
                unset($data[$item]);
            }else{
                $goods_id[] = $value['goods_id'];
                $spec_id[] = $value['spec'];
                $num[] = $value['num'];
                $id[] = $value['id'];
            }
        }
        $price = $this->calator($data);
        $order = [
            'out_trade_on' => orderNo(),
            'openid'    =>  $user['openid'],
            'goods_id' => implode(',',$goods_id),
            'spec_id'   =>  implode(',',$spec_id),
            'dateline' =>time(),
            'fee'   =>  $price,
            'num' => implode(',',$num),
            'total_fee' => $price,
            'trade_state' =>0
        ];
        $status = db('shop_order')->insertGetId($order);
        if($status){
            cookie('last_order',$order,1800);
            db('shop_cart')->where('id','in',implode(',',$id))->delete();// 写入订单表之后删除购物车表
            $this->result($status,200,'订单创建成功,支付有效期30分钟');
        }else{
            $this->result('error',500,'订单创建失败！');
        }
    }
    public function calator_cart(){
        $data = input('get.');
        foreach ($data as $item=>$value){
            if(!isset($value['id'])){
                unset($data[$item]);
            }
        }
        $total_fee = 0;
        foreach ($data as $item=>$value){
            $total_fee  = $total_fee + $value['price']*$value['num'];
        }
        return $total_fee;
    }
    public static function calator($data){
        foreach ($data as $item=>$value){
            if(!isset($value['id'])){
                unset($data[$item]);
            }
        }
        $total_fee = 0;
        foreach ($data as $item=>$value){
            $total_fee  = $total_fee + $value['price']*$value['num'];
        }
        return $total_fee;
    }
    public function notify(){
        echo 'SUCCESS';
    }
}
