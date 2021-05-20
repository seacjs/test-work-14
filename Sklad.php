<?php

namespace app\models\shop;

use app\models\YaMarket;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Yii;
use MoySklad\Components\FilterQuery;
use MoySklad\Components\Specs\QuerySpecs\QuerySpecs;
use MoySklad\Entities\Counterparty;
use MoySklad\Entities\Currency;
use MoySklad\Entities\Documents\Orders\CustomerOrder;
use MoySklad\Entities\Misc\CompanySettings;
use MoySklad\Entities\Organization;
use MoySklad\Lists\EntityList;
use MoySklad\MoySklad;
use MoySklad\Entities\Products\Product;
use yii\base\Model;
use yii\helpers\VarDumper;

class Sklad extends Model
{

    public function getSklad() {
        $sklad = MoySklad::getInstance(
            Yii::$app->params['moysklad_login'],
            Yii::$app->params['moysklad_password']
        );
        return $sklad;
    }

    public function getOrganisation($name = null) {
        $organisation = null;
        $name = $name === null ? Yii::$app->params['moysklad_organisation_name'] : $name;
        $organisationList = Organization::query($this->sklad)->search($name);
        if(isset($organisationList[0])) {
            $organisation = $organisationList[0];
        }
        return $organisation;
    }

    public function getCounterparty($email, $phone, $name = '') {
        $counterpartyList = Counterparty::query($this->sklad)
            ->filter(
                (new FilterQuery())
                    ->eq("phone", $phone)
                    ->eq("email", $email)
            );
        if(isset($counterpartyList[0])) {
            $counterparty = $counterpartyList[0];
        } else {
            $counterparty = (new Counterparty($this->sklad, [
                "name" => $name,
                "phone" => $phone,
                "email" => $email
            ]))->create();
        }
        return $counterparty;
    }

    public function addProduct($product) {
        $articles = explode(\app\models\shop\Product::ARTICLE_SEPARATOR,$product->article);
        $article = is_array($articles) ? $articles[0] : $product->article;
        $productItem = (new \MoySklad\Entities\Products\Product($this->sklad, [
            "name" => $product->name,
            "article" => $article
        ]))->create();
        return $productItem;
    }

    public function checkProduct($product)
    {
        $articles = explode(\app\models\shop\Product::ARTICLE_SEPARATOR,$product->article);
        $article = is_array($articles) ? $articles[0] : $product->article;
        $productsFromSklad = \MoySklad\Entities\Products\Product::query($this->sklad)->filter(
            (new FilterQuery())
                ->eq("article", $article)
        );

        return isset($productsFromSklad[0]) ? $productsFromSklad[0] : null;
    }

    /**
     * @param $orderProducts array|OrderProduct
     * @var $orderProduct OrderProduct
     * @return array|EntityList
     */
    public function getProducts($orderProducts)
    {
        $positions = [];
        foreach ($orderProducts as $orderProduct) {
            $productItem = $this->checkProduct($orderProduct->product);
            if($productItem == null) {
                $productItem = $this->addProduct($orderProduct->product);
            }

            $productItem->quantity = $orderProduct->count;
            $productItem->price = $orderProduct->base_price * 100;
            $productItem->discount = $orderProduct->product::calculateDiscountProcent(
                $orderProduct->base_price,
                $orderProduct->{\app\models\shop\Product::PRICE_SORT_FIELD}
            );

            $positions[] = $productItem;
            $positions = new EntityList($this->sklad, $positions);
        }
        return $positions;
    }

    /**
     * @param $yiiOrder Order
     */
    public function addOrder($yiiOrder) {

        $organisation = $this->getOrganisation();
        $counterparty = $this->getCounterparty(
            $yiiOrder->email,
            $yiiOrder->phone,
            $yiiOrder->name
        );
        $positions = $this->getProducts($yiiOrder->orderProducts);

        $order = (new CustomerOrder($this->sklad, [
            'name' => $yiiOrder->tracking_number . time(),
            'description' => 'desc_'.time(),
            'moment' => date('Y-m-d h:i:s'),
            'created' => date('Y-m-d h:i:s')
        ]))->buildCreation()
            ->addCounterparty($counterparty)
            ->addOrganization($organisation)
            ->addPositionList($positions)
            ->execute();
        return $order->fields->id;
    }

