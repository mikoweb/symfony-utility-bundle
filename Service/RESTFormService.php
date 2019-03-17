<?php

/*
 * (c) Rafał Mikołajun <root@rmweb.pl>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Mikoweb\SymfonyUtilityBundle\Service;

use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\Request;

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
    public function data(Form $form, ?string $template = null, bool $addValid = true, bool $addErrors = true): array
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
    public function errors(Form $form): array
    {
        $errors = [];
        $view = $form->createView();
        $this->childrenErrors($errors, $view, isset($view->vars['name']) ? $view->vars['name'] : '.');

        return $errors;
    }

    /**
     * Generate first form item from request.
     *
     * @param Request $request
     * @param Form $form
     * @param string $field
     * @param mixed $data
     *
     * @return array
     */
    public function firstItem(Request $request, Form $form, string $field, $data): array
    {
        $item = [];
        $content = json_decode($request->getContent());

        if (is_object($content) && isset($content->{$form->getName()})
            && isset($content->{$form->getName()}->{$field})
        ) {
            $key = key($content->{$form->getName()}->{$field});
            if (!is_null($key)) {
                $item[$field][$key] = $data;
            }
        }

        return $item;
    }

    /**
     * @return string
     */
    public function getValidAttr(): string
    {
        return $this->validAttr;
    }

    /**
     * @param string $validAttr
     */
    public function setValidAttr(string $validAttr): void
    {
        $this->validAttr = $validAttr;
    }

    /**
     * @return string
     */
    public function getHtmlAttr(): string
    {
        return $this->htmlAttr;
    }

    /**
     * @param string $htmlAttr
     */
    public function setHtmlAttr(string $htmlAttr): void
    {
        $this->htmlAttr = $htmlAttr;
    }

    /**
     * @return string
     */
    public function getMessageAttr(): string
    {
        return $this->messageAttr;
    }

    /**
     * @param string $messageAttr
     */
    public function setMessageAttr(string $messageAttr): void
    {
        $this->messageAttr = $messageAttr;
    }

    /**
     * @param array $data
     * @param FormView $view
     * @param null|array $parent
     */
    private function childrenData(array &$data, FormView $view, array &$parent = null): void
    {
        if (empty($view->children)) {
            $item = &$parent[$view->vars['name']];
            if (isset($view->vars['data']) && !is_null($view->vars['data']) && is_scalar($view->vars['data'])) {
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
     * @param string $path
     */
    private function childrenErrors(array &$errors, FormView $view, string $path): void
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
