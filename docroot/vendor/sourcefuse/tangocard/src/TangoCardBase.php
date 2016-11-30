<?php
namespace Sourcefuse;

/**
 * Copyright 2014 Sourcefuse, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
class TangoCardBase {

    /**
     * Version.
     */
    const VERSION = '1.0.0';

    /**
     * Default options for curl.
     *
     * @var array
     */
    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'tangocard-php-1.0',
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_RETURNTRANSFER => TRUE
    );

    /**
     * This method is used to send request to tangocard
     *
     * @var array
     */
    protected function tangoCardRequest($requestUrl, $params = False, $isPost = FALSE) {
        $ch = curl_init();
        $opts = self::$CURL_OPTS;
        curl_setopt_array($ch, $opts);
        curl_setopt($ch, CURLOPT_URL, $requestUrl);
        if ($isPost) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        }
        curl_setopt($ch, CURLOPT_CAINFO, dirname(__FILE__) . DIRECTORY_SEPARATOR . 'tangocard_digicert_chain.pem');
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization:Basic ' . base64_encode($this->platformName . ':' . $this->platformKey)
        ));
        $result = curl_exec($ch);
        $error = curl_error($ch);
        if (!$result) {
            curl_close($ch);
            throw new TangoCardNetworkException();
        }
        curl_close($ch);
        return $result;
    }

}

?>