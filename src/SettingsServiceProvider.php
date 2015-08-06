<?php

namespace Drupal\Settings;

use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class SettingsServiceProvider implements \Pimple\ServiceProviderInterface
{

    /**
     * Registers services on the given container.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param \Pimple\Container $pimple A container instance
     */
    public function register(\Pimple\Container $pimple)
    {

        $pimple['yaml'] = function () {
            return new \Symfony\Component\Yaml\Parser();
        };

        $pimple['processor'] = function () {
            return new \Symfony\Component\Config\Definition\Processor();
        };

        $pimple['schema.settings'] = function () {
            return new \Drupal\Settings\Schema();
        };

        $pimple['dumper'] = function (\Pimple\Container $c) {
            return new PhpDumper($c['expression_language']);
        };

        $pimple['expression_language'] = function () {
            $language = new ExpressionLanguage();

            $language->register('conf_path', function () {
                return 'conf_path()';
            }, function (array $values) {
                return conf_path();
            });

            $language->register('basename', function ($path) {
                return sprintf('basename(%s)', $path);
            }, function (array $values, $path) {
                return basename($path);
            });

            $language->register('date_create', function ($time) {
                return sprintf('date_create(%s)', $time);
            }, function (array $values, $time) {
                return date_create($time);
            });

            $language->register('date_format', function ($date, $format) {
                return sprintf('date_format(%s, %s)', $date, $format);
            }, function (array $values, $date, $format) {
                return date_format($date, $format);
            });

            $language->register('ini_set', function ($varname, $newvalue) {
                return sprintf('ini_set(%s, %s)', $varname, $newvalue);
            }, function (array $values, $varname, $newvalue) {
                ini_set($varname, $newvalue);
                return '';
            });

            return $language;
        };
    }
}
