<?php
/**
 * (c) shopware AG <info@shopware.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace SwagPaymentPayPalUnified\Tests\Functional\Components\Services\RiskManagement;

use PHPUnit\Framework\TestCase;
use SwagPaymentPayPalUnified\Components\Services\RiskManagement\RiskManagement;
use SwagPaymentPayPalUnified\Tests\Functional\DatabaseTestCaseTrait;

class RiskManagementServiceTest extends TestCase
{
    use DatabaseTestCaseTrait;

    public function testIsPayPalNotAllowed()
    {
        Shopware()->Front()->setRequest(new \Enlight_Controller_Request_RequestHttp());
        Shopware()->Front()->setResponse(new \Enlight_Controller_Response_ResponseHttp());

        static::assertFalse($this->getRiskManagement()->isPayPalNotAllowed());
    }

    public function testIsPayPalNotAllowedTestAttrIsNot()
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/risk_management_rules_attr_is_not.sql');
        static::assertTrue(\is_string($sql));
        Shopware()->Container()->get('dbal_connection')->exec($sql);

        $request = new \Enlight_Controller_Request_RequestHttp();
        $this->setRequestParameterToFront($request, 'frontend', 'listing');

        Shopware()->Front()->setResponse(new \Enlight_Controller_Response_ResponseHttp());

        static::assertFalse($this->getRiskManagement()->isPayPalNotAllowed(178));
        static::assertTrue($this->getRiskManagement()->isPayPalNotAllowed(37));
    }

    public function testIsPayPalNotAllowedTestAttrIsNotCatagory()
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/risk_management_rules_attr_is_not.sql');
        static::assertTrue(\is_string($sql));
        Shopware()->Container()->get('dbal_connection')->exec($sql);

        $request = new \Enlight_Controller_Request_RequestHttp();
        $this->setRequestParameterToFront($request, 'frontend', 'detail');

        Shopware()->Front()->setResponse(new \Enlight_Controller_Response_ResponseHttp());

        static::assertFalse($this->getRiskManagement()->isPayPalNotAllowed(null, 6));

        $expectedResult = require __DIR__ . '/_fixtures/testAttrIsNot_category_result.php';
        $result = \json_decode(Shopware()->Container()->get('template')->getTemplateVars('riskManagementMatchedProducts'), true);

        foreach ($expectedResult as $index => $resultItem) {
            static::assertSame($resultItem, $result[$index]);
        }
    }

    public function testIsPayPalNotAllowedTestAttrIs()
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/risk_management_rules_attr_is.sql');
        static::assertTrue(\is_string($sql));
        Shopware()->Container()->get('dbal_connection')->exec($sql);

        $request = new \Enlight_Controller_Request_RequestHttp();
        $this->setRequestParameterToFront($request, 'frontend', 'detail');

        Shopware()->Front()->setResponse(new \Enlight_Controller_Response_ResponseHttp());

        static::assertTrue($this->getRiskManagement()->isPayPalNotAllowed(178));
        static::assertFalse($this->getRiskManagement()->isPayPalNotAllowed(37));
    }

    public function testIsPayPalNotAllowedTestAttrIsCategory()
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/risk_management_rules_attr_is.sql');
        static::assertTrue(\is_string($sql));
        Shopware()->Container()->get('dbal_connection')->exec($sql);

        $request = new \Enlight_Controller_Request_RequestHttp();
        $this->setRequestParameterToFront($request, 'frontend', 'detail');

        Shopware()->Front()->setResponse(new \Enlight_Controller_Response_ResponseHttp());

        static::assertFalse($this->getRiskManagement()->isPayPalNotAllowed(null, 6));

        $expectedResult = ['SW10178'];
        $result = \json_decode(Shopware()->Container()->get('template')->getTemplateVars('riskManagementMatchedProducts'), true);

        foreach ($result as $index => $resultItem) {
            static::assertSame($resultItem, $expectedResult[$index]);
        }
    }

    public function testIsPayPalNotAllowedIsProductInCategory()
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/risk_management_rules_product_in_category.sql');
        static::assertTrue(\is_string($sql));
        Shopware()->Container()->get('dbal_connection')->exec($sql);

        $request = new \Enlight_Controller_Request_RequestHttp();
        $this->setRequestParameterToFront($request, 'frontend', 'detail');

        Shopware()->Front()->setResponse(new \Enlight_Controller_Response_ResponseHttp());

        static::assertTrue($this->getRiskManagement()->isPayPalNotAllowed('178'));
    }

    public function testIsPayPalNotAllowedIsProductInCategoryByCategory()
    {
        $sql = \file_get_contents(__DIR__ . '/_fixtures/risk_management_rules_product_in_category.sql');
        static::assertTrue(\is_string($sql));
        Shopware()->Container()->get('dbal_connection')->exec($sql);

        $request = new \Enlight_Controller_Request_RequestHttp();
        $this->setRequestParameterToFront($request, 'frontend', 'detail');

        Shopware()->Front()->setRequest($request);
        Shopware()->Front()->setResponse(new \Enlight_Controller_Response_ResponseHttp());

        static::assertTrue($this->getRiskManagement()->isPayPalNotAllowed(null, '6'));
    }

    private function getRiskManagement()
    {
        return new RiskManagement(
            Shopware()->Container()->get('paypal_unified.dependency_provider'),
            Shopware()->Container()->get('dbal_connection')
        );
    }

    /**
     * @param string $module
     * @param string $controller
     * @param string $action
     */
    private function setRequestParameterToFront(
        \Enlight_Controller_Request_RequestHttp $request,
        $module = 'frontend',
        $controller = 'listing',
        $action = 'index'
    ) {
        $request->setActionName($action);
        $request->setControllerName($controller);
        $request->setModuleName($module);
        Shopware()->Container()->get('front')->setRequest($request);
    }
}
