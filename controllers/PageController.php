<?php

namespace ej\Site\controllers;


use ej\helpers\Html;
use ej\web\Controller;
use yii\web\HttpException;
use ej\Site\logic\PageViewer;

class PageController extends Controller
{
    /**
     * @param null $slug
     *
     * @return string
     */
    public function actionIndex($slug = null)
    {
        $page = new PageViewer($slug, $this);

        if ($page->hasPage()) {
            return $page->fetch();
        } elseif (empty($slug) || $slug === '/') {
            return $this->render('index');
        } else {
            $page->page404();
        }
    }

    /**
     * @return string
     */
    public function actionError()
    {
        /**
         * @var $exception HttpException
         */
        $exception = \Yii::$app->getErrorHandler()->exception;

        if ($exception !== null) {
            $this->layout = 'error';

            $statusCode = $exception->statusCode;
            $name = $exception->getName();
            $message = $exception->getMessage();

            $this->getView()->title = Html::encode($name);

            return $this->renderPartial('error', [
                'handler'    => \Yii::$app->getErrorHandler(),
                'exception'  => $exception,
                'statusCode' => $statusCode,
                'name'       => $name,
                'message'    => $message
            ]);
        }
    }
}