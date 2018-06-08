<?php
/**
* 卖家对已发货未收货退货退款处理
*/
namespace Common\Controller;
use Common\Builder\Activity;
class SellerRefund2Controller extends SellerOrdersController {
    private $action_logs      =array('accept','reject','accept2');   //须要记录日志的方法
    /**
    * 同意退款
    * @param string $data['s_no'] 退款单号
    * @param int    $is_sys 系统操作
    * @param int    $check_type 订单检验类型，1为卖家，2为买家，0不检验    
    */
    public function accept($data,$check_type=1,$is_sys=0){
        //检查订单状态
        $res=$this->check_s_orders($check_type);
        if($res['code']!=1) return $res;

        //已付款未发货订单才可执行此操作！
        if($res['data']['status']!=3) return $this->apiReturn(1010);

        $rs=M('refund')
            ->where([
                'r_no'          => $data['r_no'],
                's_id'          => $res['data']['id'],
                'orders_status' => $res['data']['status']
                ])
            ->field('id,r_no,status,type,money,score,orders_goods_id,num,activity_money,s_no,refund_express')->find();

        if(!$rs) return $this->apiReturn(3);    //找不到记录

        //退款订单已失效！
        if(in_array($rs['status'],[20,100,4,5,11]))    return $this->apiReturn(1005);
        /*if ($rs['money'] - $rs['activity_money'] <= 0) {
            $msg    =   '当前不能同意退款' . number_format($rs['money'] - $rs['activity_money'], 2) . '元';
            return $this->apiReturn(0, '', $msg);
        }*/
        if($rs['type']==1){ //退货退款
            //请选择退货地址以方便买家寄回商品
            //if(empty($data['address_id'])) return $this->apiReturn(1016);

            return $this->_accept1($rs,$res,$is_sys,$data['address_id'], $data['reason']);
        }else{  //只退款
            return $this->_accept($rs,$res,$is_sys, $data['reason']);
        }
    }

