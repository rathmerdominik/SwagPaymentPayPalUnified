<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace SwagPaymentPayPalUnified\Components;

use SwagPaymentPayPalUnified\Components\Structs\Basket\Payer;
use SwagPaymentPayPalUnified\Components\Structs\Basket\RedirectUrls;
use SwagPaymentPayPalUnified\Components\Structs\Payment\Payment;
use SwagPaymentPayPalUnified\Components\Structs\Basket\Transactions;
use SwagPaymentPayPalUnified\Components\Structs\Basket\Transactions\Amount;
use SwagPaymentPayPalUnified\Components\Structs\Basket\Transactions\AmountDetails;
use SwagPaymentPayPalUnified\Components\Structs\Basket\Transactions\ItemList;
use SwagPaymentPayPalUnified\SDK\Structs\WebProfile;
use Shopware\Components\Routing\Router;

class BasketService
{
    /** @var Router $router */
    protected $router;

    /** @var array $basketData */
    private $basketData;

    /** @var array $userData */
    private $userData;

    /**
     * Checkout constructor.
     * @param Router $router
     */
    public function __construct(Router $router)
    {
        $this->router = $router;
    }

    /**
     * @param WebProfile $profile
     * @param array $basketData
     * @param array $userData
     * @return array
     */
    public function getRequestParameters(WebProfile $profile, array $basketData, array $userData)
    {
        $this->basketData = $basketData;
        $this->userData = $userData;

        $requestParameters = new Payment();
        $requestParameters->setIntent('sale');
        $requestParameters->setProfile($profile->getId());

        $payer = new Payer();
        $payer->setMethod('paypal');

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setCancelUrl($this->getRedirectUrl('cancel'));
        $redirectUrls->setReturnUrl($this->getRedirectUrl('return'));

        $amount = new Amount();
        $amount->setDetails($this->getAmountDetails());
        $amount->setCurrency($basketData['sCurrencyName']);
        $amount->setTotal(number_format($this->getTotalAmount(), 2, '.', ','));

        $itemList = new ItemList();
        $itemList->setItems($this->getItemList());

        $transactions = new Transactions();
        $transactions->setAmount($amount);
        $transactions->setItemList($itemList);

        $requestParameters->setPayer($payer);
        $requestParameters->setRedirectUrls($redirectUrls);
        $requestParameters->setTransactions($transactions);

        return $requestParameters->toArray();
    }

    /**
     * @return float
     */
    private function getTotalAmount()
    {
        //Case 1: Show gross prices in shopware and don't exclude country tax
        if ($this->showGrossPrices() && !$this->useNetPriceCalculation()) {
            return $this->basketData['AmountNumeric'];
        //Case 2: Show net prices in shopware and don't exclude country tax
        } elseif (!$this->showGrossPrices() && !$this->useNetPriceCalculation()) {
            return $this->basketData['sAmountWithTax'];
        }

        //Case 3: No tax handling at all, just use the net amounts.
        return $this->basketData['AmountNetNumeric'];
    }

    /**
     * @return array
     */
    private function getItemList()
    {
        $list = [];
        $lastCustomProduct = null;

        foreach ($this->basketData['content'] as $basketItem) {
            $sku = $basketItem['ordernumber'];
            $name = $basketItem['articlename'];
            $quantity = (int)$basketItem['quantity'];

            $price =  $this->showGrossPrices() === true
                ? str_replace(',', '.', $basketItem['price'])
                : $basketItem['netprice'];

            // Add support for custom products
            if (!empty($basketItem['customProductMode'])) {
                switch ($basketItem['customProductMode']) {
                    case 1: // Product
                        $lastCustomProduct = count($list);
                        break;
                    case 2: // Option
                        if (empty($sku) && isset($list[$lastCustomProduct])) {
                            $sku = $list[$lastCustomProduct]['sku'];
                        }
                        break;
                    case 3: // Value
                        $last = count($list) - 1;
                        if (isset($list[$last])) {
                            if (strpos($list[$last]['name'], ': ') === false) {
                                $list[$last]['name'] .= ': ' . $name;
                            } else {
                                $list[$last]['name'] .= ', ' . $name;
                            }
                            $list[$last]['price'] += $price;
                        }
                        continue 2;
                    default:
                        break;
                }
            }

            $list[] = [
                'name' => $name,
                'sku' => $sku,
                'price' => $price,
                'currency' => $this->basketData['sCurrencyName'],
                'quantity' => $quantity
            ];
        }

        return $list;
    }

    /**
     * @param string $action
     * @return false|string
     */
    private function getRedirectUrl($action)
    {
        return $this->router->assemble(
            [
                'controller' => 'payment_paypal',
                'action' => $action,
                'forceSecure' => true
            ]
        );
    }

    /**
     * @return AmountDetails
     */
    private function getAmountDetails()
    {
        $amountDetails = new AmountDetails();

        if ($this->showGrossPrices() && !$this->useNetPriceCalculation()) {
            $amountDetails->setShipping($this->basketData['sShippingcostsWithTax']);
            $amountDetails->setSubTotal(str_replace(',', '.', $this->basketData['Amount']));
            $amountDetails->setTax(number_format(0, 2, '.', ','));

        //Case 2: Show net prices in shopware and don't exclude country tax
        } elseif (!$this->showGrossPrices() && !$this->useNetPriceCalculation()) {
            $amountDetails->setShipping($this->basketData['sShippingcostsNet']);
            $amountDetails->setSubTotal(str_replace(',', '.', $this->basketData['AmountNet']));
            $amountDetails->setTax($this->basketData['sAmountTax']);

            //Case 3: No tax handling at all, just use the net amounts.
        } else {
            $amountDetails->setShipping($this->basketData['sShippingcostsNet']);
            $amountDetails->setSubTotal(str_replace(',', '.', $this->basketData['AmountNet']));
        }

        return $amountDetails;
    }

    /**
     * Returns a value indicating whether or not the current customer
     * uses the net price instead of the gross price.
     *
     * @return bool
     */
    private function showGrossPrices()
    {
        return (bool) $this->userData['additional']['show_net'];
    }

    /**
     * Returns a value indicating whether or not only the net prices without
     * any tax should be used in the total amount object.
     *
     * @return bool
     */
    private function useNetPriceCalculation()
    {
        return (bool) $this->userData['additional']['country']['taxfree'];
    }
}
