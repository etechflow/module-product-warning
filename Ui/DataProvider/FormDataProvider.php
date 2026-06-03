<?php
declare(strict_types=1);

namespace ETechFlow\ProductWarning\Ui\DataProvider;

use ETechFlow\ProductWarning\Model\ResourceModel\Warning\CollectionFactory;
use Magento\Framework\App\Request\Http;
use Magento\Ui\DataProvider\AbstractDataProvider;

class FormDataProvider extends AbstractDataProvider
{
    private $loadedData = null;
    private Http $request;

    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $collectionFactory,
        Http $request,
        array $meta = [],
        array $data = []
    ) {
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data);
        $this->collection = $collectionFactory->create();
        $this->request    = $request;
    }

    public function getData()
    {
        if ($this->loadedData !== null) return $this->loadedData;
        $this->loadedData = [];
        $id = (int)$this->request->getParam('warning_id');
        if ($id) {
            $item = $this->collection->getItemById($id);
            if ($item) {
                $data = $item->getData();
                /* Picker reads from these hidden text inputs as CSV strings. */
                $catIds  = $item->getCategoryIds();
                $prodIds = $item->getProductIds();
                $data['category_ids'] = $catIds  ? implode(',', $catIds)  : '';
                $data['product_ids']  = $prodIds ? implode(',', $prodIds) : '';
                $this->loadedData[$id] = $data;
            }
        }
        return $this->loadedData;
    }
}
