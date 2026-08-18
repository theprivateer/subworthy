<?php

namespace App\Reader;

use InvalidArgumentException;

/**
 * Feed URLs are supplied by users (the subscribe form) and by third-party OPML files, and
 * the application fetches them server-side. Without a check, that turns the app into a proxy
 * for reaching anything the host can reach — cloud metadata endpoints, internal admin panels,
 * databases bound to loopback.
 *
 * This guard is applied in GuzzleClient rather than at the call sites, because that is the
 * single chokepoint every Laminas feed fetch passes through, redirects included.
 */
class OutboundUrlGuard
{
    /**
     * Ranges that filter_var's NO_PRIV_RANGE / NO_RES_RANGE flags do not cover.
     *
     * @var array<int, array{0: string, 1: int}>
     */
    private const EXTRA_BLOCKED_V4 = [
        ['100.64.0.0', 10],  // RFC 6598 carrier-grade NAT
        ['192.0.0.0', 24],   // RFC 6890 IETF protocol assignments
        ['198.18.0.0', 15],  // RFC 2544 benchmarking
    ];

    /**
     * @throws InvalidArgumentException when the URL must not be fetched.
     */
    public static function assertFetchable(string $url): void
    {
        // Enforcement resolves DNS for every fetch, which makes it both slow and dependent on
        // the network — unusable against the fake hostnames the test factories generate. The
        // switch exists so the test suite stays deterministic; it defaults to on and should
        // never be turned off outside testing. Guard behaviour itself is covered by
        // OutboundUrlGuardTest, which enables it explicitly.
        if (! config('feeds.block_private_urls', true)) {
            return;
        }

        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'])) {
            throw new InvalidArgumentException('The feed URL is not a valid absolute URL.');
        }

        // Scheme is checked before host so that file:///etc/passwd, which parses to no host
        // at all, is reported as an unsupported scheme rather than a malformed URL.
        $scheme = strtolower($parts['scheme']);

        if (! in_array($scheme, ['http', 'https'], true)) {
            throw new InvalidArgumentException("The feed URL scheme '{$scheme}' is not supported.");
        }

        if (! isset($parts['host']) || $parts['host'] === '') {
            throw new InvalidArgumentException('The feed URL is not a valid absolute URL.');
        }

        $host = trim($parts['host'], '[]');

        foreach (self::resolve($host) as $ip) {
            if (! self::isPubliclyRoutable($ip)) {
                throw new InvalidArgumentException(
                    'The feed URL resolves to an address that is not publicly routable.'
                );
            }
        }
    }

    public static function isFetchable(string $url): bool
    {
        try {
            self::assertFetchable($url);

            return true;
        } catch (InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Every address a host resolves to is checked, not just the first — a name that returns
     * one public and one loopback record must not pass on the strength of the public one.
     *
     * @return array<int, string>
     */
    private static function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $records = @dns_get_record($host, DNS_A | DNS_AAAA) ?: [];

        $ips = [];

        foreach ($records as $record) {
            $ips[] = $record['ip'] ?? $record['ipv6'] ?? null;
        }

        $ips = array_values(array_filter($ips));

        if ($ips === []) {
            // A host that resolves to nothing cannot be shown to be safe, and letting it
            // through would leave the check dependent on Guzzle's own resolution instead.
            throw new InvalidArgumentException('The feed URL host could not be resolved.');
        }

        return $ips;
    }

    private static function isPubliclyRoutable(string $ip): bool
    {
        $isPublic = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPublic === false) {
            return false;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            foreach (self::EXTRA_BLOCKED_V4 as [$subnet, $bits]) {
                if (self::inV4Subnet($ip, $subnet, $bits)) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function inV4Subnet(string $ip, string $subnet, int $bits): bool
    {
        $mask = -1 << (32 - $bits);

        return (ip2long($ip) & $mask) === (ip2long($subnet) & $mask);
    }
}
