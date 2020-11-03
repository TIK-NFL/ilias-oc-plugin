<?php
namespace TIK_NFL\ilias_oc_plugin\opencast;

require_once __DIR__ . '/../../vendor/autoload.php';

use TIK_NFL\ilias_oc_plugin\ilOpencastConfig;

use Firebase\JWT\JWT;

trait ilDeliveryUrlTrait
{
    /*
     * Return the URL depending on the delivery mode. This is either the original URL or
     * the modified URL for the external content server
     *
     * @param string $url the original url
     * @return string the actual deliveryurl based on the selected method
     */
    private function getDeliveryUrl(string $url){
        if ($this->configObject->getDeliveryMethod() == 'api'){
            return $url;
        } else {
            $baseurl = str_replace($this->configObject->getStripUrl(),'',$url);
            $key = $this->configObject->getSigningKey();
            $payload = array(
                "iss" => ILIAS_HTTP_PATH,
                "aud" => $this->configObject->getDistributionServer(),
                "iat" => time(),
                "nbf" => time()-10,
                "exp" => time() + 3600 * $this->configObject->getTokenValidity(),
                "url" => $baseurl
            );
            $token = JWT::encode($payload, $key);
            return $this->configObject->getDistributionServer().$baseurl.'?token='.$token;
        }
    }
}
