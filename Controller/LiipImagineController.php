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

namespace vSymfo\Bundle\CoreBundle\Controller;

use Liip\ImagineBundle\Controller\ImagineController;
use Liip\ImagineBundle\Imagine\Cache\CacheManager;
use Liip\ImagineBundle\Imagine\Data\DataManager;
use Liip\ImagineBundle\Imagine\Filter\FilterManager;
use Liip\ImagineBundle\Imagine\Cache\SignerInterface;
use Psr\Log\LoggerInterface;
use vSymfo\Bundle\CoreBundle\Service\ImagesMappingService;

/**
 * LiipImagineBundle dynamic filters.
 * @link http://symfony.com/doc/master/bundles/LiipImagineBundle/filters.html#dynamic-filters
 *
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Controller
 */
class LiipImagineController extends ImagineController
{
    /**
     * @var ImagesMappingService
     */
    protected $imagesMappingService;

    /**
     * @param ImagesMappingService $mappingService
     * @param DataManager     $dataManager
     * @param FilterManager   $filterManager
     * @param CacheManager    $cacheManager
     * @param SignerInterface $signer
     * @param LoggerInterface $logger
     */
    public function __construct(
        ImagesMappingService $mappingService,
        DataManager $dataManager,
        FilterManager $filterManager,
        CacheManager $cacheManager,
        SignerInterface $signer,
        LoggerInterface $logger = null
    ) {
        $this->imagesMappingService = $mappingService;
        parent::__construct($dataManager, $filterManager, $cacheManager, $signer, $logger);
    }
}