    /**
    * 同意 只退款
    */
    public function _accept($rs,$res,$is_sys, $reasons = null){
        $buyer  =M('user')->where(['id' => $res['data']['uid']])->field('nick,erp_uid')->find();
        $seller =M('user')->where(['id' => $res['data']['seller_id']])->field('nick,erp_uid')->find();

        //1=不退积分退款，2=需要退积分的退款
        $refund_type = $rs['score'] > 0? 2:1;
        //$refund_type = $rs['type'] == 3? 1:$refund_type;
        $inventory_type =   $res['data']['inventory_type'] == 0 ? 2 : $res['data']['inventory_type'];
        $post_data  =   [
            'money'             =>$rs['money'],
            'r_no'              =>$rs['r_no'],
            'score'             =>$rs['score'],
            'buyer_uid'         =>$buyer['erp_uid'],
            'buyer_nick'        =>$buyer['nick'],
            'seller_uid'        =>$seller['erp_uid'],
            'seller_nick'       =>$seller['nick'],
            's_no'              =>$res['data']['s_no'],
            'pay_type'          =>$res['data']['pay_type'],
            'inventory_type'    =>$inventory_type,
            'refundType'        =>$refund_type   
        ];
        //$express_money          =    M('orders_shop')->where(['s_no' => $rs['s_no'], 'refund_express' => ['eq',0]])->getField('express_price_edit');
        if ($rs['refund_express'] > 0) {
            $post_data['money']   +=    $rs['refund_express'];   //有退运费，则相加
        }
        
        //如果当前退款为最后一步的话，则把剩余运费退还给买家
        /*if ($res['data']['goods_price_edit'] - (round($rs['money'] + $res['data']['refund_price'], 2)) == 0) {
            //查找是否还有其他正在退数量或者退运费的订单
            $otherRefunds = M('refund')->where(['s_no' => $res['data']['s_no'], 'r_no' => ['neq' => $rs['r_no']], 'status' => ['notin', '20,100']])->field('id,s_no,r_no,type')->select();
            if ($otherRefunds) {
                $otherRefundsFlag = true;
                $model = M();
                $model->startTrans();
                $reason = '您正在退最后一笔款，商家将把未同意退的商品数量或运费直接给您退了,所以关闭了当前退款单号。';
                $dotime = date('Y-m-d H:i:s', NOW_TIME);
                foreach ($otherRefunds as $k => $v) {
                    //日志数据
                    $closeRefund[$k] = M('refund')->where(['id' => $v['id']])->save(['status' => 20, 'dotime' => $dotime, 'cancel_time' => $dotime]);
                    if ($closeRefund[$k] == false) {
                        $otherRefundsFlag = false;
                        break;
                    }
                    $vlogs[$k]=[
                        'r_id'          =>$v['id'],
                        'r_no'          =>$v['r_no'],
                        'uid'           =>$this->seller_id,
                        'status'        =>100,
                        'type'          =>$v['type'],
                        'remark'        =>$reason, //卖家同意退款
                        'is_sys'        =>0,
                    ];
                    if(M('refund_logs')->add($vlogs[$k]) == false) {
                        $otherRefundsFlag = false;
                        break;
                    }
                }
                if ($otherRefundsFlag == false) {
                    $model->rollback();
                    return $this->apiReturn(0,'','操作其他未退运费或数量退款单失败');
                }
                $model->commit();
            }
            $lastExpressMoney = $res['data']['express_price_edit'] - (round($rs['refund_express'] + $res['data']['refund_express'], 2));
            $reason .= '<p>外加剩余运费 <span class="strong text_red">'.$lastExpressMoney.'</span> 元</p>';
            $rs['refund_express'] += $lastExpressMoney;    //运费+上最后剩余运费
            $post_data['money'] += $lastExpressMoney;
        }*/
        
        $mallDeal   =   true;
        if ($post_data['money'] > 0) {
            $erp_res=A('Erp')->_refund($post_data);
            if ($erp_res->code != 1) $mallDeal = false;
        }
        if($mallDeal){

            $do=M();
            $do->startTrans();
            //订单参与活动
            //$reason = '<p class="strong text_red">卖家同意退款</p>';
            //if (!is_null($reasons)) $reason .= '<p>'.$reasons.'</p>';
            //$reason .= C('error_code.1007');
            //goto error;

            $reason = $reasons ? $reasons :'卖家同意退款';

            if ($rs['money'] > 0) Activity::refundLessMoney($this->s_no, $res['data']['uid'], $rs['money']);  //退款后减金额，主要针对累积升级
            if(!$this->sw[]=M('refund')->where(['r_no' => $rs['r_no']])->save(['status' => 100,'dotime' => date('Y-m-d H:i:s'),'accept_time' => date('Y-m-d H:i:s'), 'refund_express' => $rs['refund_express']])) goto error;

            //if($rs['orders_goods_id']>0){   //退商品
            if ($rs['refund_express'] > 0) {
                if(!$this->sw[]=$do->execute('update '.C('DB_PREFIX').'orders_shop set refund_num=refund_num+'.$rs['num'].',refund_price=refund_price+'.$rs['money'].',refund_score=refund_score+'.$post_data['score'].',money=money-'.$post_data['money'].',refund_express=refund_express+'.$rs['refund_express'].' where s_no="'.$this->s_no.'"')) goto error;
            } else {
                if(!$this->sw[]=$do->execute('update '.C('DB_PREFIX').'orders_shop set refund_num=refund_num+'.$rs['num'].',refund_price=refund_price+'.$rs['money'].',refund_score=refund_score+'.$rs['score'].',money=money-'.$rs['money'].' where s_no="'.$this->s_no.'"')) goto error;
            }
            if ($rs['money'] > 0 || $rs['num'] > 0) {
                if(!$this->sw[]=$do->execute('update '.C('DB_PREFIX').'orders_goods set refund_num=refund_num+'.$rs['num'].',refund_price=refund_price+'.$rs['money'].',refund_score=refund_score+'.$rs['score'].' where id='.$rs['orders_goods_id'])) goto error;
            }

            //}else{  //退运费
              //  if(!$this->sw[]=$do->execute('update '.C('DB_PREFIX').'orders_shop set refund_express=refund_express+'.$rs['money'].',refund_score=refund_score+'.$rs['score'].',money=money-'.$rs['money'].' where s_no='.$this->s_no)) goto error;
            //}

            //如果订单中全部款退过完即关闭订单
            if(round($res['data']['money'], 2)-round($post_data['money']+$res['data']['daigou_cost'], 2) ==0){
                //Activity::refundSetStatus($this->s_no, $res['data']['uid']);        //关闭参与的活动，针对累积升级
                Activity::setStatus($this->s_no, $res['data']['uid'], 2);           //关闭其他活动
                if(!$this->sw[]=M('orders_shop')->where(['s_no' => $this->s_no])->save(['status' => 11,'close_time' => date('Y-m-d H:i:s')])) goto error;
            }
            //日志数据
            $logs=[
                'r_id'          =>$rs['id'],
                'r_no'          =>$rs['r_no'],
                'uid'           =>$this->seller_id,
                'status'        =>100,
                'type'          =>$rs['type'],
                'remark'        =>$reason, //卖家同意退款
                'num'           => $rs['num'],
                'money'         => $rs['money'],
                'refund_express'=> $rs['refund_express'],
                'score'         => $rs['score']
            ];

            if(!$this->sw[]=D('Common/RefundLogs')->create($logs)){
                $msg=D('Common/RefundLogs')->getError();
                goto error;
            }        

            if(!$this->sw[]=D('Common/RefundLogs')->add()) goto error;

            $do->commit();
			
			//发送退款消息
			$msg_data = ['tpl_tag'=>'refund_agree','r_no'=>$rs['r_no']];
			tag('send_msg',$msg_data);
			
            return $this->apiReturn(1, ['data' => ['r_no' => $rs['r_no']]]);

            error:
                $do->rollback();
                return $this->apiReturn(0,'',C('error_code.0').$msg);     
        }else{
            return $this->apiReturn($erp_res->code,'',$erp_res->msg . $erp_res->info);
        }                     
    }

