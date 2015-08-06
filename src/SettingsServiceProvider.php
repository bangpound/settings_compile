<?php

namespace Drupal\Settings;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Yaml\Parser;

class SettingsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['yaml'] = function () {
            return new Parser();
        };

        $pimple['processor'] = function () {
            return new Processor();
        };

        $pimple['schema.settings'] = function () {
            return new Schema();
        };

        $pimple['dumper'] = function (Container $c) {
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
