<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 *  @author    thirty bees <modules@thirtybees.com>
 *  @copyright 2017-2024 thirty bees
 *  @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

use Stripe\Exception\ApiErrorException;
use StripeModule\StripeReview;
use StripeModule\StripeTransaction;
use StripeModule\Utils;
use StripeModule\Logger\Logger;
use StripeModule\Logger\FileLogger;

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class StripeHookModuleFrontController
 */
class StripeHookModuleFrontController extends ModuleFrontController
{
    /**
     * @var Stripe $module
     */
    public $module;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * StripeHookModuleFrontController constructor.
     *
     * @throws PrestaShopException
     */
    public function __construct()
    {
        parent::__construct();

        $this->ssl = Tools::usingSecureMode();
        $this->logger = new FileLogger();
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
     * @throws Throwable
     */
    public function postProcess()
    {
        if (! headers_sent()) {
            header('Content-Type: text/plain');
        }

        $response = '[' . $this->logger->getCorrelationId() . '] ';
        try {
            $this->logger->log("Hook event start");
            $processResponse = $this->processEvent();
            $response .= $processResponse;
            $this->logger->log($processResponse);
        } catch (Throwable $e) {
            $this->logger->exception($e);
            $response .= 'error';
            throw $e;
        } finally {
            $this->logger->log("Hook event end");
            die($response);
        }
    }

    /**
     * @return string
     * @throws ApiErrorException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function processEvent()
    {
        if (!Module::isEnabled('stripe')) {
            $this->logger->error("Module is not enabled");
            return 'Module not enabled';
        }

        if (! Utils::hasValidConfiguration()) {
            $this->logger->error("Invalid stripe configuration");
            return 'Invalid stripe configuration';
        }

        $body = file_get_contents('php://input');
        if (empty($body)) {
            $this->logger->error("Empty payload");
            return 'Empty payload';
        }

        $data = json_decode($body, true);
        if (! $data) {
            $this->logger->error("Failed to parse input payload: " . $body);
            return 'Failed to parse input';
        }

        if (! isset($data['id'])) {
            $this->logger->log("Payload does not contain event id: " . json_encode($data, JSON_PRETTY_PRINT));
            return 'Payload does not contain event id';
        }

        $event = $this->getEvent((string)$data['id']);
        $this->logger->log("Fetched event: " . json_encode($event, JSON_PRETTY_PRINT));

        switch ($event->type) {
            case 'review.closed':
                return $this->processApproved($event);
            case 'charge.refunded':
                return $this->processRefund($event);
            case 'charge.succeeded':
                return $this->processSucceeded($event);
            case 'charge.captured':
                return $this->processCaptured($event);
            case 'charge.failed':
                return $this->processFailed($event);
            default:
                return 'Ignoring event "'.$event->type.'"';
        }
    }

    /**
     * Process `charge.succeeded` event
     *
     * @param \Stripe\Event $event
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function processSucceeded($event): string
    {
        /** @var \Stripe\Charge $charge */
        $charge = $event->data['object'];


        $chargeId = $charge->id;
        $pendingTransaction = StripeTransaction::findPendingChargeTransaction($charge->id);
        if (! $pendingTransaction) {
            return 'No pending transaction for charge id ' . $chargeId;
        }

        $idOrder = (int)$pendingTransaction['id_order'];
        if (! $idOrder) {
            return 'No order found for pending charge id ' . $chargeId;
        }

        $order = new Order($idOrder);
        if (! Validate::isLoadedObject($order)) {
            $this->logger->error("Order with id $idOrder not found");
            return "Order with id $idOrder not found";
        }
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

        $status = (int)Configuration::get(Stripe::STATUS_VALIDATED);
        $orderHistory = new OrderHistory();
        $orderHistory->id_order = $order->id;
        $orderHistory->changeIdOrderState($status, $idOrder);
        $orderHistory->addWithemail(true);

        $this->logger->log("Setting status for order id $idOrder to $status");
        return 'Order id ' . $idOrder . ' validated';
    }

    /**
     * Process `charge.failed` event
     *
     * @param \Stripe\Event $event
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function processFailed($event): string
    {
        /** @var \Stripe\Charge $charge */
        $charge = $event->data['object'];

        $chargeId = $charge->id;
        $pendingTransaction = StripeTransaction::findPendingChargeTransaction($charge->id);
        if (! $pendingTransaction) {
            return 'Po pending transaction for charge id ' . $chargeId;
        }

        $idOrder = (int)$pendingTransaction['id_order'];
        if (! $idOrder) {
            return 'No order found for pending charge id ' . $chargeId;
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

        $status = (int)Configuration::get('PS_OS_CANCELED');
        $orderHistory = new OrderHistory();
        $orderHistory->id_order = $order->id;
        $orderHistory->changeIdOrderState($status, $idOrder);
        $orderHistory->addWithemail(true);

        $this->logger->log("Setting status for order id $idOrder to $status");
        return 'Order id ' . $idOrder . ' marked as cancelled';
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
    protected function processApproved($event): string
    {
        $charge = \Stripe\Charge::retrieve($event->data['object']->charge);

        if (!empty($charge['metadata']['from_back_office'])) {
            return 'Not processed';
        }

        if (!$idOrder = StripeTransaction::getIdOrderByCharge($charge->id)) {
            return 'Order not found for charge ' . $charge->id;
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
            $status = (int)Configuration::get(Stripe::STATUS_AUTHORIZED);
            $orderHistory = new OrderHistory();
            $orderHistory->id_order = $idOrder;
            $orderHistory->changeIdOrderState($status, $idOrder, !$order->hasInvoice());
            $orderHistory->addWithemail(true);
            $this->logger->log("Setting status for order id $idOrder to $status");
            return 'Order id ' . $idOrder . ' marked as authorized';
        }

        return 'ok';
    }

    /**
     * Process `charge.approved` event
     *
     * @param \Stripe\Event $event
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function processCaptured($event): string
    {
        /** @var \Stripe\Charge $charge */
        $charge = $event->data['object'];

        if (!empty($charge['metadata']['from_back_office'])) {
            return 'Not processed';
        }

        if (!$idOrder = StripeTransaction::getIdOrderByCharge($charge->id)) {
            return 'Order not found for charge ' . $charge->id;
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

        return 'Captured payment for order id ' . $idOrder;
    }

    /**
     * Process `charge.refund` event
     *
     * @param \Stripe\Event $event
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function processRefund($event): string
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
                return 'Not processed';
            }
        }

        if (!$idOrder = StripeTransaction::getIdOrderByCharge($charge->id)) {
            return 'Order not found for charge ' . $charge->id;
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

        return 'Refuned processed';
    }

    /**
     * @param string $eventId
     *
     * @return \Stripe\Event
     * @throws ApiErrorException
     */
    public function getEvent($eventId)
    {
        $api = $this->module->getStripeApi();
        return $api->getEvent($eventId);
    }
}
