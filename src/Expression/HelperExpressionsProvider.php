<?php
/**
 * This is a part of Maketok site package.
 *
 * @author Oleg Kulik <slayer.birden@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Maketok\DataMigration\Expression;

use Symfony\Component\ExpressionLanguage\ExpressionFunction;
use Symfony\Component\ExpressionLanguage\ExpressionFunctionProviderInterface;

/**
 * @codeCoverageIgnore
 */
class HelperExpressionsProvider implements ExpressionFunctionProviderInterface
{
    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new ExpressionFunction('trim', function ($str, $mask = " \t\n\r\0\x0B") {
                return sprintf('trim(%1$s, %2$s)', $str, $mask);
            }, function ($arguments, $str, $mask = " \t\n\r\0\x0B") {
                if (!is_string($str)) {
                    return $str;
                }

                return trim($str, $mask);
            }),
            new ExpressionFunction('explode', function ($str, $expression) {
                return sprintf('explode(%1$s, %2$s)', $str, $expression);
            }, function ($arguments, $str, $expression) {
                if (!is_string($expression)) {
                    return $expression;
                }

                return explode($str, $expression);
            }),
            new ExpressionFunction('empty', function ($expression) {
                return sprintf('empty(%1$s)', $expression);
            }, function ($arguments, $expression) {
                return empty($expression);
            }),
            new ExpressionFunction('isset', function ($expression) {
                return sprintf('isset(%1$s)', $expression);
            }, function ($arguments, $expression) {
                return isset($expression);
            }),
            new ExpressionFunction('count', function ($array) {
                return sprintf('count(%1$s)', $array);
            }, function ($arguments, $array) {
                if (!is_array($array)) {
                    return $array;
                }

                return count($array);
            }),
        );
    }
}
