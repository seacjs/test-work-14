<?php

namespace app\models\shop;

use app\models\FrontActiveRecord;
use app\models\shop\discount\DiscountPromo;
use Yii;
use yii\db\Query;
use yii\helpers\VarDumper;

/**
 * This is the model class for table "product".
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property int $type_id
 * @property int $created_at
 * @property int $updated_at
 * @property int $status
 * @property string $content
 * @property string $article
 * @property string $short_name
 * @property int $production_year
 * @property int $available
 * @property int $old_available
 * @property int $available_in_file
 * @property int $available_in_stock
 * @property int $producer_id
 * @property int $base_price
 * @property int $actual_price
 * @property int $with_discount_price
 * @property int $end_price
 * @property int $flag_new
 * @property int $flag_sale
 * @property string $excel_code
 * @property double $weight
 * @property int $length
 * @property int $width
 * @property int $height
 * @property int $main_category_id
 * @property double $volume
 *
 * @property CharacteristicValue[] $characteristicValues
 * @property Type $type
 * @property Producer $producer
 * @property ProductCategory[] $productCategories
 */
class Product extends FrontActiveRecord
{
    const ARTICLE_SEPARATOR = '|';

    public $add_product_id;
    public $add_product_name;
    public $linkName;
    public $targetProductId;

    public $count = 0;

    const PRICE_SORT_FIELD = 'end_price'; // 'actual_price' 'with_discount_price', 'end_price'
    const ADMIN_VISIBLE_COLUMNS = 'adminVisibleProductColumns';
    const ADMIN_FILTER = 'adminFilter';

