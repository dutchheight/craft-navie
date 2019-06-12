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

use dutchheight\navie\Navie;
use dutchheight\navie\models\Settings;

use Craft;
use craft\web\Controller;
use craft\helpers\UrlHelper;

use yii\web\Response;
use yii\web\NotFoundHttpException;
use yii\web\ForbiddenHttpException;


/**
 * @author    Dutch Height
 * @package   Navie
 * @since     1.0.0
 */
class SettingsController extends Controller
{
    // Public Methods
    // =========================================================================

    public function actionPluginSettings($settings = null): Response
    {
        $variables = [];

        $currentUser = Craft::$app->getUser()->getIdentity();
        if ($currentUser === null || !$currentUser->can('navie:settings')) {
            throw new ForbiddenHttpException(Craft::t('navie', 'Your account does not have the right permissions.'));
        }

        if (!$settings) {
            $settings = Navie::$settings;
        }

        /** @var Settings $settings */
        $variables['pluginName'] = $settings->pluginName;
        $variables['selectedNavItem'] = 'general';
        $variables['crumbs'] = [
            ['label' => Craft::t('app', 'Settings'), 'url' => UrlHelper::cpUrl('settings')]
        ];
        $variables['settings'] = $settings;

        return $this->renderTemplate('navie/settings/general', $variables);
    }

    /**
     * Saves the general plugin settings.
     *
     * @return yii\web\Response|null
     * @throws yii\web\ForbiddenHttpException if the current user does not have the right permission
     * @throws yii\web\NotFoundHttpException if the requested plugin cannot be found.
     */
    public function actionSaveGeneral()
    {
        $this->requirePostRequest();

        $currentUser = Craft::$app->getUser()->getIdentity();
        if ($currentUser === null || !$currentUser->can('navie:settings')) {
            throw new ForbiddenHttpException(Craft::t('navie', 'Your account does not have the right permissions.'));
        }

        $handle = Craft::$app->getRequest()->getRequiredBodyParam('pluginHandle');
        $plugin = Craft::$app->getPlugins()->getPlugin($handle);

        $settings['pluginName'] = Craft::$app->getRequest()->getBodyParam('pluginName', 'Navie');

        if (!$plugin) {
            throw new NotFoundHttpException(Craft::t('app', 'Plugin not found.'));
        }

        $success = Craft::$app->getPlugins()->savePluginSettings($plugin, $settings);
        if (!$success) {
            Craft::$app->getSession()->setError(Craft::t('app', "Couldn't sav plugin settings."));

            // Send the plugin back to the template
            Craft::$app->getUrlManager()->setRouteParams([
                'plugin' => $plugin,
            ]);
            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('app', 'Plugin settings saved.'));
        return $this->redirectToPostedUrl();
    }
}
