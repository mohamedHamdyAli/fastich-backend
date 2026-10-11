<?php

namespace App\Listeners;

use App\AccountTransaction;
use App\Utils\Util;

class UpdateAccountTransaction
{
    protected $commonUtil;

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct(Util $commonUtil)
    {
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
        $business_id = !empty(session('user.business_id')) ? session('user.business_id') : null;
        if (!$this->commonUtil->isModuleEnabled('payment_account_module', $business_id)) {
            return true;
        }

        AccountTransaction::updateAccountTransaction($event->transactionPayment, $event->transactionType);
    }
}
