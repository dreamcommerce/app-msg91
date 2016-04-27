<?php

/**
 * Class App
 * example for xml importing
 */
class CronApp
{

    /**
     * @var null|DreamCommerce\Client
     */
    protected $client = null;

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

        $shopsToRefresh = $this->getShopsToRefresh();
        echo var_export($shopsToRefresh, true);
        $this->refreshTokens($shopsToRefresh);

    }

    /**
     * get shops ids
     * @return array
     */
    public function getShopsToRefresh()
    {
        $expirationDate = date('Y-m-d H:i:s', time() + (86400 * 7));
        $shopsIds = array();
        $db = $this->db();
        $stmt = $db->prepare('SELECT shop_id FROM access_tokens WHERE expires_at < ?');
        $stmt->execute(array($expirationDate));
        $result = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        $resultIds = array();
        if (!!$result) {
            foreach ($result as $shop) {
                $resultIds[] = $shop['shop_id'];
            }
            $shopsIds = array_merge($shopsIds, $resultIds);
        }
        return $shopsIds;
    }

    /**
     * refresh tokens
     * @throws Exception
     */
    protected function refreshTokens($shopsToRefresh)
    {

        if (!$shopsToRefresh) {
            return;
        }

        foreach ($shopsToRefresh as $shopId) {

            $shopData = $this->getShopData($shopId);

            if (!$shopData) {
                file_put_contents(
                'logs/cron.log',
                date("Y-m-d H:i:s") . ' Shop: '. $shopId . PHP_EOL . 'Could not get shop data.' . PHP_EOL,
                FILE_APPEND);
                continue;
            }
            $tokens = null;
            // instantiate SDK client
            try {
                $c = $this->instantiateClient($shopData);
                $tokens = $c->refreshToken($shopData['refresh_token']);
                $expirationDate = date('Y-m-d H:i:s', time() + $tokens['expires_in']);
            } catch (Exception $ex) {
                file_put_contents(
                    'logs/tokens.log',
                    date("Y-m-d H:i:s") . ' Shop: '. var_export($shopData, true) . PHP_EOL . 'Tokens: '. var_export($tokens, true) . PHP_EOL . 'Message: ' . var_export($ex->getMessage(), true) . PHP_EOL,
                    FILE_APPEND);
                continue;
            }

            try {
                $db = $this->db();
                $stmt = $db->prepare('update access_tokens set refresh_token=?, access_token=?, expires_at=? where shop_id=?');
                $success = $stmt->execute(array($tokens['refresh_token'], $tokens['access_token'], $expirationDate, $shopId));
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
                continue;
            }

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
     * get installed shop info
     * @param $id
     * @return array|bool
     */
    public function getShopData($id)
    {
        $db = $this->db();
        $stmt = $db->prepare('select a.access_token, a.refresh_token, s.id, s.shop_url as url, a.expires_at as expires_at from access_tokens a join shops s on a.shop_id=s.id where a.shop_id=?');
        if (!$stmt->execute(array($id))) {
            return false;
        }

        return $stmt->fetch();

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

    public function logApiException($appMsg, $exMsg) {
        file_put_contents(
            $this->config['apiLogFile'],
            date("Y-m-d H:i:s") . ' ' . $appMsg . PHP_EOL . $exMsg . PHP_EOL,
            FILE_APPEND);
    }

    public function logCronException($appMsg) {
        file_put_contents(
            'logs/cron.log',
            date("Y-m-d H:i:s").': '.$appMsg.PHP_EOL,
            FILE_APPEND);
    }

}
