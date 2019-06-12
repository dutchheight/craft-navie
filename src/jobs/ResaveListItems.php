<?php

/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\jobs;

use dutchheight\navie\Navie;
use dutchheight\navie\elements\ListItem;

use Craft;
use craft\helpers\App;
use craft\queue\BaseJob;


class ResaveListItems extends BaseJob
{
    // Properties
    // =========================================================================

    /**
     * @var array|null The element criteria that determines which elements should be resaved
     */
    public $criteria;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function execute($queue)
    {
        $cache = Craft::$app->getCache();
        $cache->delete(Navie::LIST_CACHE_KEY . ':' . $this->criteria['siteId'] . ':' . $this->criteria['listHandle']);

        $elements = Navie::$plugin->getLists()->getListItemsCache(
            $this->criteria['listHandle'],
            $this->criteria['siteId']
        );

        $total = count($elements);
        $current = 0;

        /** @var ListItem $element */
        foreach ($elements as $element) {
            $this->setProgress($queue, $current++ / $total);
        }
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function defaultDescription(): string
    {
        return Craft::t('app', 'Resaving {class} elements', [
            'class' => App::humanizeClass(ListItem::class),
        ]);
    }
}
