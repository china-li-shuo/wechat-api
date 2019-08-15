<?php
/**
 * Create by: PhpStorm.
 * Author: 李硕
 * 微信公众号：空城旧梦狂啸当歌
 * Date: 2019/8/15
 * Time: 8:57
 */


namespace app\article\service;


class Article
{
    /**
     * 处理数据
     * @param $data
     * @return mixed
     */
    public function handleData($data)
    {
        $data['cnj']['judgment_question'] = json_decode( $data['judgment_question'] ,true);
        unset($data['judgment_question']);
        $data['words'] = json_decode( $data['words'] ,true);
        $data['phrase'] = json_decode( $data['phrase'] ,true);
        $data['cnj']['word_parsing'] = json_decode( $data['cnj']['word_parsing'] ,true);
        $data['cnj']['sentence_splitting'] = json_decode( $data['cnj']['sentence_splitting'] ,true);
        foreach ($data['words'] as &$val) {
            $val['us_audio'] = config('setting.audio_prefix') . $val['us_audio'];
            $val['chinese_word'] = explode('@', $val['chinese_word']);
        }
        foreach ($data['phrase'] as &$val) {
            $val['us_audio'] = config('setting.audio_prefix') . $val['us_audio'];
            $val['chinese_word'] = explode('@', $val['chinese_word']);
        }
        foreach ($data['cnj']['word_parsing'] as &$val) {
            $val['us_audio'] = config('setting.audio_prefix') . $val['us_audio'];
        }

        $data['new_words'] = $this->getNewData($data['words']);
        $data['new_phrase'] = $this->getNewData($data['phrase']);
        return $data;
    }

    private function getNewData($data)
    {
        $sjz = 97;
        foreach ($data as $key=>$val){
            $answer[$sjz] = $val['answer'];
            $word[chr($sjz)] = $val['english_word'];
            $sjz++;
        }

        foreach( $answer as $key => $value ) {
            $arr[$key] = $value;
        }

        foreach( $word as $key => $value ) {
            $arr[$key] = $value;
        }

        $arr = $this->shuffle_assoc($arr);
        $new_arr = [];
        foreach ($arr as $key=>$val){
            array_push($new_arr,[$key=>$val]);
        }
        return $new_arr;
    }

    /**
     * 打乱数组,保持键值对关系
     * @param array  $array
     * @return array
     */
    private function shuffle_assoc($array)
    {
        if (!is_array($array)||empty($array)) return $array;

        $keys = array_keys($array);
        shuffle($keys);
        $random = array();
        foreach ($keys as $key){
                $random[$key] = $array[$key];
            }
        return $random;
     }
}