<?php

class express_rpc_electron_data_router {
    public $channel;
    public $logiId;
    public $dlyCenter;

    public function setChannel($channel) {
        $this->channel = $channel;
        return $this;
    }

    public function setLogiId($logiId) {
        $this->logiId = $logiId;
        return $this;
    }

    public function setDlyCenter($dlyCenter) {
        $this->dlyCenter = $dlyCenter;
        return $this;
    }

    public function __call($method,$args)
    {
        $platform = kernel::single('express_rpc_electron_data_common');
        if ($this->channel) {
            $channelType = $this->channel['channel_type'];
            try {
                $platform = kernel::single('express_rpc_electron_data_'.$channelType);
            } catch (Exception $e) {}
        }

        $platform->setChannel($this->channel);
        $platform->setLogiId($this->logiId);
        $platform->setDlyCenter($this->dlyCenter);
        return call_user_func_array(array($platform,$method), $args);
    }
}