<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPaymentPayPalUnified\Setup\Versions;

use Doctrine\DBAL\Connection;
use PDO;
use Shopware\Bundle\AttributeBundle\Service\CrudService;

class UpdateToREPLACE_GLOBAL_WITH_NEXT_VERSION
{
    /**
     * @var CrudService
     */
    private $crudService;

    /**
     * @var Connection
     */
    private $connection;

    public function __construct(
        CrudService $crudService,
        Connection $connection
    ) {
        $this->crudService = $crudService;
        $this->connection = $connection;
    }

    /**
     * @return void
     */
    public function update()
    {
        $this->createAttributes();
        $this->migrateLanguageSettings();
    }

    /**
     * @return void
     */
    private function createAttributes()
    {
        $this->crudService->update('s_order_attributes', 'swag_paypal_unified_carrier_was_sent', 'boolean');

        $this->crudService->update('s_order_attributes', 'swag_paypal_unified_carrier', 'string', [
            'displayInBackend' => true,
            'label' => 'Carrier code',
            'helpText' => 'Enter a PayPal carrier code (e.g. DHL_GLOBAL_ECOMMERCE)...',
            'translatable' => true,
            'supportText' => 'PayPal offers tracking for orders processed through PayPal. To use this, specify a default shipping carrier, which can be overwritten in the orders. Find a list of all shipping providers <a target="_blank" href="https://developer.paypal.com/docs/tracking/reference/carriers/">here</a>',
            'position' => 100,
        ]);

        $this->crudService->update('s_premium_dispatch_attributes', 'swag_paypal_unified_carrier', 'string', [
            'displayInBackend' => true,
            'label' => 'Carrier code',
            'helpText' => 'Enter a PayPal carrier code (e.g. DHL_GLOBAL_ECOMMERCE)...',
            'translatable' => true,
            'supportText' => 'PayPal offers tracking for orders processed through PayPal. To use this, specify a default shipping carrier, which can be overwritten in the orders. Find a list of all shipping providers <a target="_blank" href="https://developer.paypal.com/docs/tracking/reference/carriers/">here</a>',
            'position' => 100,
        ]);
    }

    /**
     * @return void
     */
    private function migrateLanguageSettings()
    {
        $buttonLocals = $this->connection->query('SELECT shop_id, button_locale from swag_payment_paypal_unified_settings_general')->fetchAll(PDO::FETCH_KEY_PAIR);
        $buttonExpressLocals = $this->connection->query('SELECT shop_id, button_locale from swag_payment_paypal_unified_settings_express')->fetchAll(PDO::FETCH_KEY_PAIR);

        foreach ($buttonLocals as $shopId => $buttonLocal) {
            if (empty($buttonLocal) && !empty($buttonExpressLocals[$shopId])) {
                $this->connection->prepare('UPDATE swag_payment_paypal_unified_settings_general SET button_locale = :button WHERE shop_id = :shopId')->execute(['shopId' => $shopId, 'button' => $buttonExpressLocals[$shopId]]);
            }
        }

        $this->connection->exec('ALTER TABLE swag_payment_paypal_unified_settings_express DROP COLUMN button_locale');
    }
}
