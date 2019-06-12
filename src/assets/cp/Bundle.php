<?php
/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\assets\cp;

use craft\web\AssetBundle;
use craft\web\assets\cp\CpAsset;

/**
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
class Bundle extends AssetBundle
{
    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        $this->sourcePath = "@dutchheight/navie/assets/cp/dist";

        $this->depends = [
            CpAsset::class,
        ];

        $this->js = [
            'js/LinkTypeSwitcher.js',
            'js/ListItemIndex.js'
        ];

        parent::init();
    }
}
