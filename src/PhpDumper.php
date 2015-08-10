<?php

namespace Drupal\Settings;

use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class PhpDumper
{
    /**
     * @var ExpressionLanguage
     */
    private $expressionLanguage;

    public function __construct(ExpressionLanguage $expressionLanguage)
    {
        $this->expressionLanguage = $expressionLanguage;
    }

    /**
     * Dumps the settings as a PHP file.
     *
     * @param array $config An array of options
     *
     * @return string A PHP class representing of the service container
     *
     * @api
     */
    public function dump($config)
    {
        $code = '<?php'.PHP_EOL;

        foreach ($config['settings'] as $key => $value) {
            $code .= '$'.$key.' = '.$this->dumpValue($value).';'.PHP_EOL;
        }

        foreach ($config['ini'] as  $key => $value) {
            $code .= sprintf('ini_set(%s, %s);', $this->dumpValue($key), $this->dumpValue($value)).PHP_EOL;
        }

        if (isset($config['include'])) {
            foreach ($config['include'] as $key => $value) {
                foreach ($value as $val) {
                    $code .= sprintf('%s %s;', $key, $this->dumpValue($val)).PHP_EOL;
                }
            }
        }

        return $code;
    }

    /**
     * Dumps values.
     *
     * @param mixed $value
     *
     * @return string
     *
     * @throws \RuntimeException
     */
    private function dumpValue($value)
    {
        if (is_array($value)) {
            $code = array();
            foreach ($value as $k => $v) {
                $code[] = sprintf('%s => %s', $this->dumpValue($k), $this->dumpValue($v));
            }

            return sprintf('array(%s)', implode(', ', $code));
        } elseif ($value instanceof Expression) {
            return $this->expressionLanguage->compile((string) $value, array('_SERVER', 'GLOBALS', 'conf'));
        } else {
            return var_export($value, true);
        }
    }
}
