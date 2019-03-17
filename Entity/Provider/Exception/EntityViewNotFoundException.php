<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\Entity\Provider\Exception;

class EntityViewNotFoundException extends \Exception
{
    /**
     * @var string
     */
    private $key;

    /**
     * @var string
     */
    private $className;

    public function getKey(): string
    {
        return $this->key;
    }

    public function setKey(string $key): void
    {
        $this->key = $key;
        $this->message();
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    public function setClassName(string $className): void
    {
        $this->className = $className;
        $this->message();
    }

    private function message(): void
    {
        $this->message = 'Not found view "' . $this->key . '" for class "' . $this->className . '"';
    }
}
