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

use Stripe\Exception\ApiErrorException;
use StripeModule\StripeReview;
use StripeModule\StripeTransaction;
use StripeModule\Utils;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class StripeHookModuleFrontController
 */
class StripeHookModuleFrontController extends ModuleFrontController
{
    /** @var Stripe $module */
    public $module;

    /**
     * StripeHookModuleFrontController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();
    }

    /**
     * Prevent displaying the maintenance page
     *
     * @return void
     */
    protected function displayMaintenancePage()
    {
    }

    /**
     * Post process
     *
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws ApiErrorException
     */
    public function postProcess()
    {
        if (!Module::isEnabled('stripe')) {
            die('module not enabled');
        }

        if (! Utils::hasValidConfiguration()) {
            die('invalid stripe configuration');
        }

        if (! headers_sent()) {
            header('Content-Type: text/plain');
        }

        $body = file_get_contents('php://input');

        if (!empty($body) && $data = json_decode($body, true)) {

            if (! isset($data['id'])) {
                die('Event id not provided');
            }
            $event = $this->getEvent((string)$data['id']);

            switch ($event->type) {
                case 'review.closed':
                    $this->processApproved($event);

                    break;
                case 'charge.refunded':
                    $this->processRefund($event);

                    break;
                case 'charge.succeeded':
                    $this->processSucceeded($event);

                    break;
                case 'charge.captured':
                    Logger::addLog(json_encode($event));
                    $this->processCaptured($event);

                    break;
                case 'charge.failed':
                    $this->processFailed($event);

                    break;
            }
            die('ok');
        }
        die('Failed to parse input');
    }

    /**
     * Process `charge.succeeded` event
     *
     * @param \Stripe\Event $event
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function processSucceeded($event)
    {
        /** @var \Stripe\Charge $charge */
        $charge = $event->data['object'];


        $chargeId = $charge->id;
        $pendingTransaction = StripeTransaction::findPendingChargeTransaction($charge->id);
        if (! $pendingTransaction) {
            die('no pending transaction for charge id ' . $chargeId);
        }

        $idOrder = (int)$pendingTransaction['id_order'];
        if (! $idOrder) {
            die('no order found for pending charge id ' . $chargeId);
        }

        $order = new Order($idOrder);
        $totalAmount = $order->getTotalPaid();

        $transaction = new StripeTransaction();
        $transaction->card_last_digits = 0;
        $transaction->id_charge = $charge->id;
        $transaction->amount = $totalAmount;
        $transaction->id_order = $order->id;
        $transaction->type = StripeTransaction::TYPE_CHARGE;
        $transaction->source = StripeTransaction::SOURCE_WEBHOOK;
        $transaction->source_type = $pendingTransaction['source_type'];
        $transaction->add();

