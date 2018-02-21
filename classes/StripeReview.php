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
 * Class StripeReview
 *
 * @since 1.6.0
 */
class StripeReview extends \ObjectModel
{
    const AUTHORIZED = 1;
    const IN_REVIEW = 2;
    const APPROVED = 3;
    const CAPTURED = 4;
    const RELEASED = 5;

    // @codingStandardsIgnoreStart
    /** @var int $id_order */
    public $id_order;
    /** @var int $status */
    public $status;
    /** @var string $id_review */
    public $id_review = '';
    /** @var string $id_charge */
    public $id_charge = '';
    /** @var bool $test */
    public $test = false;
    // @codingStandardsIgnoreEnd

    /**
     * @see ObjectModel::$definition
     */
    public static $definition = [
        'table'   => 'stripe_review',
        'primary' => 'id_stripe_review',
        'fields'  => [
            'id_order'  => [
                'type'     => self::TYPE_INT,
                'validate' => 'isUnsignedId',
                'required' => true,
                'db_type'  => 'INT(11) UNSIGNED',
            ],
            'status'    => [
                'type'     => self::TYPE_INT,
                'validate' => 'isUnsignedInt',
                'required' => true, 'default' => '0',
                'db_type'  => 'INT(11) UNSIGNED',
            ],
            'id_review' => [
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'db_type'  => 'VARCHAR(255)',
            ],
            'id_charge' => [
                'type'     => self::TYPE_STRING,
                'validate' => 'isString',
                'db_type'  => 'VARCHAR(255)',
            ],
            'test'      => [
                'type'     => self::TYPE_BOOL,
                'validate' => 'isBool',
                'db_type'  => 'TINYINT(1)',
            ]
        ],
    ];

    /**
     * @param int $idOrder
     *
     * @return static
     * @throws \Adapter_Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     *
     * @since 1.6.0
     */
    public static function getByOrderId($idOrder)
    {
        $row = \Db::getInstance(_PS_USE_SQL_SLAVE_)->getRow(
            (new \DbQuery())
                ->select('*')
                ->from(bqSQL(static::$definition['table']))
                ->where('`id_order` = '.(int) $idOrder)
        );

        $review = new static();
        if (is_array($row)) {
            $review->hydrate($row);
        }
        if (!$review->status) {
            // Figure out what happened via the transactions
            $rows = \Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                (new \DbQuery())
                    ->select('`source_type`')
                    ->from(bqSQL(StripeTransaction::$definition['table']))
                    ->where('`id_order` = '.(int) $idOrder)
            );
            if (!is_array($rows)) {
                $rows = [];
            }
            $review->status = 0;
            foreach (array_column($rows, 'type') as $type) {
                if ($type == StripeTransaction::TYPE_CHARGE) {
                    $review->status = static::CAPTURED;
                } elseif (in_array($type, [StripeTransaction::TYPE_PARTIAL_REFUND, StripeTransaction::TYPE_FULL_REFUND])) {
                    $review->status = static::RELEASED;
                    break;
                }
            }
        }
        $review->id_order = (int) $idOrder;

        return $review;
    }

    /**
     * @param int $idOrder
     *
     * @return int
     * @throws \PrestaShopException
     *
     * @since 1.6.0
     */
    public static function getStatusByOrderId($idOrder)
    {
        return (int) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new \DbQuery())
                ->select('`status`')
                ->from(bqSQL(static::$definition['table']))
                ->where('`id_order` = '.(int) $idOrder)
        );
    }

    /**
     * @param int $idOrder
     *
     * @return string
     *
     * @throws \PrestaShopException
     *
     * @since 1.6.0
     */
    public static function getReviewIdByOrderId($idOrder)
    {
        return (string) \Db::getInstance(_PS_USE_SQL_SLAVE_)->getValue(
            (new \DbQuery())
                ->select('`id_review`')
                ->from(bqSQL(static::$definition['table']))
                ->where('`id_order` = '.(int) $idOrder)
        );
    }

    /**
     * @param string $paymentText
     * @param array  $tr
     *
     * @return string
     * @throws \Adapter_Exception
     * @throws \Exception
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \ReflectionException
     * @throws \SmartyException
     */
    public static function displayPaymentText($paymentText, $tr)
    {
        $review = StripeReview::getByOrderId($tr['id_order']);
        if ($review->status) {
            $module = \Module::getInstanceByName('stripe');
            $reflection = new \ReflectionClass($module);

            \Context::getContext()->smarty->assign([
                'paymentText' => $paymentText,
                'tr'          => $tr,
                'status'      => $review->status,
            ]);

            return $module->display($reflection->getFileName(), 'views/templates/admin/payment-list-item.tpl');
        }

        return $paymentText;
    }
}
