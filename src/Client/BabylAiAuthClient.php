<?php
namespace BabylAI\Client;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\GuzzleException;
use BabylAI\DTO\ClientTokenRequest;
use BabylAI\DTO\ClientTokenResponse;

/**
 * A simple PHP client to call BabylAI’s Auth endpoint and retrieve a client token.
 */
class BabylAiAuthClient
{
    private string $baseUrl;
    private GuzzleClient $httpClient;

    /**
     * @param string|null        $baseUrl     The API base URL (e.g. https://babylai.net/api/).
     * @param GuzzleClient|null  $httpClient  Optionally supply your own Guzzle client.
     */
    public function __construct(?string $baseUrl = null, ?GuzzleClient $httpClient = null)
    {
        $this->baseUrl = rtrim($baseUrl ?? 'https://babylai.net/api/', '/') . '/';
        $this->httpClient = $httpClient
            ?? new GuzzleClient([ 'base_uri' => $this->baseUrl ]);
    }

    /**
     * Calls POST /Auth/client/get-token with tenantId & apiKey, returning a ClientTokenResponse.
     *
     * @param string $tenantId A GUID/UUID string.
     * @param string $apiKey   Your API key.
     * @return ClientTokenResponse
     * @throws \Exception If HTTP fails or response is invalid.
     */
    public function getClientToken(string $tenantId, string $apiKey): ClientTokenResponse
    {
        // Build request DTO:
        $requestDto = new ClientTokenRequest($tenantId, $apiKey);

        try {
            $response = $this->httpClient->post('Auth/client/get-token', [
                'json' => $requestDto->toArray(),
                // If the API expects a specific content-type, you could also set:
                // 'headers' => ['Accept' => 'application/json']
            ]);
        } catch (GuzzleException $e) {
            throw new \Exception(
                "HTTP error when fetching BabylAI client token: " . $e->getMessage(),
                0,
                $e
            );
        }

        $statusCode = $response->getStatusCode();
        $body       = (string) $response->getBody();

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new \Exception(
                "Failed to fetch token. Status code: {$statusCode}. Response: {$body}"
            );
        }

        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new \Exception(
                "Invalid JSON in BabylAI response: {$body}"
            );
        }

        if (!isset($decoded['token'], $decoded['expiresIn'])) {
            throw new \Exception(
                "Missing fields in BabylAI response: {$body}"
            );
        }

        return new ClientTokenResponse(
            (string) $decoded['token'],
            (int)    $decoded['expiresIn']
        );
    }
}
