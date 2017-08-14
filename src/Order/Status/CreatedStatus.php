<?php

namespace Codeages\Biz\Framework\Order\Status;

use Codeages\Biz\Framework\Util\ArrayToolkit;

class CreatedStatus extends AbstractStatus
{
    const NAME = 'created';

    public function getPriorStatus()
    {
        return array();
    }

    public function closed()
    {
        $closeTime = time();
        $order = $this->getOrderDao()->update($this->order['id'], array(
            'status' => ClosedStatus::NAME,
            'close_time' => $closeTime
        ));

        $items = $this->getOrderItemDao()->findByOrderId($this->order['id']);
        foreach ($items as $item) {
            $this->getOrderItemDao()->update($item['id'], array(
                'status' => ClosedStatus::NAME,
                'close_time' => $closeTime
            ));
        }

        return $order;
    }

    public function paid($data = array())
    {
        $data = ArrayToolkit::parts($data, array(
            'order_sn',
            'trade_sn',
            'pay_time'
        ));

        $order = $this->getOrderDao()->getBySn($data['order_sn'], array('lock' => true));
        $order = $this->payOrder($order, $data);
        $this->payOrderItems($order);
        return $order;
    }

    protected function payOrder($order, $data)
    {
        $data = ArrayToolkit::parts($data, array(
            'trade_sn',
            'pay_time'
        ));
        $data['status'] = PaidStatus::NAME;
        return $this->getOrderDao()->update($order['id'], $data);
    }

    protected function payOrderItems($order)
    {
        $items = $this->getOrderItemDao()->findByOrderId($order['id']);
        $fields = ArrayToolkit::parts($order, array('status'));
        $fields['pay_time'] = $order['pay_time'];
        foreach ($items as $item) {
            $this->getOrderItemDao()->update($item['id'], $fields);
        }
    }
}