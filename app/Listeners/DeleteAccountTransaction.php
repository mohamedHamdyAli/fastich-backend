<?php

namespace App\Listeners;

use App\AccountTransaction;
use App\Utils\TransactionUtil;
use App\Utils\Util;

class DeleteAccountTransaction
{
    protected $transactionUtil;
    protected $commonUtil;

    /**
     * Constructor
     *
     * @param TransactionUtil $transactionUtil
     * @return void
     */
    public function __construct(TransactionUtil $transactionUtil, Util $commonUtil)
    {
        $this->transactionUtil = $transactionUtil;
        $this->commonUtil = $commonUtil;
    }

    /**
     * Handle the event.
     *
     * @param  object  $event
     * @return void
     */
    public function handle($event)
    {
        //Add contact advance if exists
        if ($event->transactionPayment->method == 'advance') {
            $this->transactionUtil->updateContactBalance($event->transactionPayment->payment_for, $event->transactionPayment->amount);
        }

        $business_id = !empty(session('user.business_id')) ? session('user.business_id') : null;
        if (!$this->commonUtil->isModuleEnabled('payment_account_module', $business_id)) {
            return true;
        }

        AccountTransaction::where('account_id', $event->transactionPayment->account_id)
            ->where('transaction_payment_id', $event->transactionPayment->id)
            ->delete();
    }
}
