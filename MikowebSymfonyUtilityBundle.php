<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle;

use Mikoweb\SymfonyUtilityBundle\DependencyInjection\MikowebSymfonyUtilityExtension;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class MikowebSymfonyUtilityBundle extends Bundle
{
    /**
     * {@inheritdoc}
     */
    public function getContainerExtension()
    {
        if (null === $this->extension) {
            $this->extension = new MikowebSymfonyUtilityExtension();
        }

        return $this->extension;
    }
}
