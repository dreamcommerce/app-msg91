<?php

/**
 * Class Webhookapp
 */
class Webhookapp
{

    /**
     * @var array sended data
     * 
     * OK
     * 
     */
    public $data = array();
    
    /**
     * @var array params from headers
     * 
     * OK
     * 
     */
    public $params = array();

    /**
     * @var current shop id
     * 
     * OK
     * 
     */
    public $shopId = '';

    /**
     * @var array configuration storage
     */
    public $config = array();

    /**
     * instantiate
     * @param array $config
     */
    public function __construct($config){
        $this->config = $config;
    }

    /**
     * main application bootstrap
     * @throws Exception
     */
    public function bootstrap()
    {
        
        // checks request
        $this->validateWebhook();
        
        // detect if shop is already installed
        $shopId = $this->getShopId($this->params['license']);
        if (!$shopId) {
            file_put_contents('./logs/webhooks.log', date("Y-m-d H:i:s"). ' License incorrect or application is not installed in shop.', FILE_APPEND);
            die();
        }
        
        $this->shopId = $shopId;
              
        $controller = new \Controller\Webhook($this, $this->params);
        $actionName =  'statusAction';
        // fire
        $result = call_user_func_array(array($controller, $actionName), array($this->shopId, $this->data));

    }
    
    
    /**
     * checks variables and hash
     * @throws Exception
     * 
     * OK
     * 
     */
    public function validateWebhook()
    {

	if (!function_exists('getallheaders'))  {
	    function getallheaders()
	    {
	        if (!is_array($_SERVER)) {
	            return array();
	        }
	
	        $headers = array();
	        foreach ($_SERVER as $name => $value) {
	            if (substr($name, 0, 5) == 'HTTP_') {
	                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
	            }
	        }
	        return $headers;
	    }
	}
        
        $headers = getallheaders();
        
        $this->params = array(
            'id' => $headers['X-Webhook-Id'],
            'name' => $headers['X-Webhook-Name'],
            'shop' => $headers['X-Shop-Domain'],
            'license' => $headers['X-Shop-License'],
            'sha1' => $headers['X-Webhook-Sha1'],
        );        
        
        $secret_key = 'kjhKJHkjh876&*^';
        $jsondata = file_get_contents("php://input");
        $sha1 = sha1($this->params['id'] . ':' . $secret_key . ':' . $jsondata);

        if ($sha1 != $this->params['sha1']) {
            file_put_contents('logs/webhooks.log', date('Y:m:d H:i:s'). ' Validation failed: bad checksum: ' . $sha1 . PHP_EOL, FILE_APPEND);
            die();
        }
        
        $this->data = json_decode($jsondata, true);
        
    }
    
    /**
     * @return bool
     */
    public function getDebug(){
        return $this->config['debug'];
    }
    
    /**
     * @return string
     */
    public function getShopDataToDebug(){
        $shopData = 'URL: ' . $this->params['shop'] . ' LICENSE: ' . $this->params['license'];
        return $shopData;
    }
    
    /**
     * get installed shop info
     * @param $license
     * @return array|bool
     */
    public function getShopId($license)
    {
        $db = $this->db();
        $stmt = $db->prepare('select id from shops where shop=:license');
        if (!$stmt->execute(array(':license' => $license))) {
            return false;
        }
        $result = $stmt->fetch();
        
        return $result['id'];

    }
    
    /**
     * instantiate db connection
     * @return PDO
     */
    public function db()
    {
        static $handle = null;
        if (!$handle) {
            $handle = new PDO(
                $this->config['db']['connection'],
                $this->config['db']['user'],
                $this->config['db']['pass']
            );
        }

        return $handle;
    }

    /**
     * shows more friendly exception message
     * @param Exception $ex
     */
    public function handleException(\Exception $ex)
    {
        $message = $ex->getMessage();
        require __DIR__ . '/../view/exception.php';
    }

    public static function escapeHtml($message){
        return htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    }
    
}