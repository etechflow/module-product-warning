<?php

declare(strict_types=1);

namespace ETechFlow\ProductWarning\Model;

use Magento\Framework\App\Cache\Type\Config as ConfigCacheType;
use Magento\Framework\App\CacheInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Config\Storage\WriterInterface;
use Magento\Framework\HTTP\Client\Curl;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * License validation for ETechFlow_ProductWarning.
 *
 * Hybrid model — follows LICENSING_PROTOCOL.md:
 *   - SP-XXXX keys  -> portal validation (domain + server IP must match).
 *   - HMAC keys     -> local HMAC-SHA256 per-module key OR shared bundle key.
 *   - "Production Environment = No" bypasses licensing for dev/staging.
 *   - Common dev hostnames auto-detect and bypass.
 *
 * Enforcement contract (feedback_etechflow_portal_enforcement_semantics):
 *   validateViaPortal() returns ?bool:
 *     true   = portal valid
 *     false  = explicit reject (200+valid:false, 401, 403) -> lock IMMEDIATELY, no grace
 *     null   = portal unreachable (network error, exception, 0, 5xx) -> fall back to 48h grace
 *   Cache: VALID=60s, REJECT=60s, unreachable NOT cached.
 *
 * IP-block auto-management (portal SP- keys only):
 *   portal 403 + ip_blocked:true -> clearLicenseKey() (key='' + ip_blocked='1').
 *   IP restored -> portal returns valid -> writeLicenseKey() restores key from
 *   issued_key + resets ip_blocked='0'. The issued_key fallback ONLY fires
 *   when ip_blocked=1, so manually clearing the key keeps the module locked.
 */
class LicenseValidator
{
    public const XML_PATH_LICENSE_KEY            = 'etechflow_productwarning/license/license_key';
    public const XML_PATH_ISSUED_KEY             = 'etechflow_productwarning/license/issued_key';
    public const XML_PATH_ISSUED_AT              = 'etechflow_productwarning/license/issued_at';
    public const XML_PATH_IP_BLOCKED             = 'etechflow_productwarning/license/ip_blocked';
    public const XML_PATH_PORTAL_URL             = 'etechflow_productwarning/license/portal_url';
    public const XML_PATH_PRODUCTION_ENVIRONMENT = 'etechflow_productwarning/license/production_environment';

    /** Shared bundle config path — same value across all eTechFlow modules. */
    public const XML_PATH_BUNDLE_LICENSE_KEY = 'etechflow_bundle/license/license_key';

    private const DEFAULT_PORTAL_URL   = 'https://subpanel-paralyses-president.ngrok-free.dev/license/validate';
    public  const PORTAL_CACHE_TTL     = 60;
    public  const PORTAL_CACHE_TTL_BAD = 60;

    private const CACHE_TAG    = 'ETECHFLOW_PW';
    private const CACHE_PREFIX = 'etf_pw_lic_';

    /** Per-module HMAC identity. Preserve byte-for-byte from prior v1.0.x to keep existing keys valid. */
    private const MODULE_ID = 'product-warning';

    private const SECRET_FRAGMENTS = [
        'eTF-PW-2026',
        'a3R7-kY8v',
        'N9hT-qB4j',
        'X2fL-mP5c',
    ];

    /** Shared bundle HMAC. MUST stay byte-identical across all eTechFlow modules. */
    private const BUNDLE_ID = 'etechflow-bundle';

    private const BUNDLE_SECRET_FRAGMENTS = [
        'eTF-BUNDLE-2026',
        'k2D9-mP4x',
        'L8nR-vH2j',
        'X7tY-zW5q',
    ];

    public function __construct(
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly StoreManagerInterface $storeManager,
        private readonly CacheInterface $cache,
        private readonly Curl $curl,
        private readonly WriterInterface $configWriter
    ) {
    }

    public function isValid(): bool
    {
        $host = $this->getCurrentHost();
        if ($host === '') {
            return false;
        }
        if (!$this->isProductionEnvironment()) {
            return true;
        }
        if ($this->isDevelopmentHost($host)) {
            return true;
        }
        return $this->checkKey($host);
    }