    /**
    * 同意 退货并退款
    */
    public function _accept1($rs,$res,$is_sys,$address_id=0, $reasons = null){
        
        //取卖家常用退货地址
        $area=$this->cache_table('area');
        $a_map['uid']   = $this->seller_id;
        if($address_id) $a_map['id'] = $address_id;
        $address=M('send_address')->where($a_map)->order('is_default desc')->find();
        $address_str=$area[$address['province']].' '.$area[$address['city']].' '.$area[$address['district']].' '.$area[$address['town']].' '.$address['street'].($address['postcode']?'('.$address['postcode'].')':'').'，'.$address['linkname'].'，'.$address['mobile'].($address['tel']?'，'.$address['tel']:'');

        $do=M();
        $do->startTrans();
        
        if(!$this->sw[]=M('refund')->where(['r_no' => $rs['r_no']])->save(['status' => 4,'dotime' => date('Y-m-d H:i:s'),'accept_time' => date('Y-m-d H:i:s'),'next_time' => date('Y-m-d H:i:s',time() + C('cfg.orders')['refund_express']),'is_problem' => 0])) goto error;

        //$reason = '<p class="strong text_red">卖家同意退货退款</p>';
        //if (!is_null($reasons)) $reason .= '<p>'.$reasons.'</p>';
        //$reason.= '退货地址：'.$address_str;

        $reason = $reasons ? $reasons : '卖家同意退货退款';

        //日志数据
        $logs=[
            'r_id'          =>$rs['id'],
            'r_no'          =>$rs['r_no'],
            'uid'           =>$this->seller_id,
            'status'        =>4,
            'type'          =>$rs['type'],
            'remark'        =>$reason, //卖家同意退货
            'address'       => $address_str,
        ];

        if(!$this->sw[]=D('Common/RefundLogs')->create($logs)){
            $msg=D('Common/RefundLogs')->getError();
            goto error;
        }        

        if(!$this->sw[]=D('Common/RefundLogs')->add()) goto error;

        $do->commit();
		
		//发送退款消息
		$msg_data = ['tpl_tag'=>'refund_agree','r_no'=>$rs['r_no']];
		tag('send_msg',$msg_data);
		
        return $this->apiReturn(1, ['data' => ['r_no' => $rs['r_no']]]);

        error:
            $do->rollback();
            return $this->apiReturn(0,'',C('error_code.0').$msg);          
    }


