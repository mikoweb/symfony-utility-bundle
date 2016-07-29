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

namespace vSymfo\Bundle\CoreBundle\Entity\Provider;

use Symfony\Component\Asset\Packages;
use vSymfo\Core\Entity\Provider\ImagesProviderInterface;
use Vich\UploaderBundle\Templating\Helper\UploaderHelper;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Entity_Provider
 */
class ImagesProvider implements ImagesProviderInterface
{
    /**
     * @var Packages
     */
    protected $assetPackages;

    /**
     * @var UploaderHelper
     */
    protected $uploaderHelper;

    /**
     * @param Packages $assetPackages
     * @param UploaderHelper $uploaderHelper
     */
    public function __construct(Packages $assetPackages, UploaderHelper $uploaderHelper)
    {
        $this->assetPackages = $assetPackages;
        $this->uploaderHelper = $uploaderHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function asset($obj, $fieldName)
    {
        return $this->uploaderHelper->asset($obj, $fieldName);
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($path, $packageName = null)
    {
        return $this->assetPackages->getUrl($path, $packageName);
    }

    /**
     * {@inheritdoc}
     */
    public function render($obj, $fieldName)
    {
        // TODO: Implement render() method.
    }
}
