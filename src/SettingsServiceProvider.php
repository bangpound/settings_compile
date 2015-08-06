<?php

namespace Drupal\Settings;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;
use Symfony\Component\Yaml\Parser;

class SettingsServiceProvider implements ServiceProviderInterface
{
    public function register(Container $pimple)
    {
        $pimple['drupal_settings.config'] = array();

        $pimple['drupal_settings.loader'] = function (Container $c) {
            $locator = new FileLocator($c['drupal_settings.paths']);
            $resolver = new LoaderResolver(array(
              new YamlFileLoader($c, $locator),
            ));

            return new DelegatingLoader($resolver);
        };

        $pimple['drupal_settings.yaml'] = function () {
            return new Parser();
        };

        $pimple['drupal_settings.processor'] = function () {
            return new Processor();
        };

        $pimple['drupal_settings.schema'] = function () {
            return new Schema();
        };

        $pimple['drupal_settings.dumper'] = function (Container $c) {
            return new PhpDumper($c['drupal_settings.expression_language']);
        };

        $pimple['drupal_settings.expression_language'] = function () {
            $language = new ExpressionLanguage();
            $language->registerProvider(new ExpressionFunctionProvider());

            return $language;
        };
    }
}
