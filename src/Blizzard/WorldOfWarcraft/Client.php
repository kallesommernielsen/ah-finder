<?php

declare(strict_types=1);

namespace Blizzard\WorldOfWarcraft;

class Client
{
    protected \CurlHandle $curlHandle;

    public function __construct(
        #[\SensitiveParameter] string $clientId,
        #[\SensitiveParameter] string $clientSecret,
        protected readonly Region $region,
        protected readonly ?Locale $locale = null,
    ) {
        if ($this->locale !== null && !\in_array($this->locale, $this->region->locales())) {
            throw new \UnexpectedValueException('Locale is not available in this region');
        }

        $this->curlHandle = \curl_init();

        \curl_setopt(
            $this->curlHandle,
            \CURLOPT_RETURNTRANSFER,
            true,
        );

        $this->getToken($clientId, $clientSecret);
    }

    protected function getToken(
        #[\SensitiveParameter] string $clientId = '',
        #[\SensitiveParameter] string $clientSecret = '',
    ): string {
        static $token = null;

        if ($token !== null) {
            return $token;
        }

        $curlAuthHandle = clone $this->curlHandle;

        \curl_setopt(
            $curlAuthHandle,
            \CURLOPT_URL,
            'https://oauth.battle.net/token',
        );

        \curl_setopt(
            $curlAuthHandle,
            \CURLOPT_POSTFIELDS,
            'grant_type=client_credentials',
        );

        \curl_setopt(
            $curlAuthHandle,
            \CURLOPT_USERPWD,
            \sprintf(
                '%s:%s',
                $clientId,
                $clientSecret,
            ),
        );

        $returnValue = \curl_exec($curlAuthHandle);

        if (!\is_string($returnValue) || !\json_validate($returnValue)) {
            throw new \RuntimeException('Unable to obtain oauth token');
        }

        $returnValue = \json_decode($returnValue);

        if (!$returnValue instanceof \stdClass) {
            throw new \RuntimeException('Unable to decode oauth token response');
        }

        return $token = $returnValue->access_token;
    }

    protected function getLocale(): Locale
    {
        return $this->locale ?? $this->region->defaultLocale();
    }

    protected function request(
        Endpoint $endpoint,
        array $arguments = [],
        array $query = [],
        array $headers = [],
        bool $decode = true,
    ): \stdClass|string {
        $query['locale'] ??= $this->getLocale()->format();
        $headers['Authorization'] = \sprintf(
            'Bearer %s',
            $this->getToken(),
        );

        \curl_setopt(
            $this->curlHandle,
            \CURLOPT_URL,
            \sprintf(
                '%s%s?%s',
                $this->region->host(),
                \sizeof($arguments)
                    ? \sprintf($endpoint->value, ...$arguments)
                    : $endpoint->value,
                \http_build_query(
                    data: $query,
                    arg_separator: '&',
                ),
            ),
        );

        \curl_setopt(
            $this->curlHandle,
            \CURLOPT_HTTPHEADER,
            \array_map(
                static fn(string $k, string $v): string => \sprintf(
                    '%s: %s',
                    $k,
                    $v,
                ),
                \array_keys($headers),
                \array_values($headers),
            ),
        );

        $returnValue = \curl_exec($this->curlHandle);

        if ($decode) {
            if (!\is_string($returnValue) || !\json_validate($returnValue)) {
                throw new \RuntimeException('Unable to complete request');
            }

            $returnValue = \json_decode($returnValue);

            if (!$returnValue instanceof \stdClass) {
                throw new \RuntimeException('Unable to decode response');
            }
        } elseif ($returnValue === false) {
            throw new \RuntimeException('Unable to complete request');
        }

        return $returnValue;
    }

    public function getRealms(): \stdClass
    {
        return $this->request(
            endpoint: Endpoint::REALMS_INDEX,
            query: [
                'namespace' => $this->region->dynamicNamespace(),
            ],
        );
    }

    public function getConnectedRealms(): \stdClass
    {
        return $this->request(
            endpoint: Endpoint::CONNECTED_REALM_INDEX,
            query: [
                'namespace' => $this->region->dynamicNamespace(),
            ],
        );
    }

    public function getConnectedRealm(int $connectedRealmId): \stdClass
    {
        return $this->request(
            endpoint: Endpoint::CONNECTED_REALM,
            arguments: [
                $connectedRealmId,
            ],
            query: [
                'namespace' => $this->region->dynamicNamespace(),
            ],
        );
    }

    public function getAuctions(int $connectedRealmId): string
    {
        return $this->request(
            endpoint: Endpoint::AUCTIONS,
            arguments: [
                $connectedRealmId,
            ],
            query: [
                'namespace' => $this->region->dynamicNamespace(),
            ],
            decode: false,
        );
    }
}
