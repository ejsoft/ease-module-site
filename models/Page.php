<?php

namespace ej\Site\models;


use Exception;
use yii\db\Query;
use yii\db\ActiveRecord;
use ej\helpers\ArrayHelper;
use yii\behaviors\SluggableBehavior;

class Page extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    const IS_ACTIVE = 1;
    /**
     * @inheritdoc
     */
    const IS_DISABLED = 0;
    /**
     * @var
     */
    private $_sites;

    /**
     * @return string
     */
    public static function tableName()
    {
        return '{{%page}}';
    }

    /**
     * @return PageQuery
     */
    public static function find()
    {
        return new PageQuery(get_called_class());
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class'         => SluggableBehavior::className(),
                'attribute'     => 'title',
                'slugAttribute' => 'slug',
                'immutable'     => true
            ],
        ];
    }

    /**
     * @return array
     */
    public function transactions()
    {
        return [
            'default' => self::OP_INSERT | self::OP_UPDATE | self::OP_DELETE,
        ];
    }

    /**
     * @return array
     */
    public function rules()
    {
        return [
            [['title', 'sites', 'is_active'], 'required'],
            ['sites', 'each', 'rule' => ['integer']],
            [['meta_title', 'title', 'slug'], 'string', 'max' => 255],
            ['slug', 'match', 'pattern' => '/^[a-z0-9](-?[a-z0-9]+)$/i'],
            [['meta_keywords', 'meta_description'], 'string'],
            [['page_layout', 'page_view'], 'validateFilePath'],
            ['is_default', 'boolean']
        ];
    }

    /**
     * @return array
     */
    public function attributeLabels()
    {
        return [
            'page_id'       => __('core', 'ID'),
            'title'         => __('core', 'Title'),
            'page_layout'   => __('core', 'Layout'),
            'page_view'     => __('core', 'View File'),
            'creation_time' => __('core', 'Created'),
            'update_time'   => __('core', 'Modified'),
            'slug'          => __('core', 'Slug'),
            'is_active'     => __('core', 'Status'),
            'is_default'    => __('core', 'Default')
        ];
    }

    /**
     * @return array
     */
    public function attributeHints()
    {
        return [
            'slug' => __('site', 'Relative to Web Site Base URL')
        ];
    }

    /**
     * @inheritdoc
     */
    public function validateFilePath($attribute)
    {
        $value = str_replace(['/', '-', '_'], '', $this->$attribute);

        if (strpos($value, '@') === 0) {
            $value = str_replace('@', '', $value);
        }

        if (ctype_alpha($value) === false) {
            $this->addError($attribute, 'Invalid path.');
        }
    }

    /**
     * @param $site_id
     *
     * @return \yii\db\ActiveQuery
     */
    public static function findBySite($site_id)
    {
        return self::find()->andOnCondition(['is_active' => self::IS_ACTIVE])->bySite($site_id);
    }

    /**
     * @param $site_id
     *
     * @return array|null|ActiveRecord
     */
    public static function defaultPage($site_id = null)
    {
        if (!$site_id) {
            $site_id = \Yii::$app->getSite()->getId();
        }

        return self::findBySite($site_id)
            ->andWhere(['is_default' => 1])->one();
    }

    /**
     * @param $slug
     * @param null $site_id
     *
     * @return bool
     */
    public static function hasPageBySlug($slug, $site_id = null): bool
    {
        if (!$site_id) {
            $site_id = \Yii::$app->getSite()->getId();
        }

        return (int)self::findBySite($site_id)->andWhere(['slug' => $slug])->count() > 0;
    }

    /**
     * @param $slug
     * @param null $site_id
     *
     * @return array|null|ActiveRecord
     */
    public static function findBySlug($slug, $site_id = null)
    {
        if (!$site_id) {
            $site_id = \Yii::$app->getSite()->getId();
        }

        return self::findBySite($site_id)->andWhere(['slug' => $slug])->one();
    }

    /**
     * @param $value
     */
    public function setSites($value)
    {
        $this->_sites = $value;
    }

    /**
     * @inheritdoc
     */
    public function getSites()
    {
        if ($this->_sites !== null) {
            return $this->_sites;
        }

        if ($this->getIsNewRecord()) {
            return [];
        }

        $sites = (new Query())
            ->select('site_id')
            ->from('{{%site_page}}')
            ->where(['page_id' => $this->page_id])
            ->all();

        return ArrayHelper::getColumn($sites, 'site_id');
    }

    /**
     * @param bool $insert
     * @param array $changedAttributes
     *
     * @throws Exception
     */
    public function afterSave($insert, $changedAttributes)
    {
        foreach ($this->sites as $site) {
            $sites[] = [(int)$this->page_id, $site];
        }

        if (empty($sites)) {
            //TODO заменить на вменяемый текст ошибки
            throw new Exception('No sites error');
        }

        \Yii::$app->getDb()->createCommand()
            ->delete('{{%site_page}}', ['page_id' => $this->page_id])
            ->execute();

        \Yii::$app->getDb()->createCommand()
            ->batchInsert('{{%site_page}}', ['page_id', 'site_id'], $sites)
            ->execute();

        parent::afterSave($insert, $changedAttributes);
    }

    /**
     * @inheritdoc
     */
    public function afterDelete()
    {
        \Yii::$app->getDb()->createCommand()
            ->delete('{{%site_page}}', ['page_id' => $this->page_id])
            ->execute();

        parent::afterDelete();
    }
}