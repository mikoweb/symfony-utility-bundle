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

namespace vSymfo\Bundle\CoreBundle\Service;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;

/**
 * @author Rafał Mikołajun <rafal@vision-web.pl>
 * @package vSymfo Core Bundle
 * @subpackage Service
 */
class RESTFormService
{
    /**
     * @var string
     */
    protected $validAttr;

    /**
     * @var string
     */
    protected $htmlAttr;

    /**
     * @var string
     */
    protected $messageAttr;

    /**
     * @var \Twig_Environment
     */
    protected $twig;

    /**
     * @param \Twig_Environment $twig
     */
    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
        $this->setHtmlAttr('_html');
        $this->setValidAttr('_isValid');
        $this->setMessageAttr('_message');
    }

    /**
     * Returns form data as assoc array.
     *
     * @param Form $form
     * @param null|string $template Template path for html view.
     * @param bool $addValid
     * @param bool $addErrors
     *
     * @return array
     */
    public function data(Form $form, $template = null, $addValid = true, $addErrors = true)
    {
        $data = [];
        $view = $form->createView();

        if ($addValid === true) {
            $data[$this->getValidAttr()] = $form->isValid();
        }

        if ($addErrors === true) {
            $errors = $this->errors($form);
            if (!empty($errors)) {
                $data[$this->getMessageAttr()] = $errors;
            }
        }

        if ($template !== null) {
            $data[$this->getHtmlAttr()] = $this->twig->render($template, [
                'form' => $view,
            ]);
        }

        if (isset($view->vars['name'])) {
            $data[$view->vars['name']] = [];
            $this->childrenData($data[$view->vars['name']], $view, $data);
        } else {
            $this->childrenData($data, $view);
        }

        return $data;
    }

    /**
     * Returns form errors as assoc array.
     *
     * @param Form $form
     *
     * @return array
     */
    public function errors(Form $form)
    {
        $errors = [];
        $view = $form->createView();
        $this->childrenErrors($errors, $view, isset($view->vars['name']) ? $view->vars['name'] : '.');

        return $errors;
    }

    /**
     * @return string
     */
    public function getValidAttr()
    {
        return $this->validAttr;
    }

    /**
     * @param string $validAttr
     */
    public function setValidAttr($validAttr)
    {
        $this->validAttr = $validAttr;
    }

    /**
     * @return string
     */
    public function getHtmlAttr()
    {
        return $this->htmlAttr;
    }

    /**
     * @param string $htmlAttr
     */
    public function setHtmlAttr($htmlAttr)
    {
        $this->htmlAttr = $htmlAttr;
    }

    /**
     * @return string
     */
    public function getMessageAttr()
    {
        return $this->messageAttr;
    }

    /**
     * @param string $messageAttr
     */
    public function setMessageAttr($messageAttr)
    {
        $this->messageAttr = $messageAttr;
    }

    /**
     * @param array $data
     * @param FormView $view
     * @param null|array $parent
     */
    private function childrenData(array &$data, FormView $view, array &$parent = null)
    {
        if (empty($view->children)) {
            $item = &$parent[$view->vars['name']];
            if (!is_null($view->vars['data']) && is_scalar($view->vars['data'])) {
                $item = $view->vars['data'];
            } else {
                $item = isset($view->vars['value']) ? $view->vars['value'] : null;
            }
        } else {
            foreach ($view->children as $child) {
                $data[$child->vars['name']] = [];
                $this->childrenData($data[$child->vars['name']], $child, $data);
            }
        }
    }

    /**
     * @param array $errors
     * @param FormView $view
     * @param string
     */
    private function childrenErrors(array &$errors, FormView $view, $path)
    {
        if (isset($view->vars['errors'])) {
            foreach ($view->vars['errors'] as $error) {
                if (is_null($view->parent)) {
                    $errors[] = $error->getMessage();
                } else {
                    $errors[] = [$path => $error->getMessage()];
                }
            }
        }

        foreach ($view->children as $child) {
            $this->childrenErrors($errors, $child, isset($child->vars['name'])
                ? ($path . '.' . $child->vars['name']) : $path);
        }
    }
}
