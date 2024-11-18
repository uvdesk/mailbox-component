<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\IMAP;

use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\AppTransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\DefaultTransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\ResolvedTransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\SimpleTransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\IMAP\Transport\TransportConfigurationInterface;

final class Configuration
{
    private static $reflectedDefinitions = [];

    private function __construct()
    {
        // Force prevent instantiation
    }

    private static function getAvailableDefinitions(bool $ignoreCache = false): array
    {
        if (empty(self::$reflectedDefinitions) || true === $ignoreCache) {
            if (true === $ignoreCache) {
                // Since we are ignoring the cached results we'll reset this to an empty collection.
                self::$reflectedDefinitions = [];
            }

            // Scan all php files within the target namespace directory
            $scannedFiles = array_filter(scandir(__DIR__ . "/Transport/Type"), function($path) {
                return ('.' != $path && '..' != $path) ? (bool) ('php' === substr($path, strpos($path, '.') + 1)) : false;
            });

            // Filter invalid\unsupported classes
            foreach ($scannedFiles as $fileName) {
                $classPath = sprintf("%s\Transport\Type\%s", __NAMESPACE__, substr($fileName, 0, strpos($fileName, '.')));
    
                try {
                    $reflectionClass = new \ReflectionClass($classPath);
    
                    if ($reflectionClass->isInstantiable() && $reflectionClass->implementsInterface(TransportConfigurationInterface::class)) {
                        self::$reflectedDefinitions[] = $reflectionClass;
                    }
                } catch (\ReflectionException $exception) {
                    continue;
                } catch (\RuntimeException $exception) {
                    continue;
                }
            }
        }

        return self::$reflectedDefinitions;
    }

    public static function getSupportedTransportTypes(): array
    {
        return array_map(function ($reflectionClass) {
            return $reflectionClass->getName()::getCode();
        }, self::getAvailableDefinitions());
    }

    public static function guessTransportDefinition(array $params): TransportConfigurationInterface
    {
        foreach (self::getAvailableDefinitions() as $reflectedImapDefinition) {
            // Use default configuration only when no other transport type matches the provided configs
            if (true === $reflectedImapDefinition->implementsInterface(DefaultTransportConfigurationInterface::class)) {
                $defaultConfigurationReflection = $reflectedImapDefinition;

                continue;
            }

            $imapInstance = $reflectedImapDefinition->newInstance();

            if ($imapInstance instanceof AppTransportConfigurationInterface || $imapInstance instanceof SimpleTransportConfigurationInterface) {
                if (empty($params['host'])) {
                    return $imapInstance;
                }
            } else if (!empty($params['host']) && $imapInstance->getHost() == $params['host']) {
                return $imapInstance;
            }
        }

        if (!empty($defaultConfigurationReflection)) {
            return $defaultConfigurationReflection->newInstance($params['host']);
        }

        throw new \Exception('No matching imap definition found for host address "' . $params['host'] . '".');
    }

    public static function createTransportDefinition($transportCode, $host = null): TransportConfigurationInterface
    {
        if (false == in_array($transportCode, self::getSupportedTransportTypes(), true)) {
            throw new \Exception('No imap definition found for transport type "' . $transportCode . '".');
        }

        foreach (self::getAvailableDefinitions() as $reflectedImapDefinition) {
            if ($reflectedImapDefinition->getName()::getCode() !== $transportCode) {
                continue;
            }

            if (true === $reflectedImapDefinition->implementsInterface(DefaultTransportConfigurationInterface::class)) {
                return $reflectedImapDefinition->newInstance($host);
            }

            return $reflectedImapDefinition->newInstance();
        }
    }
}
