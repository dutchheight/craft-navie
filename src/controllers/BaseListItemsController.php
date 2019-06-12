<?php
/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\controllers;

use dutchheight\navie\elements\ListItem;

use Craft;
use craft\web\Controller;


/**
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
abstract class BaseListItemsController extends Controller
{
    // Protected Methods
    // =========================================================================
    /**
     * Enforces all Edit List item permissions.
     *
     * @param ListItem $listItem
     * @param bool $duplicate
     */
    protected function enforceEditListItemPermissions(ListItem $listItem, bool $duplicate = false)
    {
        $permissionSuffix = ':' . $listItem->getList()->uid;

        if (Craft::$app->getIsMultiSite()) {
            // Make sure they have access to this site
            $this->requirePermission('editSite:' . $listItem->getSite()->uid);
        }

        // Make sure the user is allowed to edit list items in this list
        $this->requirePermission('navie:lists' . $permissionSuffix);

        // Is it a new list item?
        if (!$listItem->id || $duplicate) {
            // Make sure they have permission to create new list items in this list
            $this->requirePermission('navie:lists:create' . $permissionSuffix);
        } else {
            $this->requirePermission('navie:lists:edit' . $permissionSuffix);
        }
    }
}
