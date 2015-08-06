<?php

namespace Drupal\Settings;

use Pimple\Container;
use Symfony\Component\Config\FileLocatorInterface;
use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Yaml\Parser as YamlParser;

class YamlFileLoader extends FileLoader
{
    private $yamlParser;
    private $container;

    public function __construct(Container $container, FileLocatorInterface $locator)
    {
        $this->container = $container;
        parent::__construct($locator);
    }

    public function load($resource, $type = null)
    {
        $path = $this->locator->locate($resource);

        $content = $this->loadFile($path);

        // empty file
        if (null === $content) {
            return;
        }

        // imports
        $this->parseImports($content, $path);

        // extensions
        $this->loadFromExtensions($content);

        // services
        $this->parseSites($content, $resource);
    }

    public function supports($resource, $type = null)
    {
        return is_string($resource) && in_array(pathinfo($resource, PATHINFO_EXTENSION), array('yml', 'yaml'), true);
    }

    /**
     * Parses all imports.
     *
     * @param array  $content
     * @param string $file
     */
    private function parseImports($content, $file)
    {
        if (!isset($content['imports'])) {
            return;
        }

        if (!is_array($content['imports'])) {
            throw new \InvalidArgumentException(sprintf('The "imports" key should contain an array in %s. Check your YAML syntax.', $file));
        }

        foreach ($content['imports'] as $import) {
            if (!is_array($import)) {
                throw new \InvalidArgumentException(sprintf('The values in the "imports" key should be arrays in %s. Check your YAML syntax.', $file));
            }

            $this->setCurrentDir(dirname($file));
            $this->import($import['resource'], null, isset($import['ignore_errors']) ? (bool) $import['ignore_errors'] : false, $file);
        }
    }

    /**
     * Parses definitions.
     *
     * @param array  $content
     * @param string $file
     */
    private function parseSites($content, $file)
    {
        $this->parseSite($content['drupal']);
    }

    private function parseSite($config)
    {
        foreach ($config as $k => $v) {
            $config[$k] = $this->resolveValue($v);
        }

        $this->container['drupal_settings.config'] = array('drupal' => $config);
    }

    protected function loadFile($file)
    {
        if (!class_exists('Symfony\Component\Yaml\Parser')) {
            throw new \RuntimeException('Unable to load YAML config files as the Symfony Yaml Component is not installed.');
        }

        if (!stream_is_local($file)) {
            throw new \InvalidArgumentException(sprintf('This is not a local file "%s".', $file));
        }

        if (!file_exists($file)) {
            throw new \InvalidArgumentException(sprintf('The service file "%s" is not valid.', $file));
        }

        if (null === $this->yamlParser) {
            $this->yamlParser = new YamlParser();
        }

        return $this->validate($this->yamlParser->parse(file_get_contents($file)), $file);
    }

    /**
     * Validates a YAML file.
     *
     * @param mixed  $content
     * @param string $file
     *
     * @return array
     *
     * @throws \InvalidArgumentException When service file is not valid
     */
    private function validate($content, $file)
    {
        if (null === $content) {
            return $content;
        }

        if (!is_array($content)) {
            throw new \InvalidArgumentException(sprintf('The service file "%s" is not valid. It should contain an array. Check your YAML syntax.', $file));
        }

        foreach ($content as $namespace => $data) {
            if (in_array($namespace, array('imports', 'drupal'))) {
                continue;
            }

            throw new \InvalidArgumentException();
        }

        return $content;
    }

    private function resolveValue($value)
    {
        if (is_array($value)) {
            $value = array_map(array($this, 'resolveValue'), $value);
        } elseif (is_string($value) &&  0 === strpos($value, '@=')) {
            return new Expression(substr($value, 2));
        }

        return $value;
    }

    private function loadFromExtensions($content)
    {
        foreach ($content as $namespace => $values) {
            if (in_array($namespace, array('imports'))) {
                continue;
            }

            if (!is_array($values)) {
                $values = array();
            }

            if ($namespace === 'drupal') {
                $this->container['drupal_settings.config'] = array('drupal' => $values);
            }
        }
    }
}
