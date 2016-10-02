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

namespace vSymfo\Bundle\CoreBundle\Crud;

use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\OptionsResolver\OptionsResolver;
use vSymfo\Core\Crud\Data;
use vSymfo\Core\Crud\DataEvent;
use vSymfo\Core\Crud\DataInterface;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Crud
 */
class Crud extends CrudAbstract
{
    /**
     * {@inheritdoc}
     */
    public function index(Request $request, array $options = [])
    {
        $data = new Data();
        $data->setCollection($this->getManager()->getPagination($request,
            $this->container->getParameter('vsymfo_core.pagination.limit')));

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function create(Request $request, array $options = [])
    {
        $options = $this->commonOptionsResolver()->resolve($options);
        $data = new Data();
        $entity = $this->getManager()->createEntity();
        $data->setEntity($entity);
        $options['route_params'] = empty($options['route_params']) ? function () {
            return [];
        } : $options['route_params'];
        $form = $this->getManager()->buildForm($entity, array_merge($options['form_options'], [
            'action' => $this->generateUrl($this->storeRoute(), $this->routeParameters($data, $options)),
        ]), $options['form_type']);
        $data->setForm($form);
        $dispatcher = new EventDispatcher();
        $this->addListeners($dispatcher, $options['events']);
        $this->dispatch($dispatcher, DataEvent::SUBMIT_BEFORE, $data);
        $form->handleRequest($request);
        $this->dispatch($dispatcher, DataEvent::SUBMIT_AFTER, $data);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function store(Request $request, array $options = [])
    {
        $options = $this->commonOptionsResolver()->resolve($options);
        $data = new Data();
        $entity = $this->getManager()->createEntity();
        $form = $this->getManager()->buildForm($entity, $options['form_options'], $options['form_type']);
        $data->setEntity($entity);
        $data->setForm($form);
        $dispatcher = new EventDispatcher();
        $this->addListeners($dispatcher, $options['events']);
        $this->dispatch($dispatcher, DataEvent::SUBMIT_BEFORE, $data);
        $form->handleRequest($request);
        $this->dispatch($dispatcher, DataEvent::SUBMIT_AFTER, $data);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatch($dispatcher, DataEvent::SAVE_BEFORE, $data);
            $this->getManager()->save($entity);
            $this->dispatch($dispatcher, DataEvent::SAVE_AFTER, $data);

            if ($options['add_flash']) {
                $this->addFlash(self::FLASH_SUCCESS, 'store_success');
            }

            $this->redirectAfterSave($data, $options);
        } else {
            $data->setResponse($this->formRedirect($request, $this->createRoute(),
                array_merge($request->query->all(), $this->routeParameters($data, $options))));
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function show(Request $request, array $options = [])
    {
        $data = new Data();
        $data->setEntity($this->getManager()->findEntity($request));

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function edit(Request $request, array $options = [])
    {
        $options = $this->commonOptionsResolver()->resolve($options);
        $data = new Data();
        $entity = $this->getManager()->findEntity($request);
        $data->setEntity($entity);
        $form = $this->getManager()->buildForm($entity, array_merge($options['form_options'], [
            'action' => $this->generateUrl($this->updateRoute(), $this->routeParameters($data, $options)),
        ]), $options['form_type']);
        $data->setForm($form);
        $dispatcher = new EventDispatcher();
        $this->addListeners($dispatcher, $options['events']);
        $this->dispatch($dispatcher, DataEvent::SUBMIT_BEFORE, $data);
        $form->handleRequest($request);
        $this->dispatch($dispatcher, DataEvent::SUBMIT_AFTER, $data);

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function update(Request $request, array $options = [])
    {
        $options = $this->commonOptionsResolver()->resolve($options);
        $data = new Data();
        $entity = $this->getManager()->findEntity($request);
        $form = $this->getManager()->buildForm($entity, $options['form_options'], $options['form_type']);
        $data->setEntity($entity);
        $data->setForm($form);
        $dispatcher = new EventDispatcher();
        $this->addListeners($dispatcher, $options['events']);
        $this->dispatch($dispatcher, DataEvent::SUBMIT_BEFORE, $data);
        $form->handleRequest($request);
        $this->dispatch($dispatcher, DataEvent::SUBMIT_AFTER, $data);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->dispatch($dispatcher, DataEvent::SAVE_BEFORE, $data);
            $this->getManager()->save($entity);
            $this->dispatch($dispatcher, DataEvent::SAVE_AFTER, $data);

            if ($options['add_flash']) {
                $this->addFlash(self::FLASH_SUCCESS, 'update_success');
            }

            $this->redirectAfterSave($data, $options);
        } else {
            $data->setResponse($this->formRedirect($request, $this->editRoute(),
                array_merge($request->query->all(), $this->routeParameters($data, $options))));
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(Request $request, array $options = [])
    {
        $options = $this->commonOptionsResolver()->resolve($options);
        $data = new Data();
        $entity = $this->getManager()->findEntity($request);
        $data->setEntity($entity);
        $this->getManager()->remove($entity);

        if ($options['add_flash']) {
            $this->addFlash(self::FLASH_SUCCESS, 'destroy_success');
        }

        if (empty($options['redirect_url'])) {
            $data->setResponse($this->redirectToRoute($this->indexRoute()));
        } else {
            $data->setResponse($this->redirect($options['redirect_url']));
        }

        return $data;
    }

    /**
     * @return OptionsResolver
     */
    protected function commonOptionsResolver()
    {
        $resolver = new OptionsResolver();
        $resolver->setDefaults([
            'events' => [],
            'form_options' => [],
            'form_type' => null,
            'add_flash' => true,
            'redirect_url' => null,
            'route_params' => null,
            'redirect_cancel_url' => null,
        ]);
        $resolver->setAllowedTypes('events', 'array');
        $resolver->setAllowedTypes('form_options', 'array');
        $resolver->setAllowedTypes('add_flash', 'bool');
        $resolver->setAllowedTypes('redirect_url', ['string', 'null']);
        $resolver->setAllowedTypes('redirect_cancel_url', ['string', 'null']);
        $resolver->setAllowedTypes('route_params', ['callable', 'null']);

        return $resolver;
    }

    /**
     * @param EventDispatcher $dispatcher
     * @param array $events
     */
    protected function addListeners(EventDispatcher $dispatcher, array $events)
    {
        foreach ($events as $name => $closure) {
            if (is_callable($closure)) {
                $dispatcher->addListener($name, $closure);
            }
        }
    }

    /**
     * Returns params required to generate url eq. edit route.
     *
     * @param DataInterface $data
     * @param array $options
     *
     * @return array
     */
    protected function routeParameters(DataInterface $data, array $options)
    {
        if (is_null($data->getEntity())) {
            throw new \UnexpectedValueException('Entity is null');
        }

        return $this->routeParams($data->getEntity(), $options['route_params']);
    }

    /**
     * @param DataInterface $data
     * @param array $options
     */
    protected function redirectAfterSave(DataInterface $data, array $options)
    {
        if (empty($options['redirect_url'])) {
            $data->setResponse($this->redirectToRoute($this->editRoute(), $this->routeParameters($data, $options)));
        } else {
            $data->setResponse($this->redirect($options['redirect_url']));
        }
    }
}
