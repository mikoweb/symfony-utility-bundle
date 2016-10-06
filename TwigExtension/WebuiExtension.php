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

use vSymfo\Bundle\CoreBundle\Service\WebuiService;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage TwigExtension
 */
class WebuiExtension extends \Twig_Extension
{
    /**
     * @var WebuiService
     */
    protected $webuiService;

    /**
     * @param WebuiService $webuiService
     */
    public function __construct(WebuiService $webuiService)
    {
        $this->webuiService = $webuiService;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('webui_startapp', [$this, 'startApp']),
        ];
    }

    /**
     * Returns json string with WebUI config.
     *
     * @param string $jsonFilePath  Path to parse json file with WebUI config.
     * @param array $options Required options eg. resources, translations.
     * @param array $extend  Additional params.
     *
     * @return string
     */
    public function startApp($jsonFilePath, array $options, array $extend = [])
    {
        return $this->webuiService->startApp($jsonFilePath, $options, $extend);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'webui';
    }
}
