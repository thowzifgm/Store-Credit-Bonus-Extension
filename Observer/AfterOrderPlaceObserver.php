<?php
namespace Informatics\Walletbonus\Observer;

use Magento\Framework\Event\ObserverInterface;

/**
 * Sales order place  observer
 */
class AfterOrderPlaceObserver implements ObserverInterface 
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_transaction;

    /**
     * @var \Magento\Framework\View\Result\PageFactory
     */
    protected $_storecreditModelFactory;


    public function __construct(
        \Magento\Customer\Model\Session $customerSession,
        \Lof\StoreCredit\Model\TransactionFactory $transaction,
        \Lof\StoreCredit\Model\CustomerFactory $storecreditModelFactory
    )
    {       
        $this->_customerSession = $customerSession;
        $this->_transaction = $transaction;
        $this->_storecreditModelFactory = $storecreditModelFactory;
    }
    /**
     * Update items stock status and low stock date.
     *
     * @param EventObserver $observer
     * @return void
     */
    public function execute(\Magento\Framework\Event\Observer $observer)
    {
        /*
        $order = $observer->getOrder();
        $order->setCanSendNewEmailFlag(false);
        */

        $order = $observer->getEvent()->getOrder();

        $incrementId = $order->getIncrementId();
        $grandTotal  = $order->getGrandTotal();
        $payment     = $order->getPayment();
        $ordrStatus  = $order->getStatus(); // getState() == 'processing'){

        $method      = $payment->getMethodInstance();
        $methodTitle = $method->getTitle();

        $percentage  = 1; 
        $hundread    = 100;
        $onePercent  = ((($grandTotal*$percentage)/$hundread));

        $customerSession  = $this->_customerSession; //Parent class initiated
        $customerId       = $customerSession->getId();

        $creditAccount      = $this->_storecreditModelFactory->create()->load($customerId,'customer_id');
        $balanceStorecredit = $creditAccount->getData('credit');

        $description = "Bonus point recievd by choosing payment method.";

        //echo $affiliateBalance . " ivde ethi ". $balanceStorecredit; exit;
        $_newstorecreditBalance = $onePercent + $balanceStorecredit;

        $_newBonusCredit        = $onePercent;
        //echo $_newstorecreditBalance . " ivde ethi ". $_newaffiliateCredit; exit;

        try{
            
                $creditAccount->setData('credit', $_newstorecreditBalance)->save();

                $dataCredit = array(
                                    'customer_id' => $customerId,
                                    'amount'      => $_newBonusCredit, // transferred amount
                                    'balance'     => $_newstorecreditBalance, //total amount after the transfer
                                    'description' => $description
                                );  
                   
                $transactionDetailsLof = $this->_transaction->create();
                $transactionDetailsLof->setData($dataCredit)->save();

                //$this->messageManager->addSuccessMessage('Transferred the bonus point to store Credit successfully');

            } catch(\Exception $e) {
                    $e->getMessage();
                    /*$this->messageManager->addError(
                        __('We can\'t process your request right now. Sorry, that\'s all we know.')
                    );*/
                    
            }
    }
}
