<?php
/**
 * 2019 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * @param \Stripe $module
 *
 * @return bool
 * @throws PrestaShopException
 */
function upgrade_module_1_7_0($module)
{
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'stripe_review` ADD `id_payment_intent` VARCHAR(255) NULL');
    Db::getInstance()->execute('ALTER TABLE `'._DB_PREFIX_.'stripe_review` ADD `captured` TINYINT(1) NOT NULL DEFAULT \'0\'');
    Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'stripe_review` SET `captured` = 1 WHERE `status` IN (0, 4)');
    return true;
}
