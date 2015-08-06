<?php

namespace Drupal\Settings;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

class ExpressionFunctionProvider implements ExpressionFunctionProviderInterface
{
    /**
     * @return ExpressionFunction[] An array of Function instances
     */
    public function getFunctions()
    {
        return array(
          new ExpressionFunction('conf_path', function () {
              return 'conf_path()';
          }, function (array $values) {
              return conf_path();
          }),

          new ExpressionFunction('conf_dir', function () {
              return 'basename(conf_path())';
          }, function (array $values) {
              return basename(conf_path());
          }),

          new ExpressionFunction('basename', function ($path) {
              return sprintf('basename(%s)', $path);
          }, function (array $values, $path) {
              return basename($path);
          }),

          new ExpressionFunction('ini_set', function ($varname, $newvalue) {
              return sprintf('ini_set(%s, %s)', $varname, $newvalue);
          }, function (array $values, $varname, $newvalue) {
              ini_set($varname, $newvalue);

              return '';
          }),
        );
    }
}
