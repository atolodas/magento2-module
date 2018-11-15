<?php
/**
 * Payment plan campaign model
 *
 * @package Webbhuset_SveaWebpay
 * @module  Webbhuset
 * @author  Webbhuset <info@webbhuset.se>
 */

namespace Webbhuset\SveaWebpay\Model;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Svea\WebPay\Helper\Helper;
use Svea\WebPay\WebPay;
use Webbhuset\SveaWebpay\Model\Config\Api\Configuration;

class Campaign
{
    const XML_PATH_CAMPAIGNS = 'payment/svea_paymentplan/campaigns';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;
    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $storeManager;

    /**
     * Campaign constructor.
     * @param Configuration $apiConfig
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Configuration $apiConfig,
        ScopeConfigInterface $scopeConfig,
        \Magento\Store\Model\StoreManagerInterface $storeManager
    ) {
        $this->apiConfig = $apiConfig;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;

    }

    /**
     * Fetch payment plan campaigns from svea
     *
     * @return array|\Svea\WebPay\WebService\WebServiceResponse\PaymentPlanParamsResponse
     * @throws \Exception
     */
    public function fetchCampaignsFromSvea($params)
    {
        $scopeId = isset($params['scope_id']) ? $params['scope_id'] : null;

        if ($params['scope'] === 'websites') {
            $code = $this->getWebsiteCodeById($scopeId);
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_WEBSITE;
        } else {
            $code = $this->getStoreCodeById($scopeId);
            $scope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        }

        $countryCode = $this->scopeConfig->getValue(
            \Magento\Directory\Helper\Data::XML_PATH_DEFAULT_COUNTRY,
            $scope,
            $code
        );

        if (!$countryCode) {
            $message = 'Country code is not set. Set it in General -> General -> Country Options -> Default Country';
            throw new \Exception($message);
        }

        $request = WebPay::getPaymentPlanParams($this->apiConfig)
            ->setCountryCode($countryCode);
        $response = $request->doRequest();

        if (!$response->accepted) {
            return [];
        }

        return $response;
    }

    /**
     * Calculate price per month
     *
     * @param $amount
     * @return \Svea\WebPay\WebService\GetPaymentPlanParams\PaymentPlanPricePerMonth
     */
    public function getPricePerMonth($amount)
    {
        $campaigns = $this->apiConfig->getCampaigns();

        $paymentPlanParams = json_decode($campaigns);

        $response = Helper::paymentPlanPricePerMonth($amount, $paymentPlanParams, true);

        return $response;
    }

    /**
     * Get saved campaigns
     *
     * @return array
     */
    public function getPaymentplayCampaigns()
    {
        $campaignDataJson = $this->scopeConfig
            ->getValue(self::XML_PATH_CAMPAIGNS, \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

        $campaignData = json_decode($campaignDataJson);

        if (!$campaignData) {
            return [];
        }

        return $campaignData->campaignCodes;
    }

    protected function getStoreCodeById($id)
    {
        $store = $this->storeManager->getStore($id);

        return $store->getCode();
    }

    protected function getWebsiteCodeById($id)
    {
        $website = $this->storeManager->getWebsite($id);

        return $website->getCode();
    }
}
