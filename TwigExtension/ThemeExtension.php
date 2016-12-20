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

use Symfony\Component\Asset\Packages;
use vSymfo\Core\ApplicationPaths;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage TwigExtension
 */
class ThemeExtension extends \Twig_Extension
{
    /**
     * @var Packages
     */
    protected $packages;

    /**
     * @var ApplicationPaths
     */
    protected $paths;

    /**
     * @param Packages $packages
     * @param ApplicationPaths $paths
     */
    public function __construct(Packages $packages, ApplicationPaths $paths)
    {
        $this->packages = $packages;
        $this->paths = $paths;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('theme_asset', [$this, 'asset']),
        ];
    }

    /**
     * Asset for theme directory.
     *
     * @param string $path
     *
     * @return string
     */
    public function asset($path)
    {
        return $this->packages->getUrl($this->paths->getThemePath() . (strpos($path, '/') === 0 ? null : '/' ) . $path);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'theme';
    }
}
