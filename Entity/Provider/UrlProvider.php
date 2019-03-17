<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\Entity\Provider;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Mikoweb\SymfonyUtility\Entity\Provider\UrlProviderInterface;

class UrlProvider implements UrlProviderInterface
{
    /**
     * @var RouterInterface
     */
    protected $router;

    /**
     * @param RouterInterface $router
     */
    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(string $name, $parameters = [], $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->router->generate($name, $parameters, $referenceType);
    }
}
