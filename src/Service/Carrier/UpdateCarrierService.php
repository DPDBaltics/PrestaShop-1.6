<?php


namespace Invertus\dpdBaltics\Service\Carrier;

use Carrier;
use DPDProduct;
use Exception;
use Invertus\dpdBaltics\Exception\ProductUpdateException;
use Invertus\dpdBaltics\Service\LanguageService;
use Invertus\dpdBaltics\Validate\Carrier\CarrierUpdateValidate;
use Language;

class UpdateCarrierService
{
    /**
     * @var LanguageService
     */
    private $languageService;
    /**
     * @var CarrierUpdateValidate
     */
    private $carrierUpdateValidate;

    public function __construct(LanguageService $languageService, CarrierUpdateValidate $carrierUpdateValidate)
    {
        $this->languageService = $languageService;
        $this->carrierUpdateValidate = $carrierUpdateValidate;
    }

    /**
     * @param $productId
     * @param $params
     *
     * @throws ProductUpdateException
     */
    public function updateCarrier($productId, $params)
    {
        try {
            $dpdProduct = new DPDProduct($productId);

            $languages = $this->languageService->getShopLanguagesIsoCodes();

            $deliveryTime = [];

            foreach ($languages as $language) {
                $deliveryTime[Language::getIdByIso($language)] =  $params['delivery-time-' . $language];
            }

            $this->carrierUpdateValidate->validateCarrierName($params['carrier-name']);
            $this->carrierUpdateValidate->validateCarrierDeliveryTime($deliveryTime);

            $carrier = Carrier::getCarrierByReference($dpdProduct->id_reference);
            $carrier->delay = $deliveryTime;
            $carrier->name = $params['carrier-name'];
            $carrier->active = $dpdProduct->active;
            $carrier->update();
        } catch (Exception $e) {
            throw new ProductUpdateException($e->getMessage());
        };
    }
}
