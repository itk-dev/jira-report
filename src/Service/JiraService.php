<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Subscriber\Oauth\Oauth1;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;

class JiraService {
  protected $token_storage;
  protected $customer_key;
  protected $pem_path;
  protected $jira_url;

  /**
   * Constructor.
   */
  public function __construct($token_storage, $customer_key, $pem_path, $jira_url) {
    $this->token_storage = $token_storage;
    $this->customer_key = $customer_key;
    $this->pem_path = $pem_path;
    $this->jira_url = $jira_url;
  }

  public function get($path) {
    $stack = HandlerStack::create();
    $token = $this->token_storage->getToken();
    $middleware = $this->setOauth($token);

    $stack->push($middleware);

    $client = new Client([
      'base_uri' => $this->jira_url,
      'handler' => $stack
    ]);

    // Set the "auth" request option to "oauth" to sign using oauth
    try {
      $response = $client->get($path, ['auth' => 'oauth']);

      if ($body = $response->getBody()) {

        return json_decode($body);
      }
    } catch (RequestException $e) {
        throw $e;
    }
  }

  public function setOauth($token) {
    $middleware = new Oauth1([
      'consumer_key'    => $this->customer_key,
      'private_key_file' => $this->pem_path,
      'private_key_passphrase' => '',
      'signature_method' => Oauth1::SIGNATURE_METHOD_RSA,
      'token'           => $token->getAccessToken(),
      'token_secret'    => $token->getTokenSecret(),
    ]);

    return $middleware;
  }
}