    public function computeKey(string $host): string
    {
        $payload = $this->canonicalize($host) . ':' . self::MODULE_ID;
        $secret  = implode('', self::SECRET_FRAGMENTS);
        $raw     = hash_hmac('sha256', $payload, $secret, true);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public function computeBundleKey(string $host): string
    {
        $payload = $this->canonicalize($host) . ':' . self::BUNDLE_ID;
        $secret  = implode('', self::BUNDLE_SECRET_FRAGMENTS);
        $raw     = hash_hmac('sha256', $payload, $secret, true);
        return rtrim(strtr(base64_encode($raw), '+/', '-_'), '=');
    }

    public function canonicalize(string $host): string
    {
        $host = strtolower(trim($host));
        if (str_starts_with($host, 'www.')) {
            $host = substr($host, 4);
        }
        return $host;
    }

    public function getConfiguredKey(): string
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_LICENSE_KEY, ScopeInterface::SCOPE_STORE);
        return trim((string) $value);
    }

    public function getConfiguredBundleKey(): string
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_BUNDLE_LICENSE_KEY, ScopeInterface::SCOPE_STORE);
        return trim((string) $value);
    }

    public function isProductionEnvironment(): bool
    {
        $value = $this->scopeConfig->getValue(self::XML_PATH_PRODUCTION_ENVIRONMENT, ScopeInterface::SCOPE_STORE);
        if ($value === null || $value === '') {
            return true;
        }
        return (bool) $value;
    }

    public function getPortalUrl(): string
    {
        $value = trim((string) $this->scopeConfig->getValue(self::XML_PATH_PORTAL_URL));
        return $value !== '' ? $value : self::DEFAULT_PORTAL_URL;
    }

    public function getCurrentHost(): string
    {
        try {
            $url  = $this->storeManager->getStore()->getBaseUrl();
            $host = parse_url($url, PHP_URL_HOST);
            return is_string($host) ? strtolower($host) : '';
        } catch (\Exception) {
            return '';
        }
    }

    public function isDevHost(?string $host = null): bool
    {
        $check = $host !== null
            ? $this->canonicalize($host)
            : $this->canonicalize($this->getCurrentHost());
        return $this->isDevelopmentHost($check);
    }

    private function checkKey(string $host): bool
    {
        $configuredKey = $this->getConfiguredKey();
        $isEmptyKey    = ($configuredKey === '');

        if ($isEmptyKey) {
            $ipBlocked = (int) $this->scopeConfig->getValue(self::XML_PATH_IP_BLOCKED);
            if ($ipBlocked !== 1) {
                return false;
            }
            $configuredKey = trim((string) $this->scopeConfig->getValue(self::XML_PATH_ISSUED_KEY));
            if ($configuredKey === '') {
                return false;
            }
        }

        if (str_starts_with($configuredKey, 'SP-')) {
            $portalAnswer = $this->validateViaPortal($host, $configuredKey);
            if ($portalAnswer === true) {
                if ($isEmptyKey) {
                    $this->writeLicenseKey($configuredKey);
                }
                return true;
            }
            if ($portalAnswer === false) {
                return false;
            }
            return !$isEmptyKey && $this->isLocallyIssuedKey($configuredKey, $host);
        }

        if ($configuredKey !== '' && hash_equals($this->computeKey($host), $configuredKey)) {
            return true;
        }
        $bundleKey = $this->getConfiguredBundleKey();
        if ($bundleKey !== '' && hash_equals($this->computeBundleKey($host), $bundleKey)) {
            return true;
        }
        return false;
    }

    private function isLocallyIssuedKey(string $key, string $host): bool
    {
        $issuedAt = (int) $this->scopeConfig->getValue(self::XML_PATH_ISSUED_AT);
        if ($issuedAt === 0) {
            return false;
        }
        if ((time() - $issuedAt) > 172800) {
            return false;
        }
        return trim((string) $this->scopeConfig->getValue(self::XML_PATH_ISSUED_KEY)) === $key;
    }

    private function validateViaPortal(string $host, string $key): ?bool
    {
        $cacheKey = self::CACHE_PREFIX . md5($host . ':' . $key);
        $cached   = $this->cache->load($cacheKey);
        if ($cached === '1') {
            return true;
        }
        if ($cached === '0') {
            return false;
        }

        $url = $this->getPortalUrl()
            . '?domain=' . urlencode($host)
            . '&license_key=' . urlencode($key)
            . '&platform=magento&module=' . self::MODULE_ID;

        $status = 0;
        $body   = '';
        try {
            $this->curl->setTimeout(10);
            $this->curl->addHeader('Accept', 'application/json');
            $this->curl->addHeader('User-Agent', 'ETechFlow-ProductWarning/1.1');
            $this->curl->get($url);
            $status = (int) $this->curl->getStatus();
            $body   = (string) $this->curl->getBody();
        } catch (\Throwable) {
            return null;
        }

        if ($status === 200 && $body !== '') {
            $data  = json_decode($body, true);
            $valid = !empty($data['valid']);
            $this->cache->save(
                $valid ? '1' : '0',
                $cacheKey,
                [self::CACHE_TAG],
                $valid ? self::PORTAL_CACHE_TTL : self::PORTAL_CACHE_TTL_BAD
            );
            if ($valid) {
                $existing = trim((string) $this->scopeConfig->getValue(self::XML_PATH_ISSUED_KEY));
                if ($existing === '') {
                    try {
                        $this->configWriter->save(self::XML_PATH_ISSUED_KEY, $key);
                        $this->configWriter->save(self::XML_PATH_ISSUED_AT, (string) time());
                        $this->cache->clean([ConfigCacheType::CACHE_TAG]);
                    } catch (\Throwable) {
                    }
                }
            }
            return $valid;
        }

        if ($status === 401 || $status === 403) {
            $data = json_decode($body, true);
            $ipBlocked = is_array($data) && !empty($data['ip_blocked']);
            $this->cache->save('0', $cacheKey, [self::CACHE_TAG], self::PORTAL_CACHE_TTL_BAD);
            if ($ipBlocked) {
                $this->clearLicenseKey();
            }
            return false;
        }

        return null;
    }

    public function clearLicenseKey(): void
    {
        try {
            $current = trim((string) $this->scopeConfig->getValue(
                self::XML_PATH_LICENSE_KEY,
                ScopeInterface::SCOPE_STORE
            ));
            if ($current === '') {
                return;
            }
            $this->configWriter->save(self::XML_PATH_LICENSE_KEY, '');
            $this->configWriter->save(self::XML_PATH_IP_BLOCKED, '1');
            $this->cache->clean([ConfigCacheType::CACHE_TAG]);
        } catch (\Throwable) {
        }
    }

    private function writeLicenseKey(string $key): void
    {
        try {
            $this->configWriter->save(self::XML_PATH_LICENSE_KEY, $key);
            $this->configWriter->save(self::XML_PATH_IP_BLOCKED, '0');
            $this->cache->clean([ConfigCacheType::CACHE_TAG]);
        } catch (\Throwable) {
        }
    }

    private function isDevelopmentHost(string $host): bool
    {
        if ($host === 'localhost' || str_starts_with($host, '127.')) {
            return true;
        }
        if (str_starts_with($host, '10.') || str_starts_with($host, '192.168.')) {
            return true;
        }
        if (preg_match('/^172\.(1[6-9]|2[0-9]|3[01])\./', $host)) {
            return true;
        }

        $devSuffixes = ['.test', '.local', '.localhost', '.dev', '.example', '.invalid'];
        foreach ($devSuffixes as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        $devPrefixes = ['staging.', 'stage.', 'dev.', 'qa.', 'uat.', 'test.', 'preview.', 'sandbox.'];
        foreach ($devPrefixes as $prefix) {
            if (str_starts_with($host, $prefix)) {
                return true;
            }
        }

        $cloudSuffixes = ['.magento.cloud', '.magentocloud.com', '.cloud.magento'];
        foreach ($cloudSuffixes as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        $tunnelSuffixes = ['.ngrok.io', '.ngrok-free.app', '.ngrok-free.dev', '.loca.lt', '.serveo.net'];
        foreach ($tunnelSuffixes as $suffix) {
            if (str_ends_with($host, $suffix)) {
                return true;
            }
        }

        return false;
    }
}