    public static $defaultVisibleColumns = [
        'article',

        'production_year',
        'producer_id',
        'type_id',
        'categories',
        'available',

        'flag_new',

        'images_count'
    ];
    public static $allVisibleColumns = [
        'article',
        'base_price',
        'actual_price',
        'own_cashback_value',
        'parent_cashback_value',

        'production_year',
        'producer_id',
        'type_id',
        'categories',
        'available',

        'return_own_cashback_on',
        'return_parent_cashback_on',
        'default_own_cashback_on',
        'default_parent_cashback_on',
        'can_pay_by_cashback',

        'on_recommended',
        'on_main',
        'on_yandex_market',
        'flag_new',

        'images_count'
    ];

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }

    const AVAILABLE_OUT_OF_STOCK = 0;
    const AVAILABLE_IN_STOCK = 1;
    const AVAILABLE_NEED_TO_CLARIFY = 2;
    const AVAILABLE_PRE_ORDER = 3;

    public static function getAvailableVariants() {
        return [
            static::AVAILABLE_OUT_OF_STOCK =>'нет в наличии',
            static::AVAILABLE_IN_STOCK =>'в наличиии',
            static::AVAILABLE_NEED_TO_CLARIFY => 'надо уточнить',
            static::AVAILABLE_PRE_ORDER => 'предзаказ',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'status', 'production_year', 'producer_id', 'base_price', 'actual_price', 'flag_new', 'flag_sale', 'length', 'width', 'height','rating'], 'integer'],
            [['with_discount_price', 'end_price'], 'integer'],
            [['base_price'], 'comparePrices'],

            [['weight', 'volume'], 'number'],
            [['on_recommended', 'on_main'], 'integer'],
            [['type_id'],'integer'],
            [['content'],'string'],
            [['name', 'slug', 'article', 'short_name', 'excel_code'], 'string', 'max' => 255],
            [['producer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Producer::className(), 'targetAttribute' => ['producer_id' => 'id']],

            [['return_own_cashback_on','return_parent_cashback_on','own_cashback_value','parent_cashback_value','default_own_cashback_on', 'default_parent_cashback_on','can_pay_by_cashback'], 'integer'],
            [['available','old_available','available_in_file','available_in_stock'], 'integer'],
            [['available_in_file'],'default','value' => 0],
            [['available'], 'default', 'value' => static::AVAILABLE_NEED_TO_CLARIFY],
            [['geometry_on'], 'integer'],
            [['images_count'], 'integer'],
            [['main_category_id'], 'integer']
        ];
    }

    public function comparePrices($attribute) {
        if($this->base_price < $this->actual_price) {
            $this->addError('base_price', 'текущая цена не должно быть больше чем базовая цена');
            $this->addError('actual_price', 'текущая цена не должно быть больше чем базовая цена');
        }
    }


    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('admin', 'ID'),
            'name' => Yii::t('admin', 'Name'),
            'slug' => Yii::t('admin', 'Slug'),
            'created_at' => Yii::t('admin', 'Created At'),
            'updated_at' => Yii::t('admin', 'Updated At'),
            'status' => Yii::t('admin', 'Status'),
            'article' => Yii::t('admin', 'Article'),
            'short_name' => Yii::t('admin', 'Short Name'),
            'production_year' => Yii::t('admin', 'Production Year'),
            'available' => Yii::t('admin', 'Available'),
            'producer_id' => Yii::t('admin', 'Producer ID'),
            'base_price' => Yii::t('admin', 'Base Price'),
            'actual_price' => Yii::t('admin', 'Actual Price'),
            'with_discount_price' => Yii::t('admin', 'Actual Price'),
            'end_price' => Yii::t('admin', 'Actual Price'),
            'flag_new' => Yii::t('admin', 'Flag New'),
            'flag_sale' => Yii::t('admin', 'Flag Sale'),
            'excel_code' => Yii::t('admin', 'Excel Code'),
            'weight' => Yii::t('admin', 'Weight'),
            'length' => Yii::t('admin', 'Length'),
            'width' => Yii::t('admin', 'Width'),
            'height' => Yii::t('admin', 'Height'),
            'volume' => Yii::t('admin', 'Volume'),
            'rating' => Yii::t('admin', 'Rating'),

            'on_recommended' => Yii::t('admin', 'On main Recommended'),
            'on_main' => Yii::t('admin', 'On main'),

            'return_own_cashback_on' => Yii::t('admin', 'Returns own the cashback'),
            'return_parent_cashback_on' => Yii::t('admin', 'Returns parent the cashback'),
            'own_cashback_value' => Yii::t('admin', 'Own cashback value'),
            'parent_cashback_value' => Yii::t('admin', 'Parent cashback value'),
            'default_own_cashback_on' => Yii::t('admin', 'Use default own cashback value'),
            'default_parent_cashback_on' => Yii::t('admin', 'Use default parent cashback value'),
            'can_pay_by_cashback' => Yii::t('admin', 'This product can be paid for cashback'),

            'video' => Yii::t('admin', 'Video'),

            'geometry_on' => Yii::t('admin', 'Geometry Calc On'),
            'main_category_id' => 'Основная категория'
        ];
    }

    public static function getAdminVisibleColumns() {
//        Yii::$app->session->set(static::ADMIN_VISIBLE_COLUMNS, serialize(static::$allVisibleColumns));
        return unserialize(Yii::$app->session->get(static::ADMIN_VISIBLE_COLUMNS, serialize(static::$defaultVisibleColumns)));
    }
    public static function setAdminVisibleColumn($columns) {
        Yii::$app->session->set(static::ADMIN_VISIBLE_COLUMNS, serialize($columns));
    }

    public static function getAdminFilter() {
        return unserialize(Yii::$app->session->get(static::ADMIN_FILTER, serialize([])));
    }
    public static function setAdminFilter($data) {
        Yii::$app->session->set(static::ADMIN_FILTER, serialize($data));
    }


    public function afterSave($insert, $changedAttributes)
    {
        if($insert) {
            $seoModel = $this->seo;
            if ($seoModel === null) {
                $seoModel = new \app\models\Seo([
                    'model_id' => $this->id,
                    'model_table' => $this::tableName()
                ]);
                $seoModel->save(false);
            }
        }

        parent::afterSave($insert, $changedAttributes);
    }

    public function beforeSave($insert)
    {
        if($this->actual_price == '') {
            $this->actual_price = $this->base_price;
            $this->flag_sale = 0;
        } elseif($this->actual_price == $this->base_price) {
            $this->flag_sale = 1;
        }

        if($this->actual_price == '' || $this->actual_price == null || $this->actual_price == 0) {
            $this->actual_price = $this->base_price;
        }
        $this->updateDiscounts();
        return parent::beforeSave($insert);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCharacteristicValues()
    {
        return $this->hasMany(CharacteristicValue::className(), ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProducer()
    {
        return $this->hasOne(Producer::className(), ['id' => 'producer_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMainCategory()
    {
        return $this->hasOne(Category::className(), ['id' => 'main_category_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getType()
    {
        return $this->hasOne(Type::className(), ['id' => 'type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductCategories()
    {
        return $this->hasMany(ProductCategory::className(), ['product_id' => 'id'])->orderBy(['sort' => SORT_ASC]);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Category::class, ['id' => 'category_id'])
            ->viaTable('product_category', ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getParent()
    {
        return $this->hasMany(Category::class, ['id' => 'parent_id']);
    }

    /**
     * @return string
     */
    public function getUrl() {
        $urls = [];
        $categories = $this->getCategories()
            ->with('parent')
//            ->andWhere(['not',['parent_id' => null]])
            ->orderBy(['parent_id' => SORT_DESC])
            ->all();
        if(!empty($categories)) {
            $urls[] = $categories[0]->slug;
            // проверка на главную категорию / если главная категория то она будет в урл как предшествующая slug продукта
            if($categories[0]->id !== $this->main_category_id) {
                if ($categories[0]->parent_id != null) {
                    $urls[] = $categories[0]->parent->slug;
                }
            }
        }
        $urls = array_reverse($urls);

        return '/catalog/' .$this->type->slug .'/'. implode('/',$urls) .'/'. $this->slug;;
    }

    /**
     * *******************************
     * GIFTS, RECOMMENDED AND OTHER LINKS WITH PRODUCTS
     * *******************************
     *
     */

    /**
     * Add link with Product
     * @param $product_id
     * @param $linkName
     */
    public function addLinkWithProduct($linkName, $product_id) {
        if(in_array($linkName, [
            'gift',
            'recommended',
            'buy_with',
            'other_year'
        ])) {
            Yii::$app->db->createCommand()->insert($linkName, [
                'product_id' => $this->id,
                'target_product_id' => $product_id
            ])->execute();
            if($linkName === 'buy_with' || $linkName === 'other_year') {
                Yii::$app->db->createCommand()->insert($linkName, [
                    'product_id' => $product_id,
                    'target_product_id' => $this->id
                ])->execute();
            }
        }

    }
    public function addLinksWithProduct($linkName, $ids = []) {
//        $this->removeLinksFromProduct($linkName);
        foreach ($ids as $id) {
            $this->addLinkWithProduct($linkName, $id);
        }
    }

    public function removeLinksFromProduct($linkName) {
        $relations = [
            'gift' => 'gifts',
            'recommended' => 'recommended',
            'buy_with' => 'buyWith',
            'other_year' => 'otherYears'
        ];
        if(in_array($linkName, [
            'gift',
            'recommended',
            'buy_with',
            'other_year'
        ])) {
            $relationName = $relations[$linkName];
//            VarDumper::dump($relations[$linkName] ,10,1);die;
            foreach ($this->$relationName as $relationModel) {
                $this->removeLinkFromProduct($linkName, $relationModel);
            }
        }
    }
    public function removeLinkFromProduct($linkName, $relationModel) {
        Yii::$app->db->createCommand()
            ->delete($linkName, [
                'product_id' => $this->id,
                'target_product_id' => $relationModel->id
            ])->execute();
        if($linkName === 'buy_with' || $linkName === 'other_year') {
            Yii::$app->db->createCommand()
                ->delete($linkName, [
                    'target_product_id' => $this->id,
                    'product_id' => $relationModel->id
                ])->execute();
        }
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGifts()
    {
        return $this->hasMany(self::class, ['id' => 'target_product_id'])->viaTable('gift', ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     * Связь - рекомендованые
     */
    public function getRecommended()
    {
        return $this->hasMany(self::class, ['id' => 'target_product_id'])->viaTable('recommended', ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     * Связь - с этим товаром часто покупают
     */
    public function getBuyWith()
    {
        return $this->hasMany(self::class, ['id' => 'target_product_id'])
            ->viaTable('buy_with', ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     * Связь - эти же модели других годов
     */
    public function getOtherYears()
    {
        return $this->hasMany(self::class, ['id' => 'target_product_id'])
            ->viaTable('other_year', ['product_id' => 'id']);
    }

    /**
     * *******************************
     * CHARACTERISTICS
     * *******************************
     *
     */

    public function checkCharacteristics()
    {

        $currentIds = [];
        $actualIds = [];
        foreach($this->type->characteristics as $characteristic) {
            $actualIds[] = $characteristic->id;
        }
        foreach($this->characteristicValues as $characteristicValue) {
            $currentIds[] = $characteristicValue->characteristic_id;
        }

        $addIds = array_diff($actualIds, $currentIds);
        $deleteIds = array_diff($currentIds, $actualIds);

        foreach($addIds as $addId) {
            $this->addCharacteristicValue($addId);
        }

        $this->deleteCharacteristicValues($deleteIds);

    }

    public function updateCharacteristicValues($characteristics)
    {
        foreach ($characteristics as $key => $value) {
            $this->changeCharacteristicValue($key, $value);
        }

    }

    public function addCharacteristicValue($characteristic_id, $value = null)
    {
        $characteristicValue = new CharacteristicValue([
            'characteristic_id' => $characteristic_id,
            'product_id' => $this->id,
        ]);
        if($value !== null) {
            $characteristicValue->value = $value;
        }
        $characteristicValue->save();
        return $characteristicValue;
    }

    public function changeCharacteristicValue($characteristic_id, $value)
    {
        $characteristicValue = CharacteristicValue::findOne([
            'product_id' => $this->id,
            'characteristic_id' => $characteristic_id,
        ]);
        if($characteristicValue === null) {
            $characteristicValue = new CharacteristicValue([
                'product_id' => $this->id,
                'characteristic_id' => $characteristic_id,
            ]);
        }
        $characteristicValue->value = $value;
        $characteristicValue->save();
    }

    public function deleteCharacteristicValues($characteristicsIds)
    {
        CharacteristicValue::deleteAll([
            'and',
            ['product_id' => $this->id],
            ['in', 'characteristic_id', $characteristicsIds]
        ]);
    }

    /**
     * *******************************
     * FAVORITE AND COMPARE
     * *******************************
     *
     */
    public function getIsInCompare() {

        $compareIds = [];
        if (false !== ($compare = Yii::$app->session->get('compare', false))) {
            $compareIds = unserialize($compare);
        }
        return in_array($this->id, $compareIds);

        if(Yii::$app->user->isGuest) {

        } else {

        }
    }
    public function getIsInFavorite() {
        $favoriteIds = [];
        if (false !== ($favorite = Yii::$app->session->get('favorite', false))) {
            $favoriteIds = unserialize($favorite);
        }
        return in_array($this->id, $favoriteIds);
    }

    /**
     * тут собираем все скидки которые есть
     * и максимальную записываем в with_discount_price
     */
    public function updateDiscounts() {
        $maxDiscount = $this->actual_price;

        // Это работать не будет, потому промокод зависит от пользователя
        // это нужно приберечь для акционных скидок
//        $tableName = DiscountPromo::tableName();
//        $discountPromos = (new Query())
//            ->from($tableName)
//            ->join('LEFT JOIN', $tableName.'_product', $tableName.'.id = '.$tableName.'_product.'.$tableName.'_id')
//            ->where([$tableName.'.status' => 1])
//            ->andWhere([$tableName .'_product.product_id' => $this->id])
//            ->all();
//
//        foreach($discountPromos as $discountPromo) {
//            $newDiscountValue = DiscountPromo::staticProcess($this->base_price, $discountPromo['value'],$discountPromo['type']);
//            if($maxDiscount > $newDiscountValue) {
//                $maxDiscount = $newDiscountValue;
//            }
//        }

        $this->with_discount_price = $maxDiscount;
        $this->end_price = $this->with_discount_price;
//        $this->flag_sale = $this->base_price != $this->{static::PRICE_SORT_FIELD};
        $this->flag_sale = intval($this->base_price) > intval($this->actual_price);

        //$this->save(false);
    }

    public function discountProcent() {
        return static::calculateDiscountProcent($this->base_price, $this->{static::PRICE_SORT_FIELD});
    }
    /**
     * @param $price
     * @param $sale
     * @return float|int
     */
    public static function calculateDiscountProcent($price, $sale) {
        return $price === 0 ? : (($price - $sale) * 100) / $price;
    }

    public function getDiscounts() {

        $userDiscountsPromo = Yii::$app->user->identity->discountsPromo;
        $discounts = [];

        foreach ($userDiscountsPromo as $userDiscountPromo) {
            $ids = [];
            foreach ($userDiscountPromo->products as $product) {
                $ids[] = $product->id;
            }
            if(in_array($this->id, $ids)) {
                $discounts[] = $userDiscountPromo;
            }
        }


        return $discounts;
    }

}
