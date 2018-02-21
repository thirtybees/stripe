<?php
/**
 * Copyright (C) 2017-2018 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
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

namespace StripeModule;

if (!defined('_TB_VERSION_')) {
    return;
}

require_once __DIR__.'/../stripe.php';

/**
 * Class StripeTransaction
 */
class StripeTransaction extends \ObjectModel
{
    const TYPE_CHARGE = 1;
    const TYPE_PARTIAL_REFUND = 2;
    const TYPE_FULL_REFUND = 3;
    const TYPE_CHARGE_FAIL = 4;
    const TYPE_CHARGE_PENDING = 5;
    const TYPE_AUTHORIZED = 5;
    const TYPE_IN_REVIEW = 6;
    const TYPE_CAPTURED = 7;

    const SOURCE_FRONT_OFFICE = 1;
    const SOURCE_BACK_OFFICE = 2;
    const SOURCE_WEBHOOK = 3;

    // @codingStandardsIgnoreStart
    /** @var int $id_order */
    public $id_order;
    /** @var int $type */
    public $type;
    /** @var int $source */
    public $source;
    /** @var string $card_last_digits */
    public $card_last_digits;
    /** @var int $id_charge */
    public $id_charge;
    /** @var int $amount */
    public $amount;
    /** @var string $source_type */
    public $source_type;
    /** @var string $date_add */
    public $date_add;
    /** @var string $date_upd */
    public $date_upd;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'stripe_transaction',
        'primary' => 'id_stripe_transaction',
        'fields'  => [
            'id_order'         => [
                'type'     => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'INT(11) UNSIGNED',
            ],
            'type'             => [
                'type'     => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'INT(11) UNSIGNED',
            ],
            'source'           => [
                'type'     => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true,
                'default'  => '0',
                'db_type'  => 'INT(11) UNSIGNED',
            ],
            'card_last_digits' => [
                'type'     => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'size'     => 4,
                'default'  => '0',
                'db_type'  => 'INT(4) UNSIGNED',
            ],
            'id_charge'        => [
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'required' => true,
                'db_type'  => 'VARCHAR(128)',
            ],
            'amount'           => [
                'type'     => self::TYPE_INT,
                'validate' => 'isInt',
                'default'  => '0',
                'db_type'  => 'INT(11) UNSIGNED',
            ],
            'date_add'         => [
                'type'     => self::TYPE_DATE,
                'validate' => 'isDate',
                'db_type'  => 'DATETIME',
            ],
            'date_upd'         => [
                'type'     => self::TYPE_DATE,
                'validate' => 'isDate',
                'db_type'  => 'DATETIME',
            ],
            'source_type'      => [
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'db_type'  => 'VARCHAR(255)',
            ],
        ],
    ];

    /**
     * Get Customer ID by Charge ID
     *
     * @param int $idCharge Charge ID
     *
     * @return int Cart ID
     *
     * @since 1.0.0
     * @throws \PrestaShopException
     */
    public static function getIdCustomerByCharge($idCharge)
    {
        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new \DbQuery())
                ->select('c.`id_customer`')
                ->from(bqSQL(static::$definition['table']), 'st')
                ->innerJoin('orders', 'o', 'st.`id_order` = o.`id_order`')
                ->innerJoin('customer', 'c', 'o.`id_customer` = c.`id_customer`')
                ->where('st.`id_charge` = \''.pSQL($idCharge).'\'')
        );
    }

    /**
     * Get Cart ID by Charge ID
     *
     * @param int $idCharge Charge ID
     *
     * @return int Cart ID
     *
     * @since 1.0.0
     * @throws \PrestaShopException
     */
    public static function getIdCartByCharge($idCharge)
    {
        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new \DbQuery())
                ->select('c.`id_cart`')
                ->from(bqSQL(static::$definition['table']), 'st')
                ->innerJoin('orders', 'o', 'st.`id_order` = o.`id_order`')
                ->innerJoin('cart', 'c', 'o.`id_cart` = c.`id_cart`')
                ->where('st.`id_charge` = \''.pSQL($idCharge).'\'')
        );
    }

    /**
     * Get Order ID by Charge ID
     *
     * @param int $idCharge Charge ID
     *
     * @return int Order ID
     *
     * @since 1.0.0
     * @throws \PrestaShopException
     */
    public static function getIdOrderByCharge($idCharge)
    {
        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new \DbQuery())
                ->select('st.`id_order`')
                ->from(bqSQL(static::$definition['table']), 'st')
                ->where('st.`id_charge` = \''.pSQL($idCharge).'\'')
        );
    }

    /**
     * Get refunded amount by Charge ID
     *
     * @param int $idCharge Charge ID
     *
     * @return int $amount
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public static function getRefundedAmount($idCharge)
    {
        $amount = 0;

        $dbAmounts = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new \DbQuery())
                ->select('st.`amount`')
                ->from(bqSQL(static::$definition['table']), 'st')
                ->where('st.`id_charge` = \''.pSQL($idCharge).'\'')
                ->where('st.`type` = '.static::TYPE_PARTIAL_REFUND.' OR st.`type` = '.static::TYPE_FULL_REFUND)
        );

        if (!is_array($dbAmounts) || empty($dbAmounts)) {
            return $amount;
        }

        foreach ($dbAmounts as $dbAmount) {
            $amount += (int) $dbAmount['amount'];
        }

        return $amount;
    }

    /**
     * Get StripeTransactions by Order ID
     *
     * @param int  $idOrder Order ID
     * @param bool $count   Return amount of transactions
     *
     * @return array|false|\mysqli_result|null|\PDOStatement|resource
     * @throws \PrestaShopDatabaseException
     *
     * @since 1.0.0
     * @throws \PrestaShopException
     */
    public static function getTransactionsByOrderId($idOrder, $count = false)
    {
        $sql = new \DbQuery();
        if ($count) {
            $sql->select('count(*)');
        } else {
            $sql->select('*');
        }
        $sql->from(bqSQL(static::$definition['table']), 'st');
        $sql->innerJoin(bqSQL(\Order::$definition['table']), 'o', 'o.`id_order` = st.`id_order`');
        $sql->innerJoin(bqSQL(\Currency::$definition['table']), 'c', 'c.`id_currency` = o.`id_currency`');
        $sql->where('st.`id_order` = '.(int) $idOrder);

        if ($count) {
            return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue($sql);
        }

        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($sql);
    }

    /**
     * Get refunded amount by Order ID
     *
     * @param int $idOrder Order ID
     *
     * @return int $amount
     *
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.0.0
     */
    public static function getRefundedAmountByOrderId($idOrder)
    {
        $amount = 0;

        $dbAmounts = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
            (new \DbQuery())
                ->select('st.`amount`')
                ->from(bqSQL(static::$definition['table']), 'st')
                ->where('st.`id_order` = \''.pSQL($idOrder).'\'')
                ->where('st.`type` = '.static::TYPE_PARTIAL_REFUND.' OR st.`type` = '.static::TYPE_FULL_REFUND)
        );

        if (!is_array($dbAmounts) || empty($dbAmounts)) {
            return $amount;
        }

        foreach ($dbAmounts as $dbAmount) {
            $amount += (int) $dbAmount['amount'];
        }

        return $amount;
    }

    /**
     * Get Transaction Object by ChargeId
     *
     * @param string $id
     *
     * @return StripeTransaction|false
     *
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @since 1.1.0
     */
    public static function getByChargeId($id)
    {
        $result = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new \DbQuery())
                ->select('st.*')
                ->from(bqSQL(static::$definition['table']), 'st')
                ->where('st.`id_charge` = \''.pSQL($id).'\'')
        );
        if (!$result) {
            return false;
        }

        $transaction = new static();
        $transaction->hydrate($result);

        return $transaction;
    }

    /**
     * Get Charge ID by Order ID
     *
     * @param int $idOrder Order ID
     *
     * @return bool|string Charge ID or false if not found
     *
     * @since 1.0.0
     * @throws \PrestaShopException
     */
    public static function getChargeByIdOrder($idOrder)
    {
        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new \DbQuery())
                ->select('DISTINCT st.`id_charge`')
                ->from(bqSQL(static::$definition['table']), 'st')
                ->where('st.`id_order` = '.(int) $idOrder)
        );
    }

    /**
     * Get last four digits of credit card by Charge ID
     *
     * @param string $idCharge Charge ID
     *
     * @return false|string Last 4 digits of CC
     *
     * @since 1.0.0
     * @throws \PrestaShopException
     */
    public static function getLastFourDigitsByChargeId($idCharge)
    {
        return \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new \DbQuery())
                ->select('DISTINCT st.`card_last_digits`')
                ->from(bqSQL(static::$definition['table']), 'st')
                ->where('st.`id_charge` = \''.pSQL($idCharge).'\'')
        );
    }

    /**
     * Delete a range
     *
     * @param int[] $range
     *
     * @return bool
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     *
     * @since 1.0.0
     */
    public static function deleteRange($range)
    {
        if (!is_array($range) || empty($range)) {
            return false;
        }

        return \Db::getInstance()->delete(
            bqSQL(static::$definition['table']),
            '`'.bqSQL(static::$definition['primary']).'` IN ('.implode(',', array_map('intval', $range)).')'
        );
    }

    /**
     * @param mixed $id
     * @param array $tr
     *
     * @return string
     * @throws \Adapter_Exception
     * @throws \Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \ReflectionException
     * @throws \SmartyException
     *
     * @since 1.6.0
     */
    public static function displayEventLabel($id, $tr)
    {
        $module = \Module::getInstanceByName('stripe');
        $reflection = new \ReflectionClass($module);

        \Context::getContext()->smarty->assign([
            'id' => $id,
            'tr' => $tr,
        ]);

        return $module->display($reflection->getFileName(), 'views/templates/admin/event-label.tpl');
    }

    /**
     * @param int $id
     *
     * @return string
     *
     * @since 1.6.0
     */
    public static function displayCardDigits($id)
    {
        $id = (int) $id;

        if (!$id) {
            return '--';
        }

        return '<code>'.str_pad($id, 4, '0', STR_PAD_LEFT).'</code>';
    }
}
