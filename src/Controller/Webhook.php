<?php

namespace Controller;

class Webhook extends ControllerAbstract {

    public function __construct(\Webhookapp $webhookapp, $params = array()) {
        $this->params = $params;
        $this->webhookapp = $webhookapp;
    }

    public function statusAction($shopId, $webhook) {
        
        $db = $this->webhookapp->db();
        
        //check if configuration of app is set
        $stmt = $db->prepare('SELECT * FROM shops_settings WHERE shop_id = :shop_id');
        $stmt->execute(array(
            ':shop_id' => $shopId
        ));
        $result = $stmt->fetch();
        $data = unserialize($result['data']);
        $langs = unserialize($result['langs']);
        $exists = $stmt->rowCount();
        if ($exists != 1) {
            if ($this->webhookapp->getDebug()) {
                file_put_contents('./logs/webhook.log', date("Y-m-d H:i:s") . ' ' . $this->webhookapp->getShopDataToDebug() . ':  Message not sent, set configuration first' . PHP_EOL, FILE_APPEND);
            }
            return;
        }

        // get required data
        $authkey = $data['authkey'];
        $sender = $data['sender'];
        if (isset($data['route']) && ($data['route'] == 1 || $data['route'] = 4)) {
            $route = $data['route'];
        } else {
            $route = '4';
        }
        $settings = $data['settings'];
        $mobile = $webhook['billingAddress']['phone'];
        $country_code = strtoupper($webhook['billingAddress']['country_code']);
        $status_id = $webhook['status']['status_id'];
        foreach ($settings as $value) {
            if ($value['id'] == $status_id) {
                if ($value['on'] != true) {
                    if ($this->webhookapp->getDebug()) {
                        file_put_contents('./logs/webhook.log', date("Y-m-d H:i:s") . ' ' . $this->webhookapp->getShopDataToDebug() . ': Message not sent, configuration off for status ID: ' . $status_id . PHP_EOL, FILE_APPEND);
                    }
                    return;
                } else {
                    if(isset($langs[$webhook['lang_id']])){
                        $locale = $langs[$webhook['lang_id']];
                    } else {
                        $locale = 'pl_PL';
                    }
                    $languages = array($locale, 'en_US', 'en_IN');
                    $message = '';
                    foreach ($languages as $language) {
                        if (isset($value['message'][$language])) {
                            if (strlen($value['message'][$language]) > 0) {
                                $message = $value['message'][$language];
                                break;
                            }
                        }
                    }
                    if ($message == '') {
                        if ($this->webhookapp->getDebug()) {
                            file_put_contents('./logs/webhook.log', date("Y-m-d H:i:s") . ' ' . $this->webhookapp->getShopDataToDebug() . ':  Message not sent, message content for status ID: ' . $status_id . ' is not translated in languages: ' . $locale . ', en_US and en_IN' . PHP_EOL, FILE_APPEND);
                        }
                        return;
                    }
                }
            }
        }
        $country = '0';
        $unicode = '1';

        //prepare phone number
        $phones = array('AC' => 247, 'AD' => 376, 'AE' => 971, 'AF' => 93, 'AG' => 1, 'AI' => 1, 'AL' => 355, 'AM' => 374, 'AO' => 244, 'AQ' => 672, 'AR' => 54, 'AS' => 1, 'AT' => 43, 'AU' => 61, 'AW' => 297, 'AX' => 358, 'AZ' => 994, 'BA' => 387, 'BB' => 1, 'BD' => 880, 'BE' => 32, 'BF' => 226, 'BG' => 359, 'BH' => 973, 'BI' => 257, 'BJ' => 229, 'BL' => 590, 'BM' => 1, 'BN' => 673, 'BO' => 591, 'BQ' => 599, 'BR' => 55, 'BS' => 1, 'BT' => 975, 'BW' => 267, 'BY' => 375, 'BZ' => 501, 'CA' => 1, 'CC' => 61, 'CD' => 243, 'CF' => 236, 'CG' => 242, 'CH' => 41, 'CI' => 225, 'CK' => 682, 'CL' => 56, 'CM' => 237, 'CN' => 86, 'CO' => 57, 'CR' => 506, 'CU' => 53, 'CV' => 238, 'CW' => 599, 'CX' => 61, 'CY' => 357, 'CZ' => 420, 'DE' => 49, 'DJ' => 253, 'DK' => 45, 'DM' => 1, 'DO' => 1, 'DZ' => 213, 'EC' => 593, 'EE' => 372, 'EG' => 20, 'EH' => 212, 'ER' => 291, 'ES' => 34, 'ET' => 251, 'FI' => 358, 'FJ' => 679, 'FK' => 500, 'FM' => 691, 'FO' => 298, 'FR' => 33, 'GA' => 241, 'GB' => 44, 'GD' => 1, 'GE' => 995, 'GF' => 594, 'GG' => 44, 'GH' => 233, 'GI' => 350, 'GL' => 299, 'GM' => 220, 'GN' => 224, 'GP' => 590, 'GQ' => 240, 'GR' => 30, 'GS' => 500, 'GT' => 502, 'GU' => 1, 'GW' => 245, 'GY' => 592, 'HK' => 852, 'HN' => 504, 'HR' => 385, 'HT' => 509, 'HU' => 36, 'ID' => 62, 'IE' => 353, 'IL' => 972, 'IM' => 44, 'IN' => 91, 'IO' => 246, 'IQ' => 964, 'IR' => 98, 'IS' => 354, 'IT' => 39, 'JE' => 44, 'JM' => 1, 'JO' => 962, 'JP' => 81, 'KE' => 254, 'KG' => 996, 'KH' => 855, 'KI' => 686, 'KM' => 269, 'KN' => 1, 'KP' => 850, 'KR' => 82, 'KW' => 965, 'KY' => 1, 'KZ' => 7, 'LA' => 856, 'LB' => 961, 'LC' => 1, 'LI' => 423, 'LK' => 94, 'LR' => 231, 'LS' => 266, 'LT' => 370, 'LU' => 352, 'LV' => 371, 'LY' => 218, 'MA' => 212, 'MC' => 377, 'MD' => 373, 'ME' => 382, 'MF' => 590, 'MG' => 261, 'MH' => 692, 'MK' => 389, 'ML' => 223, 'MM' => 95, 'MN' => 976, 'MO' => 853, 'MP' => 1, 'MQ' => 596, 'MR' => 222, 'MS' => 1, 'MT' => 356, 'MU' => 230, 'MV' => 960, 'MW' => 265, 'MX' => 52, 'MY' => 60, 'MZ' => 258, 'NA' => 264, 'NC' => 687, 'NE' => 227, 'NF' => 672, 'NG' => 234, 'NI' => 505, 'NL' => 31, 'NO' => 47, 'NP' => 977, 'NR' => 674, 'NU' => 683, 'NZ' => 64, 'OM' => 968, 'PA' => 507, 'PE' => 51, 'PF' => 689, 'PG' => 675, 'PH' => 63, 'PK' => 92, 'PL' => 48, 'PM' => 508, 'PN' => 870, 'PR' => 1, 'PS' => 970, 'PT' => 351, 'PW' => 680, 'PY' => 595, 'QA' => 974, 'RE' => 262, 'RO' => 40, 'RS' => 381, 'RU' => 7, 'RW' => 250, 'SA' => 966, 'SB' => 677, 'SC' => 248, 'SD' => 249, 'SE' => 46, 'SG' => 65, 'SH' => 290, 'SI' => 386, 'SJ' => 47, 'SK' => 421, 'SL' => 232, 'SM' => 378, 'SN' => 221, 'SO' => 252, 'SR' => 597, 'SS' => 211, 'ST' => 239, 'SV' => 503, 'SX' => 1, 'SY' => 963, 'SZ' => 268, 'TC' => 1, 'TD' => 235, 'TF' => 262, 'TG' => 228, 'TH' => 66, 'TJ' => 992, 'TK' => 690, 'TL' => 670, 'TM' => 993, 'TN' => 216, 'TO' => 676, 'TR' => 90, 'TT' => 1, 'TV' => 688, 'TW' => 886, 'TZ' => 255, 'UA' => 380, 'UG' => 256, 'UM' => 1, 'US' => 1, 'UY' => 598, 'UZ' => 998, 'VA' => 39, 'VC' => 1, 'VE' => 58, 'VG' => 1, 'VI' => 1, 'VN' => 84, 'VU' => 678, 'WF' => 681, 'WS' => 685, 'YE' => 967, 'YT' => 262, 'ZA' => 27, 'ZM' => 260, 'ZW' => 263);
        $mobile = ltrim($mobile, '0+');
        if (isset($phones[$country_code])) {
            if (substr($mobile, 0, strlen($phones[$country_code])) != $phones[$country_code]) {
                $mobile = $phones[$country_code] . $mobile;
            } elseif ($country_code == 'IN' AND strlen($mobile) < 11) {
                $mobile = $phones[$country_code] . $mobile;
            }
        }

        // change markers to values in message
        $markers = array(
            '{order_id}' => $webhook['order_id'],
            '{sum}' => $webhook['sum'] . ' ' . $webhook['currency_name'],
            '{shipping_cost}' => $webhook['shipping_cost'] . ' ' . $webhook['currency_name'],
            '{status_name}' => $webhook['status']['name'],
            '{delivery_city}' => $webhook['deliveryAddress']['city'],
            '{delivery_postcode}' => $webhook['deliveryAddress']['postcode'],
            '{delivery_street}' => $webhook['deliveryAddress']['street1'],
            '{delivery_country}' => $webhook['deliveryAddress']['country'],
        );
        foreach ($markers as $key => $value) {
            $message = str_replace($key, $value, $message);
        }
        if (strlen($message) < 1) {
            return;
        }
        
        if(mb_check_encoding($message, 'ASCII')){
            $unicode = '0';
        }

        //prepare query
        $variables = array(
            'authkey' => $authkey,
            'mobiles' => $mobile,
            'message' => $message,
            'sender' => $sender,
            'route' => $route,
            'country' => $country,
            'unicode' => $unicode
        );
        $query = http_build_query($variables);

        //make request
        $curl = curl_init();
        $url = 'https://control.msg91.com/api/sendhttp.php?' . $query;
        
        curl_setopt_array($curl, array(
            CURLOPT_RETURNTRANSFER => 1,
            CURLOPT_URL => $url,
            CURLOPT_VERBOSE => 1,
            CURLOPT_HEADER => 1
        ));

        $response = curl_exec($curl);

        $header_size = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $header_size);
        $body = substr($response, $header_size);

        curl_close($curl);

        //save request
        if ($this->webhookapp->getDebug()) {
            file_put_contents('./logs/webhook.log', date("Y-m-d H:i:s") . ' ' . $this->webhookapp->getShopDataToDebug() . ': Sending message.' . PHP_EOL . 'REQUEST URL: ' . $url . PHP_EOL . 'RESPONSE HEADERS:' . PHP_EOL . $header . PHP_EOL . 'RESPONSE BODY' . PHP_EOL . $body . PHP_EOL . PHP_EOL, FILE_APPEND);
        }
        
    }

}
