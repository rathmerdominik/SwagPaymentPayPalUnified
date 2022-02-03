<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPaymentPayPalUnified\Setup\PaymentModels\PaymentModels;

use Shopware\Models\Payment\Payment;
use SwagPaymentPayPalUnified\Components\PaymentMethodProviderInterface;

class Giropay extends AbstractPaymentModel
{
    /**
     * {@inheritDoc}
     */
    public function create()
    {
        $payment = new Payment();
        $payment->setActive(false);
        $payment->setPosition(self::POSITION_GIROPAY);
        $payment->setName(PaymentMethodProviderInterface::GIROPAY_METHOD_NAME);
        $payment->setDescription('Giropay');
        $payment->setAdditionalDescription($this->getDescription());
        $payment->setAction(self::ACTION_PAYPAL_APM);
        $payment->setPlugin($this->plugin);

        return $payment;
    }

    /**
     * @return string
     */
    private function getDescription()
    {
        $descriptionArray = [
            '<img src="https://www.paypalobjects.com/images/checkout/alternative_payments/paypal_giropay_color.svg" alt="Logo giropay">',
        ];

        return implode(' ', $descriptionArray);
    }
}
