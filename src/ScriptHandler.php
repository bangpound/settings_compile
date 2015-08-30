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

        $locator = new FileLocator(array(dirname($configFile)));
        $resolver = new LoaderResolver(array(
          new YamlFileLoader($locator),
        ));

        $loader = new DelegatingLoader($resolver);

        $config = $loader->load($configFile);

        $processor = new Processor();
        $processor->processConfiguration(new Schema(), $config);

        $expressionLanguage = new ExpressionLanguage();
        $expressionLanguage->registerProvider(new ExpressionFunctionProvider());

        $dumper = new PhpDumper($expressionLanguage);

        $sites = array();

        foreach ($config as $k => $value) {
            $code = $dumper->dumpSettings($value);
            file_put_contents($sitesDir.'/'.$k.'/settings.php', $code);

            // Process aliases.
            if (!isset($value['aliases'])) {
                continue;
            }

            foreach ($value['aliases'] as $alias) {
                if (isset($sites[$alias])) {
                    throw new \RuntimeException(sprintf('Alias %s already defined', $alias));
                }
                $sites[$alias] = $k;
            }
        }

        if (!empty($sites)) {
            $code = $dumper->dumpSites($sites);
            file_put_contents($sitesDir.'/sites.php', $code);
        }
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
