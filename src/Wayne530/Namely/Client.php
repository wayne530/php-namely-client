<?php

namespace Wayne530\Namely;

use GuzzleHttp;

/**
 * Namely v1 API client
 *
 * @package Wayne530\Namely
 */
class Client {

    /** @var array  client instances, keyed by bearer token */
    private static $instances = [];

    /** @var string  api base url, e.g. https://company.namely.com/ */
    protected $baseUrl;

    /** @var string  api token */
    protected $token;

    /** @var string  api version */
    protected $version = 'v1';

    /** @var GuzzleHttp\Client */
    protected $guzzle;


    /**
     * generate instance key for a base url and api token
     *
     * @param string $baseUrl  base url
     * @param string $token  api token
     *
     * @return string  instance key
     */
    private static function getInstanceKey($baseUrl, $token) {
        $token = mb_strtolower(trim($token));
        $baseUrl = mb_strtolower(trim($baseUrl));
        return md5($baseUrl . ':' . $token);
    }

    /**
     * singleton factory
     *
     * @param string $baseUrl  base url for namely api
     * @param string $token  API token
     *
     * @return Client
     */
    public static function getInstance($baseUrl, $token) {
        $instanceKey = self::getInstanceKey($baseUrl, $token);
        if (! isset(self::$instances[$instanceKey])) {
            self::$instances[$instanceKey] = new Client($baseUrl, $token);
        }
        return self::$instances[$instanceKey];
    }

    /**
     * protected constructor; @see Client::getInstance()
     *
     * @inheritdoc
     */
    protected function __construct($baseUrl, $token) {
        $this->baseUrl = $baseUrl;
        $this->token = $token;
        $this->guzzle = new GuzzleHttp\Client();
    }

    /**
     * make the request
     *
     * @throws RequestException  if a non-OK status code is returned
     * @throws ResponseExecption  if unable to parse response body as JSON
     *
     * @param string $method  HTTP method, e.g. 'GET', 'POST', etc
     * @param string $uri  request URI
     * @param array $queryParams  (optional) query parameters as key-value pairs
     *
     * @return array  parsed JSON response body
     */
    protected function _request($method, $uri, $queryParams = []) {
        $url = $this->baseUrl . '/api/' . urlencode($this->version) . $uri;
        if (count($queryParams) > 0) {
            $url .= '?' . http_build_query($queryParams);
        }
        $response = $this->guzzle->request($method, $url, [
            'headers' => [
                'Authorization' => 'Bearer ' . $this->token,
            ],
        ]);
        $statusCode = $response->getStatusCode();
        if ($statusCode < 200 || $statusCode > 299) {
            throw new RequestException("$method $url returned response code $statusCode", $statusCode);
        }
        $jsonData = $response->getBody();
        $parsedData = json_decode($jsonData, true);
        if (! is_array($parsedData)) {
            throw new ResponseExecption("Unable to parse response as JSON:\n" . $jsonData);
        }
        return $parsedData;
    }

    /**
     * @link https://developers.namely.com/docs/profilesid
     * @inheritdoc
     */
    public function getProfileById($id) {
        return $this->_request('GET', '/profiles/' . urlencode($id). '.json');
    }

    /**
     * @link https://developers.namely.com/docs/profiles-index
     * @inheritdoc
     *
     * @param array $queryParams  (optional) query parameters:
     *                              limit integer|string  max records to return; or string 'all' for all available records
     *                              after string  id of profile after which to return remainder of available records
     *                              sort string  field to sort results by; only 'first_name', 'last_name', 'job_title' are supported; prepend '-' for descending order
     *                              filter array  keys are fields with corresponding filter values
     */
    public function getProfiles($queryParams = []) {
        return $this->_request('GET', '/profiles.json', $queryParams);
    }

    /**
     * @inheritdoc
     */
    public function getReportById($id) {
        return $this->_request('GET', '/reports/' . urlencode($id) . '.json');
    }

}
