<?php
/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie\models;

use dutchheight\navie\Navie;

use Craft;
use craft\base\Model;

/**
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
class Settings extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var string
     */
    public $pluginName = 'Navie';

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['pluginName', 'string'],
            ['pluginName', 'default', 'value' => 'Navie'],
        ];
    }
}