    public function checkOrder($id) {
        $orders = CustomerOrder::query($this->sklad)->filter(
            (new FilterQuery())
                ->eq("id", $id)
        );
        return isset($orders[0]) ? $orders[0] : null;
    }


    /**
     * Обновить остатки из файла
     */
    public function updateExistsFromFile() {

        $file = Yii::getAlias('@webroot') . '/exists/file.xls';

        $keyCount = 7;
        $keyArticle = 0;
        $keyName = 3;
        $keyPrice = 11;
        $spreadsheet = IOFactory::load($file);

        $worksheet = $spreadsheet->getActiveSheet();
        $rows = $worksheet->toArray();
        $goodsFromFile = [];
        foreach($rows as $row) {
            if($row[$keyName] != null) {
                $goodsFromFile[$row[$keyArticle]] = $row[$keyCount] == 'Более 10'
                    ? 10
                    : intval($row[$keyCount]);
            }
        }
        unset($goodsFromFile['']);
        $products = \app\models\shop\Product::find()->indexBy('article')->all();
        $ids = []; // ids - продукты что есть в наличии в файле
        foreach ($products as $productKey => $product) {
            if(in_array($productKey, array_keys($goodsFromFile))) {
                $count = $goodsFromFile[$productKey];
                if($count > 0) {
                    $ids[] = $product->id;
                }
            }
        }

        // 0. Перед всем, поствим всем нету на складе [available_in_file]
        Yii::$app->db->createCommand()
            ->update(\app\models\shop\Product::tableName(), ['available_in_file' => 0])
            ->execute();
        Yii::$app->db->createCommand()
            ->update(\app\models\shop\Product::tableName(), ['available_in_file' => 1],['in', 'id', $ids])
            ->execute();
        // а теперь поехали

        $this->updateAvailable();

    }


    public function updateAvailable() {

        // 1. обнуляем все статусы у товаров, у которых есть артикли. Но, кроме тех что есть на моем складе!!!
        Yii::$app->db->createCommand()
            ->update(\app\models\shop\Product::tableName(), [
                'available' => \app\models\shop\Product::AVAILABLE_NEED_TO_CLARIFY
            ],[
                'and',
                ['not', ['article' => '']],
                ['not in', 'old_available', [
                    \app\models\shop\Product::AVAILABLE_OUT_OF_STOCK,
                    \app\models\shop\Product::AVAILABLE_PRE_ORDER
                ]]
            ])
            ->execute();
        Yii::$app->db->createCommand()
            ->update(\app\models\shop\Product::tableName(), [
                'available' => \app\models\shop\Product::AVAILABLE_OUT_OF_STOCK
            ],[
                'and',
                ['not', ['article' => '']],
                ['old_available' => \app\models\shop\Product::AVAILABLE_OUT_OF_STOCK]
            ])
            ->execute();

        // 2. ставим всем наличие у которых нет преордера
        Yii::$app->db->createCommand()
            ->update(\app\models\shop\Product::tableName(), [
                'available' => \app\models\shop\Product::AVAILABLE_IN_STOCK,
            ],[
                'and',
                [
                    'or',
                    ['available_in_file' => 1],
                    ['available_in_stock' => 1],
                ],
                [
                    'not', ['old_available' => \app\models\shop\Product::AVAILABLE_PRE_ORDER]
                ]
            ])
            ->execute();

        $this->refreshYaMarket();
    }

    public function refreshYaMarket() {
        Yii::$app->db->createCommand()
            ->update(\app\models\shop\Product::tableName(), [
                'on_yandex_market' => 0
            ],[
                'not', ['available' => \app\models\shop\Product::AVAILABLE_IN_STOCK]
            ])
            ->execute();

        (new YaMarket())->process();
    }

}
