<?php

namespace Drupal\Settings;

use Composer\Script\Event;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Config\Loader\DelegatingLoader;
use Symfony\Component\Config\Loader\LoaderResolver;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class ScriptHandler
{
    public static function buildSettings(Event $event)
    {
        $cwd = getcwd();
        $options = self::getOptions($event);

        $targetDir = realpath($options['drupal-root']);
        $configFile = $cwd.'/'.$options['drupal-config'];

        $sitesDir = $targetDir.'/sites';
        $config = array();

        $locator = new FileLocator(array(dirname($configFile)));
        $resolver = new LoaderResolver(array(
          new YamlFileLoader($locator),
        ));

        $loader = new DelegatingLoader($resolver);

        $config = array_merge($config, $loader->load($configFile));

        $processor = new Processor();
        $processor->processConfiguration(new Schema(), $config);

        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->registerProvider(new ExpressionFunctionProvider());

        $dumper = new PhpDumper($expressionLanguage);

        $code = $dumper->dump($config['drupal']);

        file_put_contents($sitesDir.'/default/settings.php', $code);
    }

    protected static function getOptions(Event $event)
    {
        $options = array_merge(
          array(
            'drupal-root' => '',
            'drupal-config' => 'config/config.yml',
          ),
          $event->getComposer()->getPackage()->getExtra()
        );

        return $options;
    }
}
