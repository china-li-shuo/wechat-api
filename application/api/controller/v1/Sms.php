<?php
/**
 * Created by PhpStorm.
 * User: 李硕
 * Date: 2019/3/1
 * Time: 17:13
 */

namespace app\api\controller\v1;

use app\api\model\User;
use app\lib\exception\ParameterException;
use app\lib\exception\SmsException;
use app\lib\exception\TokenException;
use think\Db;
use think\facade\Cache;
use think\facade\Request;
use think\src\sendWithParam;
use think\src\SmsSingleSender;

class Sms
{
    /**
     * 發送短信驗證碼
     * @param string $mobie
     * @return string
     * @throws ParameterException
     */
    public function sendSms($mobile = '')
    {
        if(!$mobile){
            throw new ParameterException([
                'mobie不允许为空'
            ]);
        }

        $res=Db::name('user')->field('mobile')->where('mobile',$mobile)->find();

        if (empty($res)){

            $vcode=rand(1000, 9999);
            $this->setRegSmsCache(['mobile'=>$mobile,'vcode'=>$vcode,'times'=>time()]);

            $new_sms = new SmsSingleSender(config('sms.app_id'),config('sms.app_key'));
            $params = [
                "$vcode",
                "10"
            ];

            $sign = "研线课堂";
            $res = $new_sms->sendWithParam(86, $mobile, config('sms.tpl_id'), $params,$sign = "", $extend = "", $ext = "",$vcode);
            echo $res;
        }else{
            return json_encode(array('sta'=>1001,'message'=>"该手机号已验证过"),JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * 綁定手機號
     * @param string $mobile
     * @param string $code
     * @throws ParameterException
     */
    public function bindMobile($mobile = '', $code = '')
    {
        if(!$mobile || !$code){
            throw new ParameterException([
                '你输入的参数缺失'
            ]);
        }
        $res = $this->checkRegSms($mobile,$code);

        if(!$res){
            throw new SmsException();
        }
        //获取token令牌信息，与手机号进行绑定
        $token = Request::instance()
            ->header('token');

        if(!$token){
            throw new ParameterException([
                '你输入的token参数缺失'
            ]);
        }

        $identities = Cache::get($token);

        if(!$identities){
            throw new TokenException();
        }

        $res = User::bindMobile(json_decode($identities,true),$mobile);

        if(!$res){
            return json_encode(['msg'=>'绑定失败','error_code'=>6001],JSON_UNESCAPED_UNICODE);
        }
        return json_encode(['msg'=>'绑定成功','code'=>200],JSON_UNESCAPED_UNICODE);
    }

    /**
     * 检测手机短信验证码
     * @param $mobile
     * @param bool|false $code
     * @return bool
     */
    protected function checkRegSms($mobile, $code = false)
    {

        if (!$mobile) return false;
        if ($code === false) {   //判断60秒以内是否重复发送
            if (!Cache::has('sms_' . $mobile)) return true;
            if (Cache::get('sms_' . $mobile)['times'] > time()) {
                return false;
            } else {
                return true;
            }
        } else {  //判断验证码是否输入正确
            if (!Cache::has('sms_' . $mobile)) return false;
            if (Cache::get('sms_' . $mobile)['vcode'] == $code) {
                return true;
            } else {
                return false;
            }
        }
    }

    /**
     * 设置手机短息验证码缓存
     */
    protected function setRegSmsCache($data_cache)
    {
        Cache::set('sms_' . $data_cache['mobile'], $data_cache, 600);
    }
}