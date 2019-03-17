<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\TwigExtension;

use Symfony\Component\DependencyInjection\ContainerAwareInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use vSymfo\Component\Document\UrlManager;

class UtilitiesExtension extends \Twig_Extension implements ContainerAwareInterface
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * {@inheritdoc}
     */
    public function setContainer(ContainerInterface $container = null)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getFilters(): array
    {
        return array(
            new \Twig_SimpleFilter('limit_words', array($this, 'limitWords')),
            new \Twig_SimpleFilter('trans_date', array($this, 'transDate')),
            new \Twig_SimpleFilter('safe_email', array($this, 'safeEmail'), array(
                'is_safe' => array('html')
            ))
        );
    }

    /**
     * Apply words limit in text
     *
     * @param string $text
     * @param int $limit
     * @param bool $stripTags
     * @param string $append
     * @param string $separator
     *
     * @return string
     */
    public function limitWords(
        string $text,
        int $limit = 3,
        bool $stripTags = true,
        string $append = '...',
        string $separator = ' '
    ): string
    {
        $words = explode($separator, $stripTags ? strip_tags($text) : $text);
        $count = count($words);

        $i = 0;
        $result = '';

        while ($i < $limit && $i < $count) {
            $result .= $words[$i] . $separator;
            ++$i;
        }

        $result = substr($result, 0, -strlen($separator));

        if ($limit < $count) {
            $result .= $append;
        }

        return $result;
    }

    /**
     * Safe email link
     *
     * @link http://blog.kamilbrenk.pl/proste-ukrywanie-adresu-e-mail/
     *
     * In CSS:
     * .safe-email {position: relative; cursor: pointer}
     * .safe-email:before {content: attr(data-mail-local) "@"}
     * .safe-email:after {content: attr(data-mail-domain)}
     *
     * @param string $email
     * @param string $text
     * @param string $href
     * @param array $attr
     * @param string $class
     *
     * @return string
     */
    public function safeEmail(
        string $email,
        string $text = '',
        ?string $href = null,
        ?array $attr = null,
        string $class = 'safe-email'
    ): string
    {
        $parts = explode('@', $email);
        if (count($parts) === 2 && is_string($text) && is_string($class)) {
            $additional = '';
            if (is_array($attr)) {
                foreach ($attr as $k=>$v) {
                    if (is_string($v)) {
                        $name = preg_replace('/[^A-Z0-9_-]/i', '', $k);
                        $notallowed = array('href', 'class', 'data-mail-local', 'data-mail-domain', 'tabindex', 'onfocus');
                        if (!empty($name) && !in_array($name, $notallowed)) {
                            $additional .= $name . '="' . htmlspecialchars($v) . '" ';
                        }
                    }
                }
            }

            return '<a '. (is_string($href) ? ('href="' . htmlspecialchars($href) . '" ') : '') . 'class="'.htmlspecialchars($class).'" '
            . 'data-mail-local="'.htmlspecialchars($parts[0]).'" '
            . 'data-mail-domain="'.htmlspecialchars($parts[1]).'" '
            . 'tabindex="0" '
            . $additional
            . 'onfocus="this.href=\'mailto:\' + this.getAttribute(\'data-mail-local\') + \'\u0040\' + this.getAttribute(\'data-mail-domain\');">'
            . htmlspecialchars($text) . '</a>';
        }

        return $email;
    }

    /**
     * Months and days names translation
     *
     * @param string $text
     *
     * @return string
     */
    public function transDate(string $text): string
    {
        $trans = $this->container->get('translator');

        $text = str_replace([
            "January",
            "February",
            "March",
            "April",
            "May",
            "June",
            "July",
            "August",
            "September",
            "October",
            "November",
            "December"
        ], [
            $trans->trans("date.month.january", [], "date"),
            $trans->trans("date.month.february", [], "date"),
            $trans->trans("date.month.march", [], "date"),
            $trans->trans("date.month.april", [], "date"),
            $trans->trans("date.month.may", [], "date"),
            $trans->trans("date.month.june", [], "date"),
            $trans->trans("date.month.july", [], "date"),
            $trans->trans("date.month.august", [], "date"),
            $trans->trans("date.month.september", [], "date"),
            $trans->trans("date.month.october", [], "date"),
            $trans->trans("date.month.november", [], "date"),
            $trans->trans("date.month.december", [], "date"),
        ], $text);

        $text = ucfirst(str_replace([
            "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
        ], [
            $trans->trans("date.month.jan", [], "date"),
            $trans->trans("date.month.feb", [], "date"),
            $trans->trans("date.month.mar", [], "date"),
            $trans->trans("date.month.apr", [], "date"),
            $trans->trans("date.month.may", [], "date"),
            $trans->trans("date.month.jun", [], "date"),
            $trans->trans("date.month.jul", [], "date"),
            $trans->trans("date.month.aug", [], "date"),
            $trans->trans("date.month.sep", [], "date"),
            $trans->trans("date.month.oct", [], "date"),
            $trans->trans("date.month.nov", [], "date"),
            $trans->trans("date.month.dec", [], "date"),
        ], $text));

        $text = str_replace([
            "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
        ], [
            $trans->trans("date.day.sunday", [], "date"),
            $trans->trans("date.day.monday", [], "date"),
            $trans->trans("date.day.tuesday", [], "date"),
            $trans->trans("date.day.wednesday", [], "date"),
            $trans->trans("date.day.thursday", [], "date"),
            $trans->trans("date.day.friday", [], "date"),
            $trans->trans("date.day.saturday", [], "date"),
        ], $text);

        $text = ucfirst(str_replace([
            "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"
        ], [
            $trans->trans("date.day.sun", [], "date"),
            $trans->trans("date.day.mon", [], "date"),
            $trans->trans("date.day.tue", [], "date"),
            $trans->trans("date.day.wed", [], "date"),
            $trans->trans("date.day.thu", [], "date"),
            $trans->trans("date.day.fri", [], "date"),
            $trans->trans("date.day.sat", [], "date")
        ], $text));

        return $text;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return 'utilities';
    }
}
