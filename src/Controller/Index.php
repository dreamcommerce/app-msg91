<?php
namespace Controller;

class Index extends ControllerAbstract{
    
    public function indexAction() {

        //prepare
        $settings = Array();
        $locale = $this->app->getLocale();
        $this['translations'] = $translations = $this->app->getTranslations();
        
        // always get current settings of statuses in shop
        $pages = 1;
        for ($i = 1; $i <= $pages; $i++) {
            $response = $this->app->getClient()->Status->get();
            $pages = $response->getPageCount();
            foreach ($response as $status) {
                
                if(isset($status['translations'][$translations]))
                {
                    $name = $status['translations'][$translations]['name'];
                }
                elseif(isset($status['translations'][$locale]))
                {
                    $name = $status['translations'][$locale]['name'];
                }
                elseif(isset($status['translations']['en_US']))
                {
                    $name = $status['translations']['en_US']['name'];
                }
                else
                {
                    $name = 'status ID '.$status['status_id'];
                }
				
                $settings[$status['status_id']] = array(
                    'id' => $this->app->escapeHtml($status['status_id']),
                    'name' => $this->app->escapeHtml($name),
                    'on' => false,
                    'message' => '',
                );
            }
        }
        $this['settings'] = $settings;
        
        // if saving configuration
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $errors = Array();
            $this['editedStatusId'] = $this->app->escapeHtml($_POST['status']);
            
            // get current configuration of message translations in app
            $shopId = $this->app->getShopId($_GET['shop']);
            $db = $this->app->db();
            $stmt = $db->prepare('SELECT data FROM shops_settings WHERE shop_id = :shop_id');
            $stmt->execute(array(
                ':shop_id' => $shopId
            ));
            $result = $stmt->fetch();
            $exists = $stmt->rowCount();
            if ($exists == 1) {
                $msgTranslations = unserialize($result['data']);
                $msgTranslations = $msgTranslations['settings'];
            }

            // merge settings from database and saved translation
            $config = Array();
            
            foreach($settings as $key => $value){
                if(isset($_POST['on'.$value['id']])){
                    $settings[$key]['on'] = true;
                }
                if(isset($msgTranslations[$key]['message'])){
                    $settings[$key]['message'] = $msgTranslations[$key]['message'];
                }
                if(isset($_POST['msg'.$value['id']])){
                    if($_POST['msg'.$value['id']] == '' AND isset($_POST['on'.$value['id']])){
                        $errors['translations'][] = $value['name'];
                    }
                    $settings[$key]['message'][$translations] = $this->app->escapeHtml($_POST['msg'.$value['id']]);
                } elseif (isset($_POST['on'.$value['id']])) {
                    $errors['translations'][] = $value['name'];
                }
            }
            $this['settings'] = $config['settings'] = $settings;
            $this['authkey'] = $config['authkey'] = $this->app->escapeHtml($_POST['authkey']);
            $this['sender'] = $config['sender'] = $this->app->escapeHtml($_POST['sender']);
            
            // validate
            if(strlen($config['authkey'])==0){
                $errors['authkey'] = 'Authkey cannot be empty!';
            }
            if(strlen($config['sender'])==0){
                $errors['sender'] = 'Sender cannot be empty!';
            }

            // save configuration
            if (empty($errors)) {
                
                $langs = array();
                $response = $this->app->getClient()->Language->get();
                foreach($response as $lang) {
                    $langs[$lang['lang_id']] = $lang['locale'];
                }
                $this['message'] = 'Saved';
                $this['type'] = 'success';
                $shopId = $this->app->getShopId($_GET['shop']);
                $db = $this->app->db();
                $stmt = $db->prepare('SELECT id FROM shops_settings WHERE shop_id = :shop_id');
                $stmt->execute(array(
                        ':shop_id' => $shopId
                    ));
                $exists = $stmt->rowCount();
                if($exists == 1) {
                    $stmt = $db->prepare('UPDATE shops_settings SET data = :data, langs = :langs WHERE shop_id = :shop_id');
                } else {
                    $stmt = $db->prepare('INSERT INTO shops_settings (shop_id, data, langs) VALUES (:shop_id, :data, :langs)');
                }
                $stmt->execute(array(
                            ':shop_id' => $shopId,
                            ':data' => serialize($config),
                            ':langs' => serialize($langs)
                    ));
            } else {
                $this['message'] = 'Configuration not saved: invalid data';
                $this['type'] = 'error';
            }
            
            $this['errors'] = $errors;
            
        } else {
            $this['editedStatusId'] = 0;
            $shopId = $this->app->getShopId($_GET['shop']);
            $db = $this->app->db();
            $stmt = $db->prepare('SELECT data FROM shops_settings WHERE shop_id = :shop_id');
            $stmt->execute(array(
                    ':shop_id' => $shopId
                ));
            $result = $stmt->fetch();
            $exists = $stmt->rowCount();
            if($exists == 1) {
                $config = unserialize($result['data']);
                $this['authkey'] = $this->app->escapeHtml($config['authkey']);
                $this['sender'] = $this->app->escapeHtml($config['sender']);
                foreach($settings as $key => $value){
                    if(!isset($config['settings'][$key])){
                        $config['settings'][$key] = $value;
                    }
                }
                foreach($config['settings'] as $key => $value){
                    if(!isset($settings[$key])){
                        unset($config['settings'][$key]);
                    }else{
                        $config['settings'][$key]['name'] = $this->app->escapeHtml($settings[$key]['name']);
                    }
                }
                $this['settings'] = $config['settings'];
            } else {
                $this['message'] = 'Please set configuration';
                $this['type'] = 'info';
                
                $this['authkey'] = '';
                $this['sender'] = '';
            }
            
        }
        
    }

}