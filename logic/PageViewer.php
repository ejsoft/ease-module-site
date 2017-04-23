<?php

namespace ej\Site\logic;


use ej\web\Controller;
use yii\base\Component;
use ej\Site\models\Page;
use yii\web\HttpException;
use ej\Site\events\PageEvent;

class PageViewer extends Component
{
    const EVENT_BEFORE_RENDER = 'beforeRender';
    /**
     * @var
     */
    private $_slug;
    /**
     * @var
     */
    private $_page;
    /**
     * @var
     */
    private $_controller;

    /**
     * PageViewer constructor.
     *
     * @param $slug
     * @param Controller $controller
     */
    public function __construct($slug, Controller $controller)
    {
        $this->_slug = $slug;
        $this->_controller = $controller;
    }

    /**
     * @return string
     */
    public function fetch()
    {
        $this->loadPage();

        if (!$this->hasPage()) {
            return false;
        }

        $this->registerMetaTags();

        $event = new PageEvent();
        $event->page = $this;

        $this->trigger(self::EVENT_BEFORE_RENDER, $event);

        if (!$event->isValid) {
            return false;
        }

        if (!empty($event->content)) {
            return $event->content;
        }

        if (!empty($this->getPage()->page_layout)) {
            $this->getController()->layout = $this->getPage()->page_layout;
        }

        $viewFile = 'index';

        if (!empty($this->getPage()->page_view)) {
            $viewFile = $this->getPage()->page_view;
        }

        return $this->getController()->render($viewFile, ['page' => $this->getPage()]);
    }

    /**
     * @return mixed
     */
    public function hasPage()
    {
        $this->loadPage();

        return $this->_page instanceof Page ? true : false;
    }

    /**
     * @throws HttpException
     */
    public function page404()
    {
        throw new HttpException(404, __('frontend', 'The requested page does not exist.'));
    }

    /**
     * @inheritdoc
     */
    protected function registerMetaTags()
    {
        $view = $this->getController()->getView();

        if (!empty($this->getPage()->meta_title)) {
            $view->title = $this->getPage()->meta_title;
        } else {
            $view->title = $this->getPage()->title;
        }
        if (!empty($this->getPage()->meta_keywords)) {
            $view->registerMetaTag([
                'name'    => 'keywords',
                'content' => $this->getPage()->meta_keywords
            ]);
        }
        if (!empty($this->getPage()->meta_description)) {
            $view->registerMetaTag([
                'name'    => 'description',
                'content' => $this->getPage()->meta_description
            ]);
        }
    }

    /**
     * @return Controller
     */
    public function getController()
    {
        return $this->_controller;
    }

    /**
     * @return Page|null
     */
    public function getPage()
    {
        $this->loadPage();

        return $this->_page;
    }

    /**
     * @inheritdoc
     */
    protected function loadPage()
    {
        if ($this->_page === null) {
            if (empty($this->_slug)) {
                $page = Page::defaultPage();
            } else {
                $page = Page::findBySlug($this->_slug);
                if ($page && $page->is_default) {
                    $page = null;
                }
            }
            $this->_page = $page ? $page : false;
        }
    }
}