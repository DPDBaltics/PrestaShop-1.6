<?php

namespace Invertus\dpdBaltics\Service\Product;

use Carrier;
use DPDBaltics;
use DPDProduct;
use Exception;
use Invertus\dpdBaltics\Collection\DPDProductInstallCollection;
use Invertus\dpdBaltics\Config\Config;
use Invertus\dpdBaltics\Exception\ProductUpdateException;
use Invertus\dpdBaltics\Repository\ProductRepository;
use Invertus\dpdBaltics\Service\Carrier\CreateCarrierService;

class ProductService
{
    /**
     * @var ProductRepository
     */
    private $productRepository;

    /**
     * @var CreateCarrierService
     */
    private $createCarrierService;

    public function __construct(
        ProductRepository $productRepository,
        CreateCarrierService $createCarrierService
    ) {
        $this->productRepository = $productRepository;
        $this->createCarrierService = $createCarrierService;
    }

    /**
     * @param $productId
     * @param $active
     *
     * @param $isCod
     * @throws ProductUpdateException
     */
    public function updateProduct($productId, $active)
    {
        try {
            $dpdProduct = new DPDProduct($productId);
            $dpdProduct->active = $active;
            $dpdProduct->update();
        } catch (Exception $e) {
            throw new ProductUpdateException($e->getMessage());
        };
    }

    public function deleteProduct($productReference)
    {
        $productId = $this->productRepository->getProductIdByProductReference($productReference);

        if (!$productId) {
            return true;
        }
        $product = new DPDProduct($productId);

        $carrier = Carrier::getCarrierByReference($product->id_reference);
        $carrier->deleted = 1;
        $carrier->update();

        return $this->productRepository->deleteByProductReference($product->getProductReference());
    }

    public function addProduct($productReference)
    {
        $collection = new DPDProductInstallCollection();
        $product = Config::getProductByReference($productReference);
        $collection->add($product);

        return $this->createCarrierService->createCarriers($collection);
    }


    public function updateCarriersOnCountryChange($newCountryIsoCode)
    {
        $productId = $this->productRepository->getProductIdByProductReference(Config::PRODUCT_TYPE_PUDO_COD);
        if ($newCountryIsoCode === Config::LATVIA_ISO_CODE) {
            $this->deleteProduct(Config::PRODUCT_TYPE_PUDO_COD);
        } elseif (!$productId) {
            $this->addProduct(Config::PRODUCT_TYPE_PUDO_COD);
        }

        $productId = $this->productRepository->getProductIdByProductReference(Config::PRODUCT_TYPE_SAME_DAY_DELIVERY);
        if ($newCountryIsoCode !== Config::LATVIA_ISO_CODE) {
            $this->deleteProduct(Config::PRODUCT_TYPE_SAME_DAY_DELIVERY);
        } elseif (!$productId) {
            $this->addProduct(Config::PRODUCT_TYPE_SAME_DAY_DELIVERY);
        }

        $productId = $this->productRepository->getProductIdByProductReference(Config::PRODUCT_TYPE_SATURDAY_DELIVERY_COD);
        if ($newCountryIsoCode === Config::LATVIA_ISO_CODE) {
            $this->deleteProduct(Config::PRODUCT_TYPE_SATURDAY_DELIVERY_COD);
        } elseif (!$productId) {
            $this->addProduct(Config::PRODUCT_TYPE_SATURDAY_DELIVERY_COD);
        }

        return true;
    }
}