    /**
    * 拒绝退款
    * @param string $param['r_no'] 退款单号    
    * @param string $param['reason'] 拒绝理由
    */
    public function reject($param){
        //检查订单状态
        $res=$this->check_s_orders(1);
        if($res['code']!=1) return $res;

        //已付款未发货订单才可执行此操作！
        if($res['data']['status']!=3) return $this->apiReturn(1010);

        $rs=M('refund')
            ->where([
                'r_no'          => $param['r_no'],
                's_id'          => $res['data']['id'],
                'orders_status' => $res['data']['status']
                ])
            ->field('id,r_no,status,type,money,score,orders_goods_id,num')->find();

        if(!$rs) return $this->apiReturn(3);    //找不到记录

        //退款订单已失效！
        if(!in_array($rs['status'],[1,3]))    return $this->apiReturn(1005);
        
        $do=M();
        $do->startTrans();

        if(!$this->sw[]=M('refund')->where(['r_no' => $rs['r_no']])->save(['status' => 2,'dotime' => date('Y-m-d H:i:s'),'next_time' => date('Y-m-d H:i:s',time() + C('cfg.orders')['refund_express']),'is_problem' => 0])) goto error;
        //$reason = '<p class="text_red strong">卖家拒绝退款</p>';
        //$reason.= $param['reason'];
        $reason = $param['reason'] ? $param['reason'] : '卖家拒绝退款';

        //日志数据
        $logs=[
            'r_id'          =>$rs['id'],
            'r_no'          =>$rs['r_no'],
            'uid'           =>$this->seller_id,
            'status'        =>2,
            'type'          =>$rs['type'],
            'remark'        =>$reason, //卖家拒绝退货
            'images'        =>$param['images'] ? $param['images'] : '', //拒绝图片
        ];

        if(!$this->sw[]=D('Common/RefundLogs')->create($logs)){
            $msg=D('Common/RefundLogs')->getError();
            goto error;
        }        

        if(!$this->sw[]=D('Common/RefundLogs')->add()) goto error;

        $do->commit();
		
		//发送退款消息
		$msg_data = ['tpl_tag'=>'refund_refuse','r_no'=>$rs['r_no']];
		tag('send_msg',$msg_data);
		
        return $this->apiReturn(1, ['data' => ['r_no' => $rs['r_no']]]);

        error:
            $do->rollback();
            return $this->apiReturn(0,'',C('error_code.0').$msg);  
    }

