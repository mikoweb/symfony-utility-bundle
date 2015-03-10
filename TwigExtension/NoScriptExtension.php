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

/**
 * Usuwanie znaczników <script> z kodu
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage TwigExtension
 */
class NoScriptExtension extends \Twig_Extension
{
	/**
	 * @return array
	 */
	public function getFilters()
	{
		return array(
			'noscript' => new \Twig_Filter_Method($this, 'noscriptFilter', array(
                'is_safe' => array('html')
            )),
		);
	}

	/**
	 * @param string $code
	 * @return string
	 */
	public function noscriptFilter($code)
	{
        return preg_replace('#<script(.*?)>(.*?)</script>#is', '', $code);
	}

	/**
	 * Zwraca nazwę rozszerzenia Twig
	 * @return string
	 */
	public function getName()
	{
		return 'noscript';
	}
}
