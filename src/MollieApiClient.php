<?php

namespace Mollie\Api;

use _PhpScoper5b6d7f96f14c0\GuzzleHttp\Client;
use _PhpScoper5b6d7f96f14c0\GuzzleHttp\ClientInterface;
use _PhpScoper5b6d7f96f14c0\GuzzleHttp\Exception\GuzzleException;
use _PhpScoper5b6d7f96f14c0\GuzzleHttp\Psr7\Request;
use Mollie\Api\Endpoints\CustomerEndpoint;
use Mollie\Api\Endpoints\CustomerPaymentsEndpoint;
use Mollie\Api\Endpoints\InvoiceEndpoint;
use Mollie\Api\Endpoints\MandateEndpoint;
use Mollie\Api\Endpoints\MethodEndpoint;
use Mollie\Api\Endpoints\PaymentEndpoint;
use Mollie\Api\Endpoints\PaymentRefundEndpoint;
use Mollie\Api\Endpoints\ProfileEndpoint;
use Mollie\Api\Endpoints\RefundEndpoint;
use Mollie\Api\Endpoints\SettlementsEndpoint;
use Mollie\Api\Endpoints\SubscriptionEndpoint;
use Mollie\Api\Exceptions\ApiException;
use Mollie\Api\Exceptions\IncompatiblePlatform;
use _PhpScoper5b6d7f96f14c0\Psr\Http\Message\ResponseInterface;
use _PhpScoper5b6d7f96f14c0\Psr\Http\Message\StreamInterface;
class MollieApiClient
{
    /**
     * Version of our client.
     */
    const CLIENT_VERSION = '2.0.12';
    /**
     * Endpoint of the remote API.
     */
    const API_ENDPOINT = "https://api.mollie.com";
    /**
     * Version of the remote API.
     */
    const API_VERSION = "v2";
    /**
     * HTTP Methods
     */
    const HTTP_GET = "GET";
    const HTTP_POST = "POST";
    const HTTP_DELETE = "DELETE";
    const HTTP_PATCH = "PATCH";
    /**
     * HTTP status codes
     */
    const HTTP_NO_CONTENT = 204;
    /**
     * @var ClientInterface
     */
    protected $httpClient;
    /**
     * @var string
     */
    protected $apiEndpoint = self::API_ENDPOINT;
    /**
     * RESTful Payments resource.
     *
     * @var PaymentEndpoint
     */
    public $payments;
    /**
     * RESTful Methods resource.
     *
     * @var MethodEndpoint
     */
    public $methods;
    /**
     * RESTful Customers resource.
     *
     * @var CustomerEndpoint
     */
    public $customers;
    /**
     * RESTful Customer payments resource.
     *
     * @var CustomerPaymentsEndpoint
     */
    public $customerPayments;
    /**
     * @var SettlementsEndpoint
     */
    public $settlements;
    /**
     * RESTful Subscription resource.
     *
     * @var SubscriptionEndpoint
     */
    public $subscriptions;
    /**
     * RESTful Mandate resource.
     *
     * @var MandateEndpoint
     */
    public $mandates;
    /**
     * @var ProfileEndpoint
     */
    public $profiles;
    /**
     * RESTful Invoice resource.
     *
     * @var InvoiceEndpoint
     */
    public $invoices;
    /**
     * RESTful Refunds resource.
     *
     * @var RefundEndpoint
     */
    public $refunds;
    /**
     * RESTful Refunds resource.
     *
     * @var PaymentRefundEndpoint
     */
    public $paymentRefunds;
    /**
     * @var string
     */
    protected $apiKey;
    /**
     * True if an OAuth access token is set as API key.
     *
     * @var bool
     */
    protected $oauthAccess;
    /**
     * @var array
     */
    protected $versionStrings = [];
    /**
     * @var int
     */
    protected $lastHttpResponseStatusCode;
    /**
     * @param ClientInterface $httpClient
     *
     * @throws IncompatiblePlatform
     */
    public function __construct(\_PhpScoper5b6d7f96f14c0\GuzzleHttp\ClientInterface $httpClient = null)
    {
        $this->httpClient = $httpClient ? $httpClient : new \_PhpScoper5b6d7f96f14c0\GuzzleHttp\Client([\_PhpScoper5b6d7f96f14c0\GuzzleHttp\RequestOptions::VERIFY => \_PhpScoper5b6d7f96f14c0\Composer\CaBundle\CaBundle::getBundledCaBundlePath()]);
        $compatibilityChecker = new \Mollie\Api\CompatibilityChecker();
        $compatibilityChecker->checkCompatibility();
        $this->initializeEndpoints();
        $this->addVersionString("Mollie/" . self::CLIENT_VERSION);
        $this->addVersionString("PHP/" . \phpversion());
        $this->addVersionString("Guzzle/" . \_PhpScoper5b6d7f96f14c0\GuzzleHttp\ClientInterface::VERSION);
    }
    public function initializeEndpoints()
    {
        $this->payments = new \Mollie\Api\Endpoints\PaymentEndpoint($this);
        $this->methods = new \Mollie\Api\Endpoints\MethodEndpoint($this);
        $this->customers = new \Mollie\Api\Endpoints\CustomerEndpoint($this);
        $this->settlements = new \Mollie\Api\Endpoints\SettlementsEndpoint($this);
        $this->subscriptions = new \Mollie\Api\Endpoints\SubscriptionEndpoint($this);
        $this->customerPayments = new \Mollie\Api\Endpoints\CustomerPaymentsEndpoint($this);
        $this->mandates = new \Mollie\Api\Endpoints\MandateEndpoint($this);
        $this->invoices = new \Mollie\Api\Endpoints\InvoiceEndpoint($this);
        $this->profiles = new \Mollie\Api\Endpoints\ProfileEndpoint($this);
        $this->refunds = new \Mollie\Api\Endpoints\RefundEndpoint($this);
        $this->paymentRefunds = new \Mollie\Api\Endpoints\PaymentRefundEndpoint($this);
    }
    /**
     * @param string $url
     *
     * @return MollieApiClient
     */
    public function setApiEndpoint($url)
    {
        $this->apiEndpoint = \rtrim(\trim($url), '/');
        return $this;
    }
    /**
     * @return string
     */
    public function getApiEndpoint()
    {
        return $this->apiEndpoint;
    }
    /**
     * @param string $apiKey The Mollie API key, starting with 'test_' or 'live_'
     *
     * @return MollieApiClient
     * @throws ApiException
     */
    public function setApiKey($apiKey)
    {
        $apiKey = \trim($apiKey);
        if (!\preg_match('/^(live|test)_\\w{30,}$/', $apiKey)) {
            throw new \Mollie\Api\Exceptions\ApiException("Invalid API key: '{$apiKey}'. An API key must start with 'test_' or 'live_' and must be at least 30 characters long.");
        }
        $this->apiKey = $apiKey;
        $this->oauthAccess = \false;
        return $this;
    }
    /**
     * @param string $accessToken OAuth access token, starting with 'access_'
     *
     * @return MollieApiClient
     * @throws ApiException
     */
    public function setAccessToken($accessToken)
    {
        $accessToken = \trim($accessToken);
        if (!\preg_match('/^access_\\w+$/', $accessToken)) {
            throw new \Mollie\Api\Exceptions\ApiException("Invalid OAuth access token: '{$accessToken}'. An access token must start with 'access_'.");
        }
        $this->apiKey = $accessToken;
        $this->oauthAccess = \true;
        return $this;
    }
    /**
     * Returns null if no API key has been set yet.
     *
     * @return bool|null
     */
    public function usesOAuth()
    {
        return $this->oauthAccess;
    }
    /**
     * @param string $versionString
     *
     * @return MollieApiClient
     */
    public function addVersionString($versionString)
    {
        $this->versionStrings[] = \str_replace([" ", "\t", "\n", "\r"], '-', $versionString);
        return $this;
    }
    /**
     * Perform an http call. This method is used by the resource specific classes. Please use the $payments property to
     * perform operations on payments.
     *
     * @see $payments
     * @see $isuers
     *
     * @param string $httpMethod
     * @param string $apiMethod
     * @param string|null|resource|StreamInterface $httpBody
     *
     * @return object
     * @throws ApiException
     *
     * @codeCoverageIgnore
     */
    public function performHttpCall($httpMethod, $apiMethod, $httpBody = null)
    {
        $url = $this->apiEndpoint . "/" . self::API_VERSION . "/" . $apiMethod;
        return $this->performHttpCallToFullUrl($httpMethod, $url, $httpBody);
    }
    /**
     * Perform an http call to a full url. This method is used by the resource specific classes.
     *
     * @see $payments
     * @see $isuers
     *
     * @param string $httpMethod
     * @param string $url
     * @param string|null|resource|StreamInterface $httpBody
     *
     * @return object|null
     * @throws ApiException
     *
     * @codeCoverageIgnore
     */
    public function performHttpCallToFullUrl($httpMethod, $url, $httpBody = null)
    {
        if (empty($this->apiKey)) {
            throw new \Mollie\Api\Exceptions\ApiException("You have not set an API key or OAuth access token. Please use setApiKey() to set the API key.");
        }
        $userAgent = \implode(' ', $this->versionStrings);
        if ($this->usesOAuth()) {
            $userAgent .= " OAuth/2.0";
        }
        $headers = ['Accept' => "application/json", 'Authorization' => "Bearer {$this->apiKey}", 'User-Agent' => $userAgent];
        if (\function_exists("php_uname")) {
            $headers['X-Mollie-Client-Info'] = \php_uname();
        }
        $request = new \_PhpScoper5b6d7f96f14c0\GuzzleHttp\Psr7\Request($httpMethod, $url, $headers, $httpBody);
        try {
            $response = $this->httpClient->send($request, ['http_errors' => \false]);
        } catch (\_PhpScoper5b6d7f96f14c0\GuzzleHttp\Exception\GuzzleException $e) {
            throw new \Mollie\Api\Exceptions\ApiException($e->getMessage(), $e->getCode(), $e);
        }
        if (!$response) {
            throw new \Mollie\Api\Exceptions\ApiException("Did not receive API response.");
        }
        return $this->parseResponseBody($response);
    }
    /**
     * Parse the PSR-7 Response body
     *
     * @param ResponseInterface $response
     * @return object|null
     * @throws ApiException
     */
    private function parseResponseBody(\_PhpScoper5b6d7f96f14c0\Psr\Http\Message\ResponseInterface $response)
    {
        $body = $response->getBody()->getContents();
        if (empty($body)) {
            if ($response->getStatusCode() === self::HTTP_NO_CONTENT) {
                return null;
            }
            throw new \Mollie\Api\Exceptions\ApiException("No response body found.");
        }
        $object = @\json_decode($body);
        if (\json_last_error() !== \JSON_ERROR_NONE) {
            throw new \Mollie\Api\Exceptions\ApiException("Unable to decode Mollie response: '{$body}'.");
        }
        if ($response->getStatusCode() >= 400) {
            $field = null;
            if (!empty($object->field)) {
                $field = $object->field;
            }
            $documentationUrl = null;
            if (!empty($object->_links) && !empty($object->_links->documentation)) {
                $documentationUrl = $object->_links->documentation->href;
            }
            throw new \Mollie\Api\Exceptions\ApiException("Error executing API call ({$object->status}: {$object->title}): {$object->detail}", $response->getStatusCode(), $field, $documentationUrl);
        }
        return $object;
    }
    /**
     * Serialization can be used for caching. Of course doing so can be dangerous but some like to live dangerously.
     *
     * \serialize() should be called on the collections or object you want to cache.
     *
     * We don't need any property that can be set by the constructor, only properties that are set by setters.
     *
     * Note that the API key is not serialized, so you need to set the key again after unserializing if you want to do
     * more API calls.
     *
     * @deprecated
     * @return string[]
     */
    public function __sleep()
    {
        return ["apiEndpoint"];
    }
    /**
     * When unserializing a collection or a resource, this class should restore itself.
     *
     * Note that if you use a custom GuzzleClient, this client is lost. You can't re set the Client, so you should
     * probably not use this feature.
     *
     * @throws IncompatiblePlatform If suddenly unserialized on an incompatible platform.
     */
    public function __wakeup()
    {
        $this->__construct();
    }
}
