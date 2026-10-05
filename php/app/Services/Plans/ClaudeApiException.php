<?php

namespace App\Services\Plans;

use Exception;
use Illuminate\Http\Client\Response;

/** Foutmelding van de API van de AI (HTTP-status en fouttype uit het antwoord, of een fout midden in de stream). */
class ClaudeApiException extends Exception
{
    public function __construct(public readonly ?int $status, public readonly ?string $type = null, string $message = '')
    {
        parent::__construct($message !== '' ? $message : 'Fout van de AI-API ('.($status ?? 'onbekend').($type ? ", {$type}" : '').')');
    }

    public static function fromResponse(Response $response): self
    {
        $json = json_decode($response->body(), true);
        $error = is_array($json) && is_array($json['error'] ?? null) ? $json['error'] : [];

        return new self($response->status(), is_string($error['type'] ?? null) ? $error['type'] : null, is_string($error['message'] ?? null) ? $error['message'] : '');
    }

    /** De API is (tijdelijk) te druk: rate limit of overbelast. */
    public function overloaded(): bool
    {
        return $this->status === 429 || $this->status === 529 || in_array($this->type, ['rate_limit_error', 'overloaded_error'], true);
    }
}
