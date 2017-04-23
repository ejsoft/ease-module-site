<?php

namespace ej\Site\events;


use yii\base\Event;

class PageEvent extends Event
{
    /**
     * @var bool
     */
    public $isValid = true;
    /**
     * @var
     */
    public $page;
}