        $orderHistory = new OrderHistory();
        $orderHistory->id_order = $order->id;
        $orderHistory->changeIdOrderState((int) Configuration::get(Stripe::STATUS_VALIDATED), $idOrder);
        $orderHistory->addWithemail(true);
    }

    /**
     * Process `charge.failed` event
     *
     * @param \Stripe\Event $event
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function processFailed($event)
    {
        /** @var \Stripe\Charge $charge */
        $charge = $event->data['object'];

        $chargeId = $charge->id;
        $pendingTransaction = StripeTransaction::findPendingChargeTransaction($charge->id);
        if (! $pendingTransaction) {
            die('no pending transaction for charge id ' . $chargeId);
        }

        $idOrder = (int)$pendingTransaction['id_order'];
        if (! $idOrder) {
            die('no order found for pending charge id ' . $chargeId);
        }

        $order = new Order($idOrder);

        $transaction = new StripeTransaction();
        $transaction->card_last_digits = 0;
        $transaction->id_charge = $charge->id;
        $transaction->amount = 0;
        $transaction->id_order = $order->id;
        $transaction->type = StripeTransaction::TYPE_CHARGE_FAIL;
        $transaction->source = StripeTransaction::SOURCE_WEBHOOK;
        $transaction->source_type = $pendingTransaction['source_type'];
        $transaction->add();

        $orderHistory = new OrderHistory();
        $orderHistory->id_order = $order->id;
        $orderHistory->changeIdOrderState((int) Configuration::get('PS_OS_CANCELED'), $idOrder);
        $orderHistory->addWithemail(true);
    }

    /**
     * Process `charge.approved` event
     *
     * @param \Stripe\Event $event
     *
     * @throws PrestaShopException
     * @throws SmartyException
     * @throws \Stripe\Exception\ApiErrorException
     */
    protected function processApproved($event)
    {
        $charge = \Stripe\Charge::retrieve($event->data['object']->charge);

        if (!empty($charge['metadata']['from_back_office'])) {
            die('not processed');
        }

        if (!$idOrder = StripeTransaction::getIdOrderByCharge($charge->id)) {
            die('no id');
        }
        $order = new Order($idOrder);

        $review = StripeReview::getByOrderId($idOrder);
        $review->status = StripeReview::AUTHORIZED;
        $review->save();

        $transaction = new StripeTransaction();
        $transaction->id_order = $idOrder;
        $transaction->id_charge = $charge->id;
        $transaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
        $transaction->type = StripeTransaction::TYPE_AUTHORIZED;
        $transaction->card_last_digits = (int) StripeTransaction::getLastFourDigitsByChargeId($charge->id);
        $transaction->amount = (int) $charge->amount;
        $transaction->save();

        if (Configuration::get(Stripe::USE_STATUS_AUTHORIZED)) {
            $orderHistory = new OrderHistory();
            $orderHistory->id_order = $idOrder;
            $orderHistory->changeIdOrderState((int) Configuration::get(Stripe::STATUS_AUTHORIZED), $idOrder, !$order->hasInvoice());
            $orderHistory->addWithemail(true);
        }
    }

    /**
     * Process `charge.approved` event
     *
     * @param \Stripe\Event $event
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function processCaptured($event)
    {
        /** @var \Stripe\Charge $charge */
        $charge = $event->data['object'];

        if (!empty($charge['metadata']['from_back_office'])) {
            die('not processed');
        }

        if (!$idOrder = StripeTransaction::getIdOrderByCharge($charge->id)) {
            die('no id');
        }
        $order = new Order($idOrder);

        $review = StripeReview::getByOrderId($idOrder);
        $review->status = StripeReview::CAPTURED;
        $review->save();

        $transaction = new StripeTransaction();
        $transaction->id_order = $idOrder;
        $transaction->id_charge = $charge->id;
        $transaction->source = StripeTransaction::SOURCE_FRONT_OFFICE;
        $transaction->type = StripeTransaction::TYPE_CAPTURED;
        $transaction->card_last_digits = (int) StripeTransaction::getLastFourDigitsByChargeId($charge->id);
        $transaction->amount = (int) $charge->amount;
        $transaction->save();

        $orderHistory = new OrderHistory();
        $orderHistory->id_order = $idOrder;
        $orderHistory->changeIdOrderState((int) Configuration::get('PS_OS_PAYMENT'), $idOrder, !$order->hasInvoice());
        $orderHistory->addWithemail(true);
    }

    /**
     * Process `charge.refund` event
     *
     * @param \Stripe\Event $event
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function processRefund($event)
    {
        /** @var \Stripe\Charge $charge */
        $charge = $event->data['object'];

        $refunds = [];
        $previousAttributes = [];

        if (isset($charge['previous_attributes'][0]['refunds']['data'])) {
            foreach ($charge['previous_attributes'][0]['refunds']['data'] as $previousAttribute) {
                $previousAttributes[] = $previousAttribute['id'];
            }
        }

        // Remove previous attributes
        foreach ($charge['refunds']['data'] as $refund) {
            if (!in_array($refund['id'], $previousAttributes)) {
                $refunds[] = $refund;
            }
        }

        foreach ($refunds as $refund) {
            if (isset($refund['metadata']['from_back_office']) && $refund['metadata']['from_back_office'] == 'true') {
                die('not processed');
            }
        }

        if (!$idOrder = StripeTransaction::getIdOrderByCharge($charge->id)) {
            die('ok');
        }

        $order = new Order($idOrder);

        $totalAmount = Utils::toCurrencyUnitWithIso((string)$charge->currency, $order->getTotalPaid());

        $amountRefunded = (int) $charge->amount_refunded;

        if (Configuration::get(Stripe::USE_STATUS_REFUND) && (int) ($amountRefunded - $totalAmount) === 0) {
            // Full refund
            if (Configuration::get(Stripe::GENERATE_CREDIT_SLIP)) {
                   $fullProductList = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS(
                    (new DbQuery())
                        ->select('od.`id_order_detail`, od.`product_quantity`')
                        ->from('order_detail', 'od')
                        ->where('od.`id_order` = '.(int) $order->id)
                );

                if (is_array($fullProductList) && !empty($fullProductList)) {
                    $productList = [];
                    $quantityList = [];
                    foreach ($fullProductList as $dbOrderDetail) {
                        $idOrderDetail = (int) $dbOrderDetail['id_order_detail'];
                        $productList[] = (int) $idOrderDetail;
                        $quantityList[$idOrderDetail] = (int) $dbOrderDetail['product_quantity'];
                    }
                    OrderSlip::createOrderSlip($order, $productList, $quantityList, $order->getShipping());
                }
            }

            $transaction = new StripeTransaction();
            $transaction->card_last_digits = (int) $charge->source['last4'];
            $transaction->id_charge = $charge->id;
            $transaction->amount = $charge->amount_refunded - StripeTransaction::getRefundedAmount($charge->id);
            $transaction->id_order = $order->id;
            $transaction->type = StripeTransaction::TYPE_FULL_REFUND;
            $transaction->source = StripeTransaction::SOURCE_WEBHOOK;
            $transaction->add();

            $orderHistory = new OrderHistory();
            $orderHistory->id_order = $order->id;
            $orderHistory->changeIdOrderState((int) Configuration::get(Stripe::STATUS_REFUND), $idOrder);
            $orderHistory->addWithemail(true);
        } else {
            $transaction = new StripeTransaction();
            $transaction->card_last_digits = (int) $charge->source['last4'];
            $transaction->id_charge = $charge->id;
            $transaction->amount = $charge->amount_refunded - StripeTransaction::getRefundedAmount($charge->id);
            $transaction->id_order = $order->id;
            $transaction->type = StripeTransaction::TYPE_PARTIAL_REFUND;
            $transaction->source = StripeTransaction::SOURCE_WEBHOOK;
            $transaction->add();

            if (Configuration::get(Stripe::USE_STATUS_PARTIAL_REFUND)) {
                $orderHistory = new OrderHistory();
                $orderHistory->id_order = $order->id;
                $orderHistory->changeIdOrderState((int) Configuration::get(Stripe::STATUS_PARTIAL_REFUND), $idOrder);
                $orderHistory->addWithemail(true);
            }
        }
    }

    /**
     * @param string $eventId
     *
     * @return \Stripe\Event
     */
    public function getEvent($eventId)
    {
        // Verify with Stripe
        try {
            $api = $this->module->getStripeApi();
            $event = $api->getEvent($eventId);
            if ($event) {
                return $event;
            }
        } catch (\Throwable $e) {}
        die('Failed to fetch event ' . $eventId);
    }
}
