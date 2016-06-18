<?php

/*
 * This file is part of the vSymfo package.
 *
 * website: www.vision-web.pl
 * (c) Rafał Mikołajun <rafal@vision-web.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace vSymfo\Bundle\CoreBundle\TwigExtension;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use vSymfo\Component\Document\UrlManager;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage TwigExtension
 */
class UtilitiesExtension extends \Twig_Extension
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @return array
     */
    public function getFilters()
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
     * @return array
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction('cdn_asset', array($this, 'cdnAsset')),
        );
    }

    /**
     * Limit wyrazów w tekście
     * @param string $text
     * @param int $limit
     * @param bool $stripTags
     * @param string $append
     * @param string $separator
     * @return string
     */
    public function limitWords($text, $limit = 3, $stripTags = true, $append = '...', $separator = ' ')
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
     * Odnośnik HTML do maila w formacie HTML.
     * Pomysł zaczęrpnięty z http://blog.kamilbrenk.pl/proste-ukrywanie-adresu-e-mail/
     *
     * W CSS:
     * .safe-email {position: relative; cursor: pointer}
     * .safe-email:before {content: attr(data-mail-local) "@"}
     * .safe-email:after {content: attr(data-mail-domain)}
     *
     * @param string $email
     * @param string $text
     * @param string $href
     * @param array $attr
     * @param string $class
     * @return string
     */
    public function safeEmail($email, $text = '', $href = null, array $attr = null, $class = 'safe-email')
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
     * Tłumaczenie nazw miesiąca i dnia tygodnia na język witryny
     * @param string $text
     * @return string
     */
    public function transDate($text)
    {
        $trans = $this->container->get('translator');

        $text = str_replace(array(
            "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"
        ), array(
            $trans->trans("date.month.january", array(), "date"), $trans->trans("date.month.february", array(), "date"), $trans->trans("date.month.march", array(), "date"), $trans->trans("date.month.april", array(), "date"),
            $trans->trans("date.month.may", array(), "date"), $trans->trans("date.month.june", array(), "date"), $trans->trans("date.month.july", array(), "date"), $trans->trans("date.month.august", array(), "date"),
            $trans->trans("date.month.september", array(), "date"), $trans->trans("date.month.october", array(), "date"), $trans->trans("date.month.november", array(), "date"), $trans->trans("date.month.december", array(), "date")
        ), $text);

        $text = str_replace(array(
            "Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
        ), array(
            $trans->trans("date.month.jan", array(), "date"), $trans->trans("date.month.feb", array(), "date"), $trans->trans("date.month.mar", array(), "date"), $trans->trans("date.month.apr", array(), "date"),
            $trans->trans("date.month.may", array(), "date"), $trans->trans("date.month.jun", array(), "date"), $trans->trans("date.month.jul", array(), "date"), $trans->trans("date.month.aug", array(), "date"),
            $trans->trans("date.month.sep", array(), "date"), $trans->trans("date.month.oct", array(), "date"), $trans->trans("date.month.nov", array(), "date"), $trans->trans("date.month.dec", array(), "date")
        ), $text);

        $text = str_replace(array(
            "Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday"
        ), array(
            $trans->trans("date.day.sunday", array(), "date"), $trans->trans("date.day.monday", array(), "date"), $trans->trans("date.day.tuesday", array(), "date"), $trans->trans("date.day.wednesday", array(), "date"), $trans->trans("date.day.thursday", array(), "date"), $trans->trans("date.day.friday", array(), "date"), $trans->trans("date.day.saturday", array(), "date")
        ), $text);

        $text = str_replace(array(
            "Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"
        ), array(
            $trans->trans("date.day.sun", array(), "date"), $trans->trans("date.day.mon", array(), "date"), $trans->trans("date.day.tue", array(), "date"), $trans->trans("date.day.wed", array(), "date"), $trans->trans("date.day.thu", array(), "date"), $trans->trans("date.day.fri", array(), "date"), $trans->trans("date.day.sat", array(), "date")
        ), $text);

        return $text;
    }

    /**
     * Rozszerzenie natywnej funkcji asset o automatyczne wstawianie hosta CDN w URL-u
     * @param string $path ścieżka do zasobu
     * @param string $type type zasobu
     * @return string
     */
    public function cdnAsset($path, $type = 'image')
    {
        $params = $this->container->getParameter('vsymfo_core.document');
        if (Kernel::MAJOR_VERSION < 3) {
            $asset = $this->container->get('templating.helper.assets');
        } else {
            $asset = $this->container->get('assets.packages');
        }

        if (!$params['cdn_enable']) {
            return $asset->getUrl($path);
        }

        switch ($type) {
            case 'css':
                $domain = $params['cdn_css'];
                break;
            case 'javascript':
            case 'js':
                $domain = $params['cdn_javascript'];
                break;
            case 'image':
            default:
                $domain = $params['cdn_image'];
        }

        if (empty($domain)) {
            return $asset->getUrl($path);
        }

        $urlManager = new UrlManager();
        $urlManager->setDomainPath($domain);

        return $urlManager->url($asset->getUrl($path), false);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'utilities';
    }
}
