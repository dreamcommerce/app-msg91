<?php

/**
 * Class App
 * example for xml importing
 */
class App
{

    /**
     * @var null|DreamCommerce\Client
     */
    protected $client = null;
    /**
     * @var string default locale
     */
    protected $locale = 'en_US';
    /**
     * @var string default locale
     */
    protected $translations = 'en_US';

    /**
     * @var array current shop metadata
     */
    public $shopData = array();

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

        // check request hash and variables
        $this->validateRequest();

        $this->translations = basename($_GET['translations']);
        $this->locale = basename($_GET['locale']);

        // detect if shop is already installed
        $shopData = $this->getShopData($_GET['shop']);
        if (!$shopData) {
            throw new Exception('An application is not installed in this shop');
        }

        $this->shopData = $shopData;

        // refresh token
        if (strtotime($shopData['expires']) - time() < 86400) {
            $shopData = $this->refreshToken($shopData);
        }

        // instantiate SDK client
        $this->client = $this->instantiateClient($shopData);

        // fire
        $this->dispatch();

    }

    /**
     * dispatcher
     * @throws Exception
     */
    protected function dispatch()
    {

        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $path = ltrim($path, '/');

        // check for parameter existence
        $query = $path=='' ? 'index/index' : $path;

        $query = str_replace('\\', '', $query);

        $queryData = explode('/', $query);

        $controllerName = ucfirst($queryData[0]);
        $class = '\\Controller\\'.$controllerName;

        if(!class_exists($class)){
            throw new Exception('Controller not found');
        }

        $params = $_GET;
        if(!empty($params['id'])){
            $params['id'] = @json_decode($params['id']);
        }

        $actionName = strtolower($queryData[1]).'Action';
        $controller = new $class($this, $params);
        if(!method_exists($controller, $actionName)){
            throw new Exception('Action not found');
        }

        $controller['shopUrl'] = $this->shopData['url'];

        $result = call_user_func_array(array($controller, $actionName), array_slice($queryData, 2));

        if($result!==false) {
            $viewName = strtolower($queryData[0]) . '/' . strtolower($queryData[1]);
            $controller->render($viewName);
        }

    }

    /**
     * instantiate client resource
     * @param $shopData
     * @return \DreamCommerce\Client
     */
    public function instantiateClient($shopData)
    {

        $c = new DreamCommerce\Client($shopData['url'], $this->config['appId'], $this->config['appSecret']);
        $c->setAccessToken($shopData['access_token']);

        return $c;
    }

    /**
     * get client resource
     * @throws Exception
     * @return \DreamCommerce\Client|null
     */
    public function getClient(){
        if($this->client===null){
            throw new Exception('Client is NOT instantiated');
        }

        return $this->client;
    }

    /**
     * @return string
     */
    public function getLocale(){
        return $this->locale;
    }
    /**
     * @return string
     */
    public function getTranslations(){
        return $this->translations;
    }
    /**
     * @return bool
     */
    public function getDebug(){
        return $this->config['debug'];
    }

    /**
     * refresh OAuth token
     * @param array $shopData
     * @return mixed
     * @throws Exception
     */
    public function refreshToken($shopData)
    {
        $c = new DreamCommerce\Client($shopData['url'], $this->config['appId'], $this->config['appSecret']);
        $tokens = $c->refreshToken($shopData['refresh_token']);
        $expirationDate = date('Y-m-d H:i:s', time() + $tokens['expires_in']);

        try {
            $db = $this->db();
            $stmt = $db->prepare('update access_tokens set refresh_token=?, access_token=?, expires_at=? where shop_id=?');
            $success = $stmt->execute(array($tokens['refresh_token'], $tokens['access_token'], $expirationDate, $shopData['id']));
            if (!$success) {
                file_put_contents(
                    'logs/tokens.log',
                    date("Y-m-d H:i:s") . ' Shop: '. var_export($shopData, true) . PHP_EOL . 'Tokens: '. var_export($tokens, true) . PHP_EOL,
                    FILE_APPEND);
            }
        } catch (PDOException $ex) {
            file_put_contents(
                'logs/tokens.log',
                date("Y-m-d H:i:s") . ' Shop: '. var_export($shopData, true) . PHP_EOL . 'Tokens: '. var_export($tokens, true) . PHP_EOL,
                FILE_APPEND);
            throw new Exception('Database error', 0, $ex);
        }
        $shopData['refresh_token'] = $tokens['refresh_token'];
        $shopData['access_token'] = $tokens['access_token'];

        return $shopData;
    }

    /**
     * checks variables and hash
     * @throws Exception
     */
    public function validateRequest()
    {
        if (empty($_GET['translations'])) {
            throw new Exception('Invalid request');
        }

        $params = array(
            'place' => $_GET['place'],
            'shop' => $_GET['shop'],
            'timestamp' => $_GET['timestamp'],
        );

        ksort($params);
        $parameters = array();
        foreach ($params as $k => $v) {
            $parameters[] = $k . "=" . $v;
        }
        $p = join("&", $parameters);


        $hash = hash_hmac('sha512', $p, $this->config['appstoreSecret']);

        if ($hash != $_GET['hash']) {
            throw new Exception('Invalid request');
        }

    }

    /**
     * get installed shop info
     * @param $shop
     * @return array|bool
     */
    public function getShopData($shop)
    {
        $db = $this->db();
        $stmt = $db->prepare('select a.access_token as access_token, a.refresh_token as refresh_token, s.shop_url as url, a.expires_at as expires, a.shop_id as id from access_tokens a join shops s on a.shop_id=s.id where s.shop=?');
        if (!$stmt->execute(array($shop))) {
            return false;
        }

        return $stmt->fetch();

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
    
    public static function getUrl($url){
        $params = array();
        parse_str($_SERVER['QUERY_STRING'], $params);
        $params['q'] = $url;
        $query = http_build_query($params);
        return $url.'?'.$query;
    }
}
