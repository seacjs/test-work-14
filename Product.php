<?php

namespace app\models\shop;

use app\models\FrontActiveRecord;
use Yii;
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
 * @property int $producer_id
 * @property int $base_price
 * @property int $actual_price
 * @property int $flag_new
 * @property int $flag_sale
 * @property string $excel_code
 * @property double $weight
 * @property int $length
 * @property int $width
 * @property int $height
 * @property double $volume
 *
 * @property CharacteristicValue[] $characteristicValues
 * @property Type $type
 * @property Producer $producer
 * @property ProductCategory[] $productCategories
 */
class Product extends FrontActiveRecord
{
    public $add_product_id;
    public $add_product_name;

    public $count = 0;

    const PRICE_SORT_FIELD = 'actual_price';

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'product';
    }

    public function getAvailableVariants() {
        return [
            'нет в наличии',
            'в наличиии',
            'надо уточнить',
            'предзаказ',
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['created_at', 'updated_at', 'status', 'production_year', 'available', 'producer_id', 'base_price', 'actual_price', 'flag_new', 'flag_sale', 'length', 'width', 'height','rating'], 'integer'],
            [['with_discount_price', 'end_price'], 'integer'],
            [['weight', 'volume'], 'number'],
            [['on_recommended', 'on_main'], 'integer'],
            [['type_id'],'integer'],
            [['content'],'string'],
            [['name', 'slug', 'article', 'short_name', 'excel_code'], 'string', 'max' => 255],
            [['producer_id'], 'exist', 'skipOnError' => true, 'targetClass' => Producer::className(), 'targetAttribute' => ['producer_id' => 'id']],
            [['return_own_cashback_on','return_parent_cashback_on','own_cashback_value','parent_cashback_value','default_own_cashback_on', 'default_parent_cashback_on','can_pay_by_cashback'], 'integer']
        ];
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
            'return_parent_cashback_on' => Yii::t('admin', 'Returns children the cashback'),
            'own_cashback_value' => Yii::t('admin', 'Own cashback value'),
            'parent_cashback_value' => Yii::t('admin', 'Children cashback value'),
            'default_own_cashback_on' => Yii::t('admin', 'Use default cashback value'),
            'default_parent_cashback_on' => Yii::t('admin', 'Use default cashback value'),
            'can_pay_by_cashback' => Yii::t('admin', 'This product can be paid for cashback'),

        ];
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
    public function getType()
    {
        return $this->hasOne(Type::className(), ['id' => 'type_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getProductCategories()
    {
        return $this->hasMany(ProductCategory::className(), ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getCategories()
    {
        return $this->hasMany(Category::class, ['id' => 'category_id'])->viaTable('product_category', ['product_id' => 'id']);
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
        $url = '/catalog/' .$this->type->slug;
        $categories = $this->getCategories()->with('parent')->andWhere(['not',['parent_id' => null]])->all();
        if(!empty($categories)) {
            $url .= '/' .$categories[0]->slug;
            if($categories[0]->parent_id != null) {
                $url .= '/'.$categories[0]->parent->slug;
            }
        }
        $url .= '/'. $this->slug;
        return $url;
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
            if($linkName === 'buy_with') {
                Yii::$app->db->createCommand()->insert($linkName, [
                    'product_id' => $product_id,
                    'target_product_id' => $this->id
                ])->execute();
            }
        }

    }
    public function addLinksWithProduct($linkName, $ids = []) {
        $this->removeLinksFromProduct($linkName);
        foreach ($ids as $id) {
            $this->addLinkWithProduct($linkName, $id);
        }
    }

    public function removeLinksFromProduct($linkName) {

    }
    public function removeLinkFromProduct($linkName) {

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
        return $this->hasMany(self::class, ['id' => 'target_product_id'])->viaTable('buy_with', ['product_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     * Связь - эти же модели других годов
     */
    public function getOtherYears()
    {
        return $this->hasMany(self::class, ['id' => 'target_product_id'])
            ->viaTable('other_year', ['product_id' => 'id'])
            ->orderBy(['production_year']);
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
        return false;
        if(Yii::$app->user->isGuest) {

        } else {

        }
    }
    public function getIsInFavorite() {
        return false;
    }

}
