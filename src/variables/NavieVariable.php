<?php
/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\variables;

use dutchheight\navie\Navie;
use dutchheight\navie\elements\ListItem;

use Craft;
use craft\web\View;
use craft\elements\db\ElementQueryInterface;

/**
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
class NavieVariable
{
    // Public Methods
    // =========================================================================

    /**
     * Get the plugin's name
     *
     * @return null|string
     */
    public function getPluginName()
    {
        return Navie::$plugin->name;
    }

    /**
     * Render a list with a pre-built template
     *
     * @param string $handle
     * @param array $options
     * @return void
     */
    public function render(string $handle, array $options = null)
    {
        $view = Craft::$app->getView();
        $view->setTemplateMode(View::TEMPLATE_MODE_CP);

        echo $view->renderTemplate('navie/render/_index', [
            'items' => Navie::$plugin->getLists()->getListItemsForRender($handle),
            'options' => $options
        ]);

        $view->setTemplateMode(View::TEMPLATE_MODE_SITE);
    }

    /**
     * Returns all list items for the current site by a given list handle
     *
     * @param string $handle
     */
    public function raw(string $handle, $siteId = null)
    {
        return Navie::$plugin->getLists()->getListItemsForRender($handle, $siteId);
    }

    /**
     * Adds a `craft.navie.items()` function to the templates (like `craft.entries()`)
     *
     * @param array $criteria
     * @return ElementQueryInterface
     */
    public function items(array $criteria = null): ElementQueryInterface
    {
        $query = ListItem::find();

        if ($criteria) {
            Craft::configure($query, $criteria);
        }

        return $query;
    }
}
