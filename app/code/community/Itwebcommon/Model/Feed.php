<?php

class ITwebexperts_Itwebcommon_Model_Feed extends Mage_AdminNotification_Model_Feed {

    public function getFeedUrl() {
        $url = 'http://magt.sidev.info/rent19/shell/feed.rss';
        return $url;
    }

    public function observe() {
        $model  = Mage::getModel('itwebcommon/feed');
        $model->checkUpdate();
    }
    public function getLastUpdate()
    {
        return Mage::app()->loadCache('itwebexperts_itwebcommom_feed_lastcheck');
    }

    public function setLastUpdate()
    {
        Mage::app()->saveCache(time(), 'itwebexperts_itwebcommom_feed_lastcheck');
        return $this;
    }
}