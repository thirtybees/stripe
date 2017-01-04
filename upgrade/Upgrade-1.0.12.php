<?php
/**
 * 2016 Michael Dekker
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@michaeldekker.com so we can send you a copy immediately.
 *
 *  @author    Michael Dekker <prestashop@michaeldekker.com>
 *  @copyright 2016 Michael Dekker
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

function upgrade_module_1_0_12($module)
{
    Configuration::deleteByName('MDSTRIPE_LATEST_VERSION');
    Configuration::updateGlobalValue('MDSTRIPE_LATEST_PATCH', '0.0.0');
    Configuration::updateGlobalValue('MDSTRIPE_LATEST_MINOR', '0.0.0');
    Configuration::updateGlobalValue('MDSTRIPE_LATEST_MAJOR', '0.0.0');
    Configuration::updateGlobalValue('MDSTRIPE_AUTO_UPDATE_PATCH', true);

    return Db::getInstance()->execute('ALTER IGNORE TABLE `'._DB_PREFIX_.'stripe_transaction` MODIFY `id_charge` VARCHAR(128) NOT NULL');
}
