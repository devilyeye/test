<?php

class express_rpc_electron_router {
    public $channel;

    public function setChannel($channel) {
        $this->channel = $channel;
        return $this;
    }

    public function __call($method,$args)
    {
        if ($this->channel) {
            $channelType = $this->channel['channel_type'];
            try {
                $platform = kernel::single('express_rpc_electron_'.$channelType);
            } catch (Exception $e) {}
        }

        $platform->setChannel($this->channel);
        return call_user_func_array(array($platform,$method), $args);
    }
}