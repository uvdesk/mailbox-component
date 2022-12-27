<?php

namespace Webkul\UVDesk\MailboxBundle\Utils\SMTP;

use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\AppTransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\DefaultTransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\ResolvedTransportConfigurationInterface;
use Webkul\UVDesk\MailboxBundle\Utils\SMTP\Transport\TransportConfigurationInterface;

final class Configuration
{
    private static $reflectedDefinitions = [];

    private function __construct()
    {
        // Force prevent instantiation
    }

    private static function getAvailableDefinitions(bool $ignoreCache = false) : array
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

    public static function getSupportedTransportTypes() : array
    {
        return array_map(function ($reflectionClass) {
            return $reflectionClass->getName()::getCode();
        }, self::getAvailableDefinitions());
    }

    public static function guessTransportDefinition(array $params): TransportConfigurationInterface
    {
        foreach (self::getAvailableDefinitions() as $reflectedSmtpDefinition) {
            // Use default configuration only when no other transport type matches the provided configs
            if (true === $reflectedSmtpDefinition->implementsInterface(DefaultTransportConfigurationInterface::class)) {
                $defaultConfigurationReflection = $reflectedSmtpDefinition;

                continue;
            }

            $smtpInstance = $reflectedSmtpDefinition->newInstance();

            if ($smtpInstance instanceof AppTransportConfigurationInterface) {
                if (empty($params['host']) && !empty($params['type']) && $smtpInstance->getType() == $params['type']) {
                    return $smtpInstance;
                }
            } else if (!empty($params['host']) && $smtpInstance->getHost() == $params['host']) {
                return $smtpInstance;
            }
        }

        if (!empty($defaultConfigurationReflection)) {
            return $defaultConfigurationReflection->newInstance();
        }

        throw new \Exception('No matching smtp definition found for host address "' . $params['host'] . '".');
    }

    public static function createTransportDefinition($transportCode, $host = null): TransportConfigurationInterface
    {
        if (false == in_array($transportCode, self::getSupportedTransportTypes(), true)) {
            throw new \Exception('No smtp definition found for transport type "' . $transportCode . '".');
        }

        foreach (self::getAvailableDefinitions() as $reflectionClass) {
            if ($reflectionClass->getName()::getCode() !== $transportCode) {
                continue;
            }

            // if (true === $reflectedSmtpDefinition->implementsInterface(DefaultTransportConfigurationInterface::class)) {
            //     return $reflectedSmtpDefinition->newInstance($host);
            // }

            return $reflectionClass->newInstance();
        }
    }
}
