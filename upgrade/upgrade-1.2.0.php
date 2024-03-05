<?php
/**
 * 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017-2018 thirty bees
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * @return true
 * @throws PrestaShopException
 */
function upgrade_module_1_2_0()
{
    foreach (Shop::getShops(true) as $shop) {
        $secretKey = Configuration::get('STRIPE_SECRET_KEY', null, $shop['id_shop_group'], $shop['id_shop']);
        $publishableKey = Configuration::get('STRIPE_PUBLISHABLE_KEY', null, $shop['id_shop_group'], $shop['id_shop']);
        $publishableKeyLive = $secretKeyLive = $publishableKeyTest = $secretKeyTest = '';
        $goLive = false;
        if (substr($secretKey, 0, 7) === 'sk_live') {
            $goLive = true;
            $secretKeyLive = $secretKey;
        } else {
            $secretKeyTest = $secretKey;
        }
        if (substr($publishableKey, 0, 7) === 'pk_live') {
            $goLive = true;
            $publishableKeyLive = $publishableKey;
        } else {
            $publishableKeyTest = $publishableKey;
        }

        Configuration::updateValue('STRIPE_SECRET_KEY_LIVE', $secretKeyLive, false, $shop['id_shop_group'], $shop['id_shop']);
        Configuration::updateValue('STRIPE_PUBLISHABLE_KEY_LIVE', $publishableKeyLive, false, $shop['id_shop_group'], $shop['id_shop']);
        Configuration::updateValue('STRIPE_SECRET_KEY_TEST', $secretKeyTest, false, $shop['id_shop_group'], $shop['id_shop']);
        Configuration::updateValue('STRIPE_PUBLISHABLE_KEY_TEST', $publishableKeyTest, false, $shop['id_shop_group'], $shop['id_shop']);
        Configuration::updateValue('STRIPE_GO_LIVE', $goLive, false, $shop['id_shop_group'], $shop['id_shop']);
    }
    Configuration::deleteByName('STRIPE_SECRET_KEY');
    Configuration::deleteByName('STRIPE_PUBLISHABLE_KEY');

    return true;
}
