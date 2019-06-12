<?php
/**
 * Navie plugin for Craft CMS 3.x
 *
 * Simple navigation plugin for Craft CMS 3
 *
 * @link      https://www.dutchheight.com
 * @copyright Copyright (c) 2019 Dutch Height
 */

namespace dutchheight\navie;

use dutchheight\navie\base\PluginTrait;
use dutchheight\navie\services\ListService;
use dutchheight\navie\variables\NavieVariable;
use dutchheight\navie\models\Settings;

use Craft;
use craft\base\Plugin;
use craft\helpers\UrlHelper;
use craft\services\Plugins;
use craft\services\UserPermissions;
use craft\events\PluginEvent;
use craft\web\UrlManager;
use craft\web\twig\variables\CraftVariable;
use craft\events\RegisterUrlRulesEvent;
use craft\events\RegisterUserPermissionsEvent;

use yii\base\Event;
use yii\log\Logger;

/**
 * Class Navie
 *
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 *
 * @property  NavieService $navieService
 */
class Navie extends Plugin
{
    // Traits
    // =========================================================================
    use PluginTrait;

    // Constants
    // =========================================================================

    const FIELD_GROUP_NAME = 'Navie';
    const FIELD_ENTRY_NAME = 'Navie Entry';
    const FIELD_CATEGORY_NAME = 'Navie Category';
    const FIELD_URL_NAME = 'Navie URL';

    const FIELD_ENTRY_HANDLE = 'navieEntry';
    const FIELD_CATEGORY_HANDLE = 'navieCategory';
    const FIELD_URL_HANDLE = 'navieCustomUrl';

    const LIST_CACHE_KEY = 'navie-list';
    const LIST_CACHE_DURATION = 60;

    // Public Properties
    // =========================================================================

    /**
     * @var boolean
     */
    public $hasCpSettings = true;

    /**
     * @var boolean
     */
    public $hasCpSection = true;

    /**
     * @var string
     */
    public $schemaVersion = '1.0.0';

    /**
     * @var bool
     */
    public static $craft31 = false;

    // Public Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        self::$plugin = $this;
        self::$settings = $this->getSettings();
        self::$craft31 = version_compare(Craft::$app->getVersion(), '3.1', '>=');

        $this->name = self::$settings->pluginName;

        $this->registerComponents();
        $this->registerEventListeners();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse()
    {
        return Craft::$app->getResponse()->redirect(UrlHelper::cpUrl('navie/settings'));
    }

        /**
     * @inheritdoc
     */
    public function getCpNavItem()
    {
        $subNavs = [];
        $lists = self::$plugin->getLists()->getEditableLists();
        $navItem = parent::getCpNavItem();

        $currentUser = Craft::$app->getUser()->getIdentity();

        if (!$currentUser) return;

        $editableSettings = true;
        $general = Craft::$app->getConfig()->getGeneral();

        if (self::$craft31 && !$general->allowAdminChanges) {
            $editableSettings = false;
        }

        $editable = $currentUser->can('navie:settings') && $editableSettings;

        if ((count($lists) < 0 && !$currentUser->admin) || (!$editable && !$currentUser->can('navie:lists'))) {
            $navItem = [];
        }

        if ($currentUser->admin) {
            $subNavs['lists'] = [
                'label' => Craft::t('navie', 'Lists'),
                'url' => 'navie/settings/lists',
            ];
        }

        if ($editable) {
            $subNavs['general'] = [
                'label' => Craft::t('app', 'Settings'),
                'url' => 'navie/settings',
            ];
        }

        $navItem = array_merge($navItem, [
            'subnav' => $subNavs,
        ]);;

        return count($navItem) > 1 ? $navItem : null;
    }

    // Protected Methods
    // =========================================================================

    /**
     * @inheritdoc
     */
    protected function createSettingsModel()
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    protected function settingsHtml(): string
    {
        return Craft::$app->view->renderTemplate('navie/settings/general', [
            'settings' => $this->getSettings()
        ]);
    }

    protected function registerEventListeners()
    {
        $request = Craft::$app->getRequest();

        // Install event listeners that are needed every request
        $this->registerGlobalEventListeners();

        // Install only for non-console Control Panel requests
        if ($request->getIsCpRequest() && !$request->getIsConsoleRequest()) {
            $this->registerCpEventListeners();
        }
    }

    protected function registerGlobalEventListeners()
    {
        // Handler: CraftVariable::EVENT_INIT
        Event::on(
            CraftVariable::class,
            CraftVariable::EVENT_INIT,
            function (Event $event) {
                /** @var CraftVariable $variable */
                $variable = $event->sender;
                $variable->set('navie', NavieVariable::class);
            }
        );
    }

    protected function registerCpEventListeners()
    {
        // Handler: UrlManager::EVENT_REGISTER_CP_URL_RULES
        Event::on(
            UrlManager::class,
            UrlManager::EVENT_REGISTER_CP_URL_RULES,
            function (RegisterUrlRulesEvent $event) {
                $event->rules = array_merge(
                    $event->rules,
                    $this->registerCpRoutes()
                );
            }
        );
        // Handler: UserPermissions::EVENT_REGISTER_PERMISSIONS
        Event::on(
            UserPermissions::class,
            UserPermissions::EVENT_REGISTER_PERMISSIONS,
            function (RegisterUserPermissionsEvent $event) {
                // Register our custom permissions
                $event->permissions[Craft::t('navie', 'Navie')] = $this->registerCpPermissions();
            }
        );
    }

    /**
     * Returns the Control Panel user permissions.
     *
     * @return array
     */
    protected function registerCpPermissions()
    {
        $lists = Navie::$plugin->getLists()->getAllLists();
        $permissions = [];

        foreach ($lists as $list) {
            $uid = $list->uid;
            $permissions["navie:lists:${uid}"] = [
                'label' => Craft::t('navie', 'List - {list}', ['list' => $list->name]),
                'nested' => [
                    "navie:lists:create:${uid}" => [
                        'label' => Craft::t('navie', 'Create list items')
                    ],
                    "navie:lists:edit:${uid}" => [
                        'label' => Craft::t('navie', 'Edit list items')
                    ],
                    "navie:lists:delete:${uid}" => [
                        'label' => Craft::t('navie', 'Delete list items')
                    ]
                ]
            ];
        }

        return [
            'navie:lists' => [
                'label' => Craft::t('navie', 'Manage lists'),
                'nested' => $permissions
            ],
            'navie:settings' => [
                'label' => Craft::t('navie', 'Edit Settings')
            ]
        ];
    }

    /**
     * Return the Control Panel routes
     *
     * @return array
     */
    protected function registerCpRoutes() : array
    {
        return [
            'navie' => 'navie/lists/list-item-index',
            'navie/settings' => 'navie/settings/plugin-settings',
            'navie/settings/lists' => 'navie/lists/list-index',
            'navie/settings/lists/new' => 'navie/lists/edit-list',
            'navie/settings/lists/<listId:\d+>' => 'navie/lists/edit-list',

            'navie/<listHandle:{handle}>' => 'navie/lists/list-item-index',
            'navie/<listHandle:{handle}>/new' => 'navie/lists/edit-list-item',
            'navie/<listHandle:{handle}>/new/<siteHandle:{handle}>' => 'navie/lists/edit-list-item',
            'navie/<listHandle:{handle}>/<listItemId:\d+>' => 'navie/lists/edit-list-item',
            'navie/<listHandle:{handle}>/<listItemId:\d+>/<siteHandle:{handle}>' => 'navie/lists/edit-list-item',
            'navie/<siteHandle:{handle}>' => 'navie/lists/list-item-index',
        ];
    }
}