    /**
    * 已收到退货且无异议，同意退款
    * @param string $r_no 退款单号    
    * @param int    $is_sys 系统操作
    * @param int    $check_type 订单检验类型，1为卖家，2为买家，0不检验      
    */
    public function accept2($r_no,$check_type=1,$is_sys=0){
        //检查订单状态
        $res=$this->check_s_orders($check_type);
        if($res['code']!=1) return $res;

        //已付款未发货订单才可执行此操作！
        if($res['data']['status']!=3) return $this->apiReturn(1010);

        $rs=M('refund')
            ->where([
                'r_no'          => $r_no,
                's_id'          => $res['data']['id'],
                'orders_status' => $res['data']['status']
                ])
            ->field('id,r_no,status,type,money,score,orders_goods_id,num,s_no,activity_money,refund_express')->find();

        if(!$rs) return $this->apiReturn(3);    //找不到记录

        //退款订单已失效！
        if($rs['status']!=5)    return $this->apiReturn(1005);  


        $buyer  =M('user')->where(['id' => $res['data']['uid']])->field('nick,erp_uid')->find();
        $seller =M('user')->where(['id' => $res['data']['seller_id']])->field('nick,erp_uid')->find();

        //$reason = '<p class="strong text_red">卖家已收到退货</p>';
        //$reason .= C('error_code.1007');
        $reason = '卖家已收到退货且无异议，退款/退货成功!';
        //订单参与活动
        /*$activity    = Activity::refundActivity($rs['s_no'], $rs['money'], $rs['orders_goods_id']);
        
        if ($activity) {
            $rs['money']    -=  $activity['lessMoney'];
            $reason         .=  $activity['msg'];
            $flag            =  Activity::setRefundStatus(trim($activity['ids'], ',')); //设置活动退款处理状态
            if ($flag == false) goto error;
        }*/
        $refund_type    = $rs['score'] > 0? 2:1;
        $inventory_type =   $res['data']['inventory_type'] == 0 ? 2 : $res['data']['inventory_type'];
        $post_data  =   [
            'r_no'              =>$rs['r_no'],
            'money'             =>$rs['money'],
            'score'             =>$rs['score'],
            'buyer_uid'         =>$buyer['erp_uid'],
            'buyer_nick'        =>$buyer['nick'],
            'seller_uid'        =>$seller['erp_uid'],
            'seller_nick'       =>$seller['nick'],
            's_no'              =>$res['data']['s_no'],
            'pay_type'          =>$res['data']['pay_type'],
            'inventory_type'    =>$inventory_type,
            'refundType'        =>$refund_type,
        ];
        //$express_money         =    M('orders_shop')->where(['s_no' => $rs['s_no'], 'refund_express' => ['eq',0]])->getField('express_price_edit');
        if ($rs['refund_express'] > 0) {
            $post_data['money']   +=    $rs['refund_express'];   //有退运费，则相加
        }
        //如果当前退款为最后一步的话，则把剩余运费退还给买家
        /*if ($res['data']['goods_price_edit'] - (round($rs['money'] + $res['data']['refund_price'], 2)) == 0) {
            //查找是否还有其他正在退数量或者退运费的订单
            $otherRefunds = M('refund')->where(['s_no' => $res['data']['s_no'], 'r_no' => ['neq' => $rs['r_no']], 'status' => ['notin', '20,100']])->field('id,s_no,r_no,type')->select();
            if ($otherRefunds) {
                $otherRefundsFlag = true;
                $model = M();
                $model->startTrans();
                $reason = '您正在退最后一笔款，商家将把未同意退的商品数量或运费直接给您退了,所以关闭了当前退款单号。';
                $dotime = date('Y-m-d H:i:s', NOW_TIME);
                foreach ($otherRefunds as $k => $v) {
                    //日志数据
                    $closeRefund[$k] = M('refund')->where(['id' => $v['id']])->save(['status' => 20, 'dotime' => $dotime, 'cancel_time' => $dotime]);
                    if ($closeRefund[$k] == false) {
                        $otherRefundsFlag = false;
                        break;
                    }
                    $vlogs[$k]=[
                        'r_id'          =>$v['id'],
                        'r_no'          =>$v['r_no'],
                        'uid'           =>$this->seller_id,
                        'status'        =>100,
                        'type'          =>$v['type'],
                        'remark'        =>$reason, //卖家同意退款
                        'is_sys'        =>0,
                    ];
                    if(M('refund_logs')->add($vlogs[$k]) == false) {
                        $otherRefundsFlag = false;
                        break;
                    }
                }
                if ($otherRefundsFlag == false) {
                    $model->rollback();
                    return $this->apiReturn(0,'','操作其他未退运费或数量退款单失败');
                }
                $model->commit();
            }
            $lastExpressMoney = $res['data']['express_price_edit'] - (round($rs['refund_express'] + $res['data']['refund_express'], 2));
            $reason .= '<p>外加剩余运费 <span class="strong text_red">'.$lastExpressMoney.'</span> 元</p>';
            $rs['refund_express'] += $lastExpressMoney;    //运费+上最后剩余运费
            $post_data['money'] += $lastExpressMoney;
        }*/
        
        
        $mallDeal   =   true;
        if ($post_data['money'] > 0) {
            $erp_res=A('Erp')->_refund($post_data);
            if ($erp_res->code!=1) $mallDeal = false;
        }
        if($mallDeal){
            $do=M();
            $do->startTrans();
            if ($rs['money'] > 0) Activity::refundLessMoney($this->s_no, $res['data']['uid'], $rs['money']);  //活动减金额，主要针对累积升级
            if(!$this->sw[]=M('refund')->where(['r_no' => $rs['r_no']])->save(['status' => 100,'dotime' => date('Y-m-d H:i:s'),'accept_time' => date('Y-m-d H:i:s'), 'refund_express' => $rs['refund_express']])) goto error;
            
            if ($rs['refund_express'] > 0) {
                if(!$this->sw[]=$do->execute('update '.C('DB_PREFIX').'orders_shop set refund_num=refund_num+'.$rs['num'].',refund_price=refund_price+'.$rs['money'].',refund_score=refund_score+'.$rs['score'].',money=money-'.$post_data['money'].',refund_express=refund_express+'.$rs['refund_express'].' where s_no="'.$this->s_no.'"')) goto error;
            } else {
                if(!$this->sw[]=$do->execute('update '.C('DB_PREFIX').'orders_shop set refund_num=refund_num+'.$rs['num'].',refund_price=refund_price+'.$rs['money'].',refund_score=refund_score+'.$rs['score'].',money=money-'.$rs['money'].' where s_no="'.$this->s_no.'"')) goto error;
            }
            
            if ($rs['num'] > 0 || $rs['money'] > 0) {
                if(!$this->sw[]=$do->execute('update '.C('DB_PREFIX').'orders_goods set refund_num=refund_num+'.$rs['num'].',refund_price=refund_price+'.$rs['money'].',refund_score=refund_score+'.$rs['score'].' where id='.$rs['orders_goods_id'])) goto error;
            }

            //如果订单中全部款退过完即关闭订单
            if(round($res['data']['money'], 2)-round($post_data['money']+$res['data']['daigou_cost'], 2) ==0){
                //Activity::refundSetStatus($this->s_no, $res['data']['uid']);    //关闭参与活动，主要针对累积升级
                Activity::setStatus($this->s_no, $res['data']['uid'], 2);       //关闭其他活动
                if(!$this->sw[]=M('orders_shop')->where(['s_no' => $this->s_no])->save(['status' => 11,'close_time' => date('Y-m-d H:i:s')])) goto error;
            }
            //日志数据
            $logs=[
                'r_id'          =>$rs['id'],
                'r_no'          =>$rs['r_no'],
                'uid'           =>$this->seller_id,
                'status'        =>100,
                'type'          =>$rs['type'],
                'remark'        =>$reason, //卖家同意退款
                'is_sys'        =>$is_sys
            ];

            if(!$this->sw[]=D('Common/RefundLogs')->create($logs)){
                $msg=D('Common/RefundLogs')->getError();
                goto error;
            }        

            if(!$this->sw[]=D('Common/RefundLogs')->add()) goto error;

            $do->commit();
            return $this->apiReturn(1, ['data' => ['r_no' => $rs['r_no']]]);

            error:
                $do->rollback();
                return $this->apiReturn(0,'',C('error_code.0').$msg);
        }else{
            return $this->apiReturn($erp_res->code,'',$erp_res->msg . $erp_res->info);
        }
    }

}