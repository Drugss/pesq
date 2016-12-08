<?php
namespace app\user\controller;

use app\common\model\Channel;
class Posts extends Common
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $channel = Channel::data_formcat(1);
        $this->assign('channel', $channel);
    }

    public function index()
    {
        return $this->fetch();
    }

    public function posts_article()
    {
        $data = input('post.');
        $validate = $this->validate(
            $data,
            [
                'subject' => 'require|max:125|token',
                'message' => 'require'
            ]
        );
        if (true !== $validate) {
            $this->error('标题不能为空或是超过120个字,或是内容不能为空');
        }

        $data['uid'] = uid('user');
        unset($data['__token__']);
        $result = \app\common\model\Posts::article($data);
        $this->result('success', $result['code'], $result['msg']);
    }
}

