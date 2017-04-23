<?php

namespace ej\Site\rules;


use Yii;
use yii\web\UrlRule;
use yii\web\Request;
use yii\web\UrlManager;
use yii\web\UrlNormalizer;
use yii\base\InvalidConfigException;
use ej\Site\models\Page as PageModel;

class Page extends UrlRule
{
    /**
     * @var array
     */
    public $normalizer = [
        'class' => 'yii\web\UrlNormalizer',
    ];
    /**
     * @var string
     */
    public $route = 'site/page/index';

    /**
     * Initializes this rule.
     */
    public function init()
    {
        if (is_array($this->normalizer)) {
            $normalizerConfig = array_merge(['class' => UrlNormalizer::className()], $this->normalizer);
            $this->normalizer = Yii::createObject($normalizerConfig);
        }

        if ($this->normalizer !== null && $this->normalizer !== false && !$this->normalizer instanceof UrlNormalizer) {
            throw new InvalidConfigException('Invalid config for UrlRule::normalizer.');
        }
    }

    /**
     * Parses the given request and returns the corresponding route and parameters.
     *
     * @param UrlManager $manager the URL manager
     * @param Request $request    the request component
     *
     * @return array|bool the parsing result. The route and the parameters are returned as an array.
     * If false, it means this rule cannot be used to parse this path info.
     */
    public function parseRequest($manager, $request)
    {
        $suffix = (string)($this->suffix === null ? $manager->suffix : $this->suffix);
        $pathInfo = $request->getPathInfo();

        $normalized = false;
        if ($this->hasNormalizer($manager)) {
            $pathInfo = $this->getNormalizer($manager)->normalizePathInfo($pathInfo, $suffix, $normalized);
        }

        if ($suffix !== '' && $pathInfo !== '') {
            $n = strlen($suffix);
            if (substr_compare($pathInfo, $suffix, -$n, $n) === 0) {
                $pathInfo = substr($pathInfo, 0, -$n);
                if ($pathInfo === '') {
                    return false;
                }
            } else {
                return false;
            }
        }

        $pathInfo = $this->trimSlashes($pathInfo);

        if (preg_match('/^([0-9a-zA-Z_\-\/.])+$/i', $pathInfo, $matches) || empty($pathInfo)) {
            $params = [];

            if (isset($matches[0])) {
                $params['slug'] = $matches[0];
                if (!PageModel::hasPageBySlug($params['slug'])) {
                    return false;
                }
            }

            if ($normalized) {
                return $this->getNormalizer($manager)->normalizeRoute([$this->route, $params]);
            } else {
                return [$this->route, $params];
            }
        }

        return false;
    }

    /**
     * @param UrlManager $manager
     * @param string $route
     * @param array $params
     *
     * @return bool|string
     */
    public function createUrl($manager, $route, $params)
    {
        if ($route === $this->route) {
            if (isset($params['slug'])) {
                $url = $params['slug'];

                $suffix = ($this->suffix === null ? $manager->suffix : $this->suffix);

                if ($url !== '' && !empty($suffix) && $url != '/') {
                    $url .= $suffix;
                }

                return $url;
            }
        }
        return false;
    }

    /**
     * @param string $string
     *
     * @return string
     */
    private function trimSlashes($string)
    {
        if (strpos($string, '//') === 0) {
            return '//' . trim($string, '/');
        }
        return trim($string, '/');
    }
}