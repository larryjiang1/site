<?php
/**
 * Created by PhpStorm.
 * User: dttx
 * Date: 2017/4/21
 * Time: 14:20
 */

namespace Seller\Controller;


use Common\Form\Form;
use Think\Exception;

class LuckdrawController extends InitController
{


    protected function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        if (session('user.shop_type') == 6) {   //如果为个人店的话则执行跳转
            redirect(DM('zhaoshang', '/shopup'));
        }
    }

    /**
     * subject: 可报名列表
     * api: index
     * author: Mercury
     * day: 2017-04-21 15:30
     * [字段名,类型,是否必传,说明]
     */
    public function index()
    {
        $map  = [
            //'start_time' => ['gt', date('Y-m-d H:i:s', NOW_TIME)],
            'status'     => ['in', '1,3'],
        ];
        if (isset($_GET['sid'])) {

        }
        $list = pagelist([
            'table' => 'Luckdraw1View',
            'do'    => 'D',
            'pagesize' => 10,
            'map'   => $map,
            'order' => 'id desc',
            'p'     => I('get.p', 1, 'int')
        ]);
        if (!empty($list['list'])) {
            foreach ($list['list'] as &$v) {
                $v['is_apply']   = M('luckdraw1_apply')->where(['uid' => getUid(), 'luckdraw_id' => $v['id']])->getField('id');
                $v['coupons']    = join(',', M('luckdraw1_coupon_condition')->cache(true)->where(['id' => ['in', $v['coupon_condition']]])->getField('price', true));
            }
            unset($v);
        }
        $this->assign('list', $list);
        C('seo', ['title' => '第' . I('p', 1, 'int') . '页_游戏促销']);
        $this->display();
    }

    /**
     * subject: 已报名的
     * api: lists
     * author: Mercury
     * day: 2017-04-21 15:29
     * [字段名,类型,是否必传,说明]
     */
    public function lists()
    {
        $map  = [
            //'start_time' => ['gt', date('Y-m-d H:i:s', NOW_TIME)],
            'status'     => ['in', '1,2,3'],
            'uid'        => getUid(),
        ];
        if (isset($_GET['sid']) && in_array(I('get.sid'), [1,2,3])) $map['status'] = I('get.sid');
        $list = pagelist([
            'table' => 'Luckdraw1ApplyView',
            'do'    => 'D',
            'pagesize' => 10,
            //'group' =>  'id',
            'map'   => $map,
            'order' => 'id desc',
            'p'     => I('get.p', 1, 'int')
        ]);
        if (!empty($list['list'])) {
            $statusName = [
                '已关闭',
                '待审核',
                '已通过',
                '被拒绝'
            ];
            foreach ($list['list'] as &$v) {
                $v['statusName']= $statusName[$v['status']];
                $v['coupons']   = unserialize($v['coupons']);
            }
            unset($v);
        }
        $this->assign('list', $list);
        C('seo', ['title' => '第' . I('p', 1, 'int') . '页_我的申请']);
        $this->display();
    }

    /**
     * subject:修改
     * api: edit
     * author: Mercury
     * day: 2017-04-21 15:30
     * [字段名,类型,是否必传,说明]
     */
    public function edit()
    {
        $id = I('get.id', 0, 'int');
        if ($id > 0) {
            $data = D('Luckdraw1ApplyView')->where(['uid' => getUid(), 'id' => $id, 'status' => ['in', '1,3']])->find();
            if ($data) {
                $coupons= M('luckdraw1_coupon_condition')->cache(true)->where(['id' => ['in', $data['coupon_condition']]])->getField('id,price', true);
                $config = [
                    'action' => U('/luckdraw/editSave'),
                    'gourl'  => '"' . U('/luckdraw/lists') . '"',
                ];
                $form = Form::getInstance($config)
                    ->hidden(['name' => 'id', 'value' => $id])
                    ->luckdrawSelect(['name' => ['price', 'min_price'], 'title' => ['请选择面额', '需消费金额'], 'options' => $coupons, 'require' => 1, 'value' => unserialize($data['coupons'])])
                    ->submit(['title' => '提交申请'])
                    ->create();
                $this->assign('data', $data);
                $this->assign('form', $form);
                $this->assign('options', $this->getCouponsOption($coupons));
                C('seo', ['title' => '修改申请']);
                $this->display();
            }
        }
    }

    /**
     * subject: 保存修改信息
     * api: editSave
     * author: Mercury
     * day: 2017-04-21 15:30
     * [字段名,类型,是否必传,说明]
     */
    public function editSave()
    {
        if (IS_POST) {
            try {
                $id = I('post.id', 0, 'int');
                if ($id == 0) throw new Exception('非法操作');
                $model = D('Luckdraw1Apply');
                //生成优惠券
                $cData  = I('post.');
                //['coupons'] = join(',', I('post.coupons'));//多个使用逗号隔开,后台生成
                if (empty($cData['price'])) throw new Exception('面额不能为空');
                if (empty($cData['min_price'])) throw new Exception('需消费金额不能为空');
                $tmps = [];
                $tmp  = [];
                foreach ($cData['price'] as $k => $v) {
                    if (!$v) throw new Exception('第' . ($k+1) . '个面额不能为空');
                    $price = M('luckdraw1_coupon_condition')->cache(true)->where(['id' => $v])->getField('price');
                    if (empty($cData['min_price'][$k]) || !is_numeric($cData['min_price'][$k]) || $cData['min_price'][$k] < ($price+2)) throw new Exception('第' . ($k+1) . '个需消费金额不正确');
                    $tmps[$k]['id']         = $v;
                    $tmps[$k]['price']      = $price;
                    $tmps[$k]['min_price']  = $cData['min_price'][$k];
                    $tmp[$k] = md5(join(',', $tmps[$k]));
                }
                if (count(array_unique($tmp)) != $k+1) throw new Exception('不可设相同优惠信息');
                if ($k+1 > C('cfg.luckdraw')['luckdraw_create_coupon_num']) throw new Exception('最多选三张面额');
                $data['id']      = $id;
                $data['coupons'] = serialize($tmps);
                $data['status']  = 1;
                C('TOKEN_ON', false);
                if ($model->create($data) == false) throw new Exception($model->getError());
                if ($model->where(['id' => $id, 'uid' => getUid()])->save() == false) throw new Exception('修改申请失败');
                $this->ajaxReturn(['code' => 1, 'msg' => '提交成功']);
            } catch (Exception $e) {
                $this->ajaxReturn(['code' => 0, 'msg' => $e->getMessage()]);
            }
        }
    }

    /**
     * subject: 申请
     * api: apply
     * author: Mercury
     * day: 2017-04-21 14:30
     * [字段名,类型,是否必传,说明]
     */
    public function apply()
    {
        $id = I('get.id', 0, 'int');
        if ($id > 0) {
            $data = D('Luckdraw1View')->cache(true)->where(['id' => $id, 'status' => ['in', '1,3']])->find();
            if ($data) {
                $coupons= M('luckdraw1_coupon_condition')->cache(true)->where(['id' => ['in', $data['coupon_condition']]])->getField('id,price', true);
                $config = [
                    'action' => U('/luckdraw/applySave'),
                    'gourl'  => '"' . U('/luckdraw/lists') . '"',
                ];
                $form = Form::getInstance($config)
                    ->hidden(['name' => 'luckdraw_id', 'value' => $id])
                    ->luckdrawSelect(['name' => ['price', 'min_price'], 'title' => ['请选择面额', '需消费金额'], 'options' => $coupons, 'require' => 1])
                    ->submit(['title' => '提交申请'])
                    ->create();
                $this->assign('data', $data);
                $this->assign('form', $form);
                $this->assign('options', $this->getCouponsOption($coupons));
                C('seo', ['title' => '申请']);
                $this->display();
            } else {
                $this->display(T('Home@Empty:404'));
            }
        } else {
            $this->display(T('Home@Empty:404'));
        }
    }

    /**
     * subject: 保存申请信息
     * api: applySave
     * author: Mercury
     * day: 2017-04-21 14:33
     * [字段名,类型,是否必传,说明]
     */
    public function applySave()
    {
        if (IS_POST) {
            try {
                $data['luckdraw_id'] = I('post.luckdraw_id', 0, 'int');
                $cData  = I('post.');
                if ($data['luckdraw_id'] == 0) throw new Exception('非法操作');
                $model = D('Luckdraw1Apply');
                //生成优惠券
                $data['shop_id'] = getShopId();
                $data['uid']     = getUid();
                //['coupons'] = join(',', I('post.coupons'));//多个使用逗号隔开,后台生成
                if (empty($cData['price'])) throw new Exception('面额不能为空');
                if (empty($cData['min_price'])) throw new Exception('需消费金额不能为空');
                $tmps = [];
                $tmp  = [];
                foreach ($cData['price'] as $k => $v) {
                    if (!$v) throw new Exception('第' . ($k+1) . '个面额不能为空');
                    $price = M('luckdraw1_coupon_condition')->cache(true)->where(['id' => $v])->getField('price');
                    if (empty($cData['min_price'][$k]) || !is_numeric($cData['min_price'][$k]) || $cData['min_price'][$k] < ($price+2)) throw new Exception('第' . ($k+1) . '个需消费金额不正确');
                    $tmps[$k]['id']         = $v;
                    $tmps[$k]['price']      = $price;
                    $tmps[$k]['min_price']  = $cData['min_price'][$k];
                    $tmp[$k] = md5(join(',', $tmps[$k]));
                }
                if (count(array_unique($tmp)) != $k+1) throw new Exception('不可设相同优惠信息');
                if ($k+1 > C('cfg.luckdraw')['luckdraw_create_coupon_num']) throw new Exception('最多选三张面额');
                $data['coupons'] = serialize($tmps);
                C('TOKEN_ON', false);
                if ($model->create($data) == false) {
                    throw new Exception($model->getError());
                }
                if ($model->add() == false) throw new Exception('提交申请失败');
                $this->ajaxReturn(['code' => 1, 'msg' => '提交成功']);
            } catch (Exception $e) {
                $this->ajaxReturn(['code' => 0, 'msg' => $e->getMessage()]);
            }
        }
    }

    /**
     * subject: 生成option
     * api: getCouponsOption
     * author: Mercury
     * day: 2017-05-13 15:23
     * [字段名,类型,是否必传,说明]
     * @param $data
     * @return string
     */
    private function getCouponsOption($data) {
        $h = '';
        foreach ($data as $k => $v) {
            $h .= '<option data-price="'.$v.'" value="'.$k.'">面额 '.$v.' 元</option>';
        }
        return $h;
    }
}