<?php

namespace ej\Site\models;


use yii\db\ActiveQuery;

class PageQuery extends ActiveQuery
{
    /**
     * @param $site_id
     *
     * @return $this
     */
    public function bySite($site_id)
    {
        return $this->innerJoin('{{%site_page}}', '{{%page}}.page_id={{%site_page}}.page_id')
            ->andWhere('{{%site_page}}.site_id=:site_id', ['site_id' => $site_id]);
    }
}