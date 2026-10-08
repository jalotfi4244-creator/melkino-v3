<?php
declare(strict_types=1);

namespace Melkino\Integrations;

/**
 * Melkino V2 — provider adapter contract (spec §101).
 * Business logic sends through NotifierInterface; no `if telegram/if bale` scattered in code.
 */
interface NotifierInterface
{
    public function name(): string;
    public function available(): bool;

    /** @param array<string,mixed> $options */
    public function send(string $to, string $message, array $options = []): bool;
}
