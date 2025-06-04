<?php
namespace BabylAI\DTO;

/**
 * Represents the request payload for obtaining a client token.
 */
class ClientTokenRequest
{
    /** @var string A GUID/UUID string for the tenant. */
    private string $tenantId;

    /** @var string The API key provided by BabylAI. */
    private string $apiKey;

    public function __construct(string $tenantId, string $apiKey)
    {
        $this->tenantId = $tenantId;
        $this->apiKey   = $apiKey;
    }

    /**
     * Convert to an associative array for JSON serialization.
     */
    public function toArray(): array
    {
        return [
            'TenantId' => $this->tenantId,
            'ApiKey'   => $this->apiKey
        ];
    }
}
