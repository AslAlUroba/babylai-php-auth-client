<?php
namespace BabylAI\DTO;

/**
 * Represents the response returned by BabylAI Auth API.
 */
class ClientTokenResponse
{
    /** @var string The JWT or token string. */
    private string $token;

    /** @var int Seconds until this token expires. */
    private int $expiresIn;

    public function __construct(string $token, int $expiresIn)
    {
        $this->token     = $token;
        $this->expiresIn = $expiresIn;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getExpiresIn(): int
    {
        return $this->expiresIn;
    }

    /**
     * Create from a decoded response array.
     */
    public static function fromArray(array $data): self
    {
        $token     = $data['Token']     ?? '';
        $expiresIn = $data['ExpiresIn'] ?? 0;
        return new self($token, $expiresIn);
    }
}
