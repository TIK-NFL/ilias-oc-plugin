<?php
namespace TIK_NFL\ilias_oc_plugin\opencast;

use Exception;
use ilLoggerFactory;

/**
 * This class implements the basic REST client to connect to a Opencast server using the api endpoint
 *
 * Require Opencast API version 1.1.0 or higher
 *
 * @author Leon Kiefer <leon.kiefer@tik.uni-stuttgart.de>
 */
class ilOpencastRESTClient
{

    const API_VERSION = "v1.9.0";

    /**
     *
     * @var string
     */
    private $opencastURL;

    /**
     *
     * @var string
     */
    private $opencastAPIUser;

    /**
     *
     * @var string
     */
    private $opencastAPIPassword;

    /**
     * Singleton constructor
     */
    public function __construct(string $opencastURL, string $opencastAPIUser, string $opencastAPIPassword)
    {
        $this->opencastURL = $opencastURL;
        $this->opencastAPIUser = $opencastAPIUser;
        $this->opencastAPIPassword = $opencastAPIPassword;
    }

    /**
     * Do a GET Request of the given url on the Opencast server with basic authorization
     *
     * @param string $url
     * @param array $query
     *            this array gets url encoded
     * @throws Exception
     * @return mixed
     */
    public function get(string $url, array $query = null)
    {
        $queryString = "null";
        if ($query) {
            $queryString = http_build_query($query);
            $requestURL = $this->opencastURL . "$url?$queryString";
        } else {
            $requestURL = $this->opencastURL . $url;
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $requestURL);
        $this->basicAuthentication($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/" . self::API_VERSION . "+json"
        ));

        $response = curl_exec($ch);
        if ($response === FALSE) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (! $httpCode) {
                ilLoggerFactory::getLogger('xmh')->warning(curl_error($ch));
                throw new Exception("error GET request: $requestURL $queryString", 503);
            }
            throw new Exception("error GET request: $requestURL $queryString $httpCode", 500);
        }
        return json_decode($response);
    }

    /**
     * Do a POST Request of the given url on the Opencast Server with basic authorization
     *
     * @param string $url
     * @param array $post
     *            this array gets url encoded
     * @param boolean $returnHttpCode
     * @throws Exception
     * @return mixed
     */
    public function post(string $url, array $post, bool $returnHttpCode = false)
    {
        $post_string = http_build_query($post);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->opencastURL . $url);
        curl_setopt($ch, CURLOPT_POST, count($post));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        $this->basicAuthentication($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/" . self::API_VERSION . "+json"
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === FALSE) {
            if (! $httpCode) {
                ilLoggerFactory::getLogger('xmh')->warning(curl_error($ch));
                throw new Exception("error POST request: $url $post_string", 503);
            }
            throw new Exception("error POST request: $url $post_string $httpCode", 500);
        }

        if ($returnHttpCode) {
            return $httpCode;
        }
        return $response;
    }

    /**
     * Do a multipart POST Request of the given url on the Opencast Server with basic authorization
     *
     * @param string $url
     * @param array $post
     *            CURLOPT_POSTFIELDS
     * @param boolean $returnHttpCode
     * @throws Exception
     * @return mixed
     */
    public function postMultipart(string $url, array $post, bool $returnHttpCode = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->opencastURL . $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        $this->basicAuthentication($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/" . self::API_VERSION . "+json"
        ));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        if ($response === FALSE) {
            $postinfo = print_r($post, true);
            if (! $httpCode) {
                ilLoggerFactory::getLogger('xmh')->warning(curl_error($ch));
                throw new Exception("error multipart POST request: $url $postinfo", 503);
            }
            throw new Exception("error multipart POST request: $url $postinfo $httpCode", 500);
        }

        if ($returnHttpCode) {
            return $httpCode;
        }
        return $response;
    }

    /**
     * Do a PUT Request of the given url on the Opencast Server with Basic Authentication
     *
     * @param string $url
     * @param array $post
     * @param boolean $returnHttpCode
     * @throws Exception
     * @return mixed
     */
    public function put(string $url, array $post, bool $returnHttpCode = false)
    {
        $post_string = http_build_query($post);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->opencastURL . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POST, count($post));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        $this->basicAuthentication($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/" . self::API_VERSION . "+json"
        ));

        $response = curl_exec($ch);
        if ($response === FALSE) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (! $httpCode) {
                ilLoggerFactory::getLogger('xmh')->warning(curl_error($ch));
                throw new Exception("error PUT request: $url $post_string", 503);
            }
            throw new Exception("error PUT request: $url $post_string $httpCode", 500);
        }

        if ($returnHttpCode) {
            return curl_getinfo($ch, CURLINFO_HTTP_CODE);
        }
        return $response;
    }

    /**
     * Do a DELETE Request of the given url on the Opencast Server with Basic Authentication
     *
     * @param string $url
     * @param array $post
     * @throws Exception
     * @return mixed
     */
    public function delete(string $url, array $post = [])
    {
        $post_string = http_build_query($post);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $this->opencastURL . $url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        curl_setopt($ch, CURLOPT_POST, count($post));
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_string);
        $this->basicAuthentication($ch);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FAILONERROR, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            "Accept: application/" . self::API_VERSION . "+json"
        ));

        $response = curl_exec($ch);
        if ($response === FALSE) {
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if (! $httpCode) {
                ilLoggerFactory::getLogger('xmh')->warning(curl_error($ch));
                throw new Exception("error DELETE request: $url $post_string", 503);
            }
            throw new Exception("error DELETE request: $url $post_string $httpCode", 500);
        }

        return $response;
    }

    private function basicAuthentication($ch)
    {
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($ch, CURLOPT_USERPWD, $this->opencastAPIUser . ':' . $this->opencastAPIPassword);
    }

    public function checkOpencast()
    {
        $url = "/api/version";
        try {
            $versionInfo = $this->get($url);
            return in_array(self::API_VERSION, $versionInfo->versions);
        } catch (Exception $e) {
            return false;
        }
    }
}
