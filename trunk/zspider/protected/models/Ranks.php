<?php

/**
 * This is the model class for table "ranks".
 *
 * The followings are the available columns in table 'ranks':
 * @property integer $id
 * @property string $url
 * @property string $title
 * @property integer $rank_best_sell
 * @property string $info_best_sell
 * @property string $info_ranks
 * @property string $isbn_10
 * @property string $isbn_13
 */
class Ranks extends CActiveRecord
{
	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'ranks';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('rank_best_sell', 'numerical', 'integerOnly'=>true),
			array('url, title, info_best_sell, isbn_10, isbn_13', 'length', 'max'=>255),
			array('info_ranks', 'length', 'max'=>4096),
			// The following rule is used by search().
			// @todo Please remove those attributes that should not be searched.
			array('id, url, title, rank_best_sell, info_best_sell, info_ranks, isbn_10, isbn_13', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'url' => 'Url',
			'title' => 'Title',
			'rank_best_sell' => 'Rank Best Sell',
			'info_best_sell' => 'Info Best Sell',
			'info_ranks' => 'Info Ranks',
			'isbn_10' => 'Isbn 10',
			'isbn_13' => 'Isbn 13',
			'utime' => '更新时间',
			'main_image' => '主图',
			'thumb_image' => '缩略图',
		);
	}

//    public function behaviors()
//    {
//        return array(
//                     'CTimestampBehavior' => array(
//                                                   'class' => 'zii.behaviors.CTimestampBehavior',
//                                                   'createAttribute' => 'created_date',
//                                                   'updateAttribute' => 'updated_date',
//                                                   'setUpdateOnCreate' => true,
//                                                   ),
////                     'BlameableBehavior' => array(
////                                                  'class' => 'application.components.behaviors.BlameableBehavior',
////                                                  'createdByColumn' => 'created_by', // optional
////                                                  'updatedByColumn' => 'modified_by', // optional
////                                                  ),
//                     );
//    }


	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 *
	 * Typical usecase:
	 * - Initialize the model fields with values from filter form.
	 * - Execute this method to get CActiveDataProvider instance which will filter
	 * models according to data in model fields.
	 * - Pass data provider to CGridView, CListView or any similar widget.
	 *
	 * @return CActiveDataProvider the data provider that can return the models
	 * based on the search/filter conditions.
	 */
	public function search()
	{
		// @todo Please modify the following code to remove attributes that should not be searched.

		$criteria=new CDbCriteria;

        $criteria->join = "join ranks_group rg on t.id = rg.rank_id";
		$criteria->compare('id',$this->id);
		$criteria->compare('url',$this->url,true);
		$criteria->compare('title',$this->title,true);
		$criteria->compare('rank_best_sell',$this->rank_best_sell);
		$criteria->compare('info_best_sell',$this->info_best_sell,true);
		$criteria->compare('info_ranks',$this->info_ranks,true);
		$criteria->compare('isbn_10',$this->isbn_10,true);
		$criteria->compare('isbn_13',$this->isbn_13,true);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
            'pagination'=>array(
                                'pageSize'=>200,
                                ),
		));
	}

	/**
	 * Returns the static model of the specified AR class.
	 * Please note that you should have this exact method in all your CActiveRecord descendants!
	 * @param string $className active record class name.
	 * @return Ranks the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}
}
