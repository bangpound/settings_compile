<?php

namespace Drupal\Settings;

use Symfony\Component\Config\Loader\FileLoader;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\Yaml\Parser as YamlParser;

class YamlFileLoader extends FileLoader
{
    private $yamlParser;

    public function load($resource, $type = null)
    {
        $path = $this->locator->locate($resource);

        $content = $this->loadFile($path);

        // empty file
        if (null === $content) {
            return array();
        }

        // imports
        $content = $this->parseImports($content, $path);

        // services
        $content = $this->parseSites($content, $resource);

        return $content;
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
     *
     * @return array
     */
    private function parseImports($content, $file)
    {
        if (!isset($content['imports'])) {
            return $content;
        }

        if (!is_array($content['imports'])) {
            throw new \InvalidArgumentException(sprintf('The "imports" key should contain an array in %s. Check your YAML syntax.', $file));
        }

        foreach ($content['imports'] as $import) {
            if (!is_array($import)) {
                throw new \InvalidArgumentException(sprintf('The values in the "imports" key should be arrays in %s. Check your YAML syntax.', $file));
            }

            $this->setCurrentDir(dirname($file));
            $content = array_merge($content, $this->import($import['resource'], null, isset($import['ignore_errors']) ? (bool) $import['ignore_errors'] : false, $file));
        }

        return $content;
    }

    /**
     * Parses definitions.
     *
     * @param array  $content
     * @param string $file
     *
     * @return array
     */
    private function parseSites($content, $file)
    {
        foreach ($content as $k => $v) {
            $content[$k] = $this->parseSite($v);
        }

        return $content;
    }

    private function parseSite($config)
    {
        foreach ($config as $k => $v) {
            $config[$k] = $this->resolveValue($v);
        }

        return $config;
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

        return $this->yamlParser->parse(file_get_contents($file));
    }

    private function resolveValue($value)
    {
        if (is_array($value)) {
            $value = array_map(array($this, 'resolveValue'), $value);
        } elseif (is_string($value) && 0 === strpos($value, '@=')) {
            return new Expression(substr($value, 2));
        }

        return $value;
    }
}
