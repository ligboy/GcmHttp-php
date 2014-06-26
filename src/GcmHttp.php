<?php
namespace org\ligboy\GcmHttp;
/**
 * 
 * $Id: $
 */

class GcmHttp {

    /**
     *
     */
    const  SEND_URL = "https://gcm.googleapis.com/gcm/send";

    private $_api_key;
    private $_proxy = false;
    private $_proxy_userpwd;

    private $_curl_handle = null;
    private $_multi_curl_handle = null;

    /**
     * @param string $api_key The API_KEY
     * @param string $proxy The http Proxy,like this: www.theproxy.com:8080 . (Optional)
     * @param string $proxy_userpwd If the Proxy need authorization. Like this: username:password (Optional)
     * @throws \Exception The Api_key can't be NULL.
     */
    function __construct($api_key, $proxy = null, $proxy_userpwd = null) {
        if(empty($api_key)) {
            throw new \Exception("The Api_key can't be NULL.");
        }

        $this->_api_key = $api_key;
        if($proxy) {
            $this->_proxy = $proxy;
            if(!empty($proxy_userpwd)) {
                $this->_proxy_userpwd = $proxy_userpwd;
            }
        }
    }

    /**
     * Send the data or message to Registration_ids.
     * @param array $registration_ids Registration_Ids
     * @param array|string $data Er. The sent data.
     * @param array|null $option The Sender Option See http://developer.android.com/intl/zh-cn/google/gcm/server.html#params for a list of all the parameters your JSON or plain text message can contain.
     * @return array
     */
    public function send(array $registration_ids, $data, array $option = null) {
        $all_results = null;
        if(count($registration_ids) > 1000) {
            $registration_ids_big = array_chunk($registration_ids, 1000, true);
            $multi_curl_handle = curl_multi_init();
            $map = array();
            foreach($registration_ids_big as $key=>$value) {
                $ch = $this->generateCurlHandle($value, $data, $option);
                curl_multi_add_handle($multi_curl_handle, $this->generateCurlHandle($value, $data, $option));
                $map[(string) $ch] = $key;

            }
            $all_results = array(
                'multicast_id' => array(),
                'success' => 0,
                'failure' => 0,
                'results' => array()
            );
            do {
                while (($code = curl_multi_exec($multi_curl_handle, $active)) == CURLM_CALL_MULTI_PERFORM) ;

                if ($code != CURLM_OK) { break; }

                // 找到刚刚完成的任务句柄
                while ($done = curl_multi_info_read($multi_curl_handle)) {
                    // 处理当前句柄的信息、错误、和返回内容
                    $info = curl_getinfo($done['handle']);
                    $error = curl_error($done['handle']);

                    $every_result = json_decode(curl_multi_getcontent($done['handle']));

                    $all_results['multicast_id'][$map[(string) $done['handle']]] = $every_result['multicast_id'];
                    $all_results['success'] += $every_result['success'];
                    $all_results['failure'] += $every_result['failure'];
                    $all_results['results'][$map[(string) $done['handle']]] += $every_result['results'];

                    // 从队列里移除上面完成处理的句柄
                    curl_multi_remove_handle($multi_curl_handle, $done['handle']);
                    curl_close($done['handle']);
                }

                // Block for data in / output; error handling is done by curl_multi_exec
                if ($active > 0) {
                    curl_multi_select($multi_curl_handle, 0.5);
                }

            } while ($active);

            curl_multi_close($multi_curl_handle);
            $multi_curl_handle = null;
            sort($all_results['multicast_id']);
            sort($all_results['results']);
            $tmp_results_array = array();
            foreach($all_results['results'] as $tmp_value) {
                $tmp_results_array = array_merge($tmp_results_array, $tmp_value);
            }
            $all_results['results'] = $tmp_results_array;

        } else {
            $curl_handle = $this->generateCurlHandle($registration_ids, $data, $option);
            $all_results = curl_exec($curl_handle);
            curl_close($curl_handle);
            $curl_handle = null;
            $all_results = json_decode($all_results);
        }
        return $all_results;
    }

    /**
     * @param array $registration_ids
     * @param $data
     * @param array $option
     * @return resource
     */
    private function generateCurlHandle(array $registration_ids, $data, array $option = null) {
        $curl_handle = curl_init(self::SEND_URL);
        curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl_handle, CURLOPT_HEADER, 0);
        curl_setopt($curl_handle, CURLOPT_POST, 1);
        if($this->_proxy) {
            curl_setopt($curl_handle, CURLOPT_PROXY, $this->_proxy);
            if(!empty($this->_proxy_userpwd)) {
                curl_setopt($curl_handle, CURLOPT_PROXYUSERPWD, $this->_proxy_userpwd);
            }
        }
        curl_setopt($curl_handle, CURLOPT_HTTPHEADER, array("Content-Type: application/json", "Authorization: key=" . $this->_api_key));

        $sent_array = array(
            'registration_ids' => $registration_ids,
            'data' => $data
        );
        if($option != null && is_array($option)) {
            $sent_array = array_merge($sent_array, $option);
        }
        curl_setopt($curl_handle, CURLOPT_POSTFIELDS, json_encode($sent_array));

        return $curl_handle;
    }
}
