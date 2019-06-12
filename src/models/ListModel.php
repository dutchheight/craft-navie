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
use dutchheight\navie\records\ListRecord;
use dutchheight\navie\elements\ListItem;

use Craft;
use craft\base\Model;
use craft\validators\HandleValidator;
use craft\validators\UniqueValidator;
use craft\behaviors\FieldLayoutBehavior;

/**
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
class ListModel extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var int|null ID
     */
    public $id;

    /**
     * @var int|null Structure ID
     */
    public $structureId;

    /**
     * @var int|null Field layout ID
     */
    public $fieldLayoutId;

    /**
     * @var string|null Name
     */
    public $name;

    /**
     * @var string|null Handle
     */
    public $handle;

    /**
     * @var int|null Max Levels
     */
    public $maxLevels;

    /**
     * @var bool Propagate list items
     */
    public $propagate = true;

    /**
     * @var DateTime|null Date Created
     */
    public $dateCreated;

    /**
     * @var DateTime|null Date Updated
     */
    public $dateUpdated;

    /**
     * @var string|null UID
     */
    public $uid;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function attributeLabels(): array
    {
        return [
            'name' => Craft::t('app', 'Name'),
            'handle' => Craft::t('app', 'Handle')
        ];
    }

    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'fieldLayout' => [
                'class' => FieldLayoutBehavior::class,
                'elementType' => ListItem::class
            ]
        ];
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        return [
            [['id', 'structureId', 'fieldLayoutId', 'maxLevels'], 'number', 'integerOnly' => true],
            [['handle'], HandleValidator::class, 'reservedWords' => ['id', 'dateCreated', 'dateUpdated', 'uid', 'title']],
            [['name', 'handle'], UniqueValidator::class, 'targetClass' => ListRecord::class],
            [['name', 'handle'], 'required'],
            [['name', 'handle'], 'string', 'max' => 255]
        ];
    }

    /**
     * Use the translated list's name as the string representation.
     *
     * @return string
     */
    public function __toString(): string
    {
        return Craft::t('navie', $this->name);
    }

    /**
     * Returns whether list items in this list support multiple sites.
     *
     * @return bool
     */
    public function getHasMultiSiteListItems(): bool
    {
        return Craft::$app->getIsMultiSite() && $this->propagate;
    }
}
