<?php
/**
 * Category Model
 *
 * @property Block $Block
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('CategoriesAppModel', 'Categories.Model');

/**
 * Category Model
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Categories\Model
 */
class Category extends CategoriesAppModel {

/**
 * use behaviors
 *
 * @var array
 */
	public $actsAs = array(
		'NetCommons.OriginalKey',
		'M17n.M17n', //多言語
	);

/**
 * Validation rules
 *
 * @var array
 */
	public $validate = array();

/**
 * belongsTo associations
 *
 * @var array
 */
	public $belongsTo = array(
		'Block' => array(
			'className' => 'Blocks.Block',
			'foreignKey' => 'block_id',
			'type' => 'INNER',
			'conditions' => '',
			'fields' => '',
			'order' => ''
		),
	);

/**
 * Called during validation operations, before validation. Please note that custom
 * validation rules can be defined in $validate.
 *
 * @param array $options Options passed from Model::save().
 * @return bool True if validate operation should continue, false to abort
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforevalidate
 * @see Model::save()
 */
	public function beforeValidate($options = array()) {
		$this->validate = array(
			//'id' => array(
			//	'numeric' => array(
			//		'rule' => array('numeric'),
			//		'message' => __d('net_commons', 'Invalid request.'),
			//		'allowEmpty' => true,
			//	),
			//),
			'block_id' => array(
				'notBlank' => array(
					'rule' => array('notBlank'),
					'message' => __d('net_commons', 'Invalid request.'),
					'allowEmpty' => false,
					'required' => true,
					'on' => 'update', // Limit validation to 'create' or 'update' operations
				),
			),
			'key' => array(
				'notBlank' => array(
					'rule' => array('notBlank'),
					'message' => __d('net_commons', 'Invalid request.'),
					'allowEmpty' => false,
					'required' => true,
					'on' => 'update', // Limit validation to 'create' or 'update' operations
				),
			),
		);

		return parent::beforeValidate($options);
	}

/**
 * Called before each find operation. Return false if you want to halt the find
 * call, otherwise return the (modified) query data.
 *
 * @param array $query Data used to execute this query, i.e. conditions, order, etc.
 * @return mixed true if the operation should continue, false if it should abort; or, modified
 *  $query to continue with new $query
 * @link http://book.cakephp.org/2.0/en/models/callback-methods.html#beforefind
 */
	public function beforeFind($query) {
		if (Hash::get($query, 'recursive') > -1) {
			$belongsTo = $this->bindModelCategoryLang();
			$this->bindModel($belongsTo, true);
		}
		return true;
	}

/**
 * Get categories
 *
 * @param int $blockId blocks.id
 * @param int $roomId rooms.id
 * @return array Categories
 */
	public function getCategories($blockId, $roomId) {
		$conditions = array(
			'Category.block_id' => $blockId,
			//'Block.room_id' => $roomId,
		);

		$this->unbindModel(['belongsTo' => ['Block', 'TrackableCreator', 'TrackableUpdater']], true);
		$this->bindModel(array(
			'belongsTo' => array(
				'CategoryOrder' => array(
					'className' => 'Categories.CategoryOrder',
					'type' => 'INNER',
					'foreignKey' => false,
					'conditions' => 'CategoryOrder.category_key=Category.key',
					'fields' => '',
					'order' => array('CategoryOrder.weight' => 'ASC')
				),
			)
		), false);
		$this->CategoryOrder->useDbConfig = $this->useDbConfig;

		$belongsTo = $this->bindModelCategoryLang();
		$this->bindModel($belongsTo, true);

		$categories = $this->find('all', array(
			'recursive' => 0,
			'fields' => [
				'Category.id',
				'Category.block_id',
				'Category.key',
				'CategoryOrder.id',
				'CategoryOrder.category_key',
				'CategoryOrder.block_key',
				'CategoryOrder.weight',
				'CategoriesLanguage.id',
				'CategoriesLanguage.language_id',
				'CategoriesLanguage.category_id',
				'CategoriesLanguage.name',
			],
			'conditions' => $conditions,
		));

		return $categories;
	}

/**
 * カテゴリーの取得
 *
 * @param int $categoryId カテゴリーID
 * @return array Categories
 */
	public function getCategory($categoryId) {
		$conditions = array(
			'Category.id' => $categoryId,
		);

		$this->bindModel(array(
			'belongsTo' => array(
				'CategoryOrder' => array(
					'className' => 'Categories.CategoryOrder',
					'foreignKey' => false,
					'conditions' => 'CategoryOrder.category_key=Category.key',
					'fields' => '',
					//'order' => array('CategoryOrder.weight' => 'ASC')
				),
			)
		), false);
		$this->CategoryOrder->useDbConfig = $this->useDbConfig;

		$belongsTo = $this->bindModelCategoryLang();
		$this->bindModel($belongsTo, true);

		$categories = $this->find('first', array(
			'recursive' => 0,
			'conditions' => $conditions,
		));

		return $categories;
	}

/**
 * カテゴリ言語テーブルのバインド条件を戻す
 *
 * @param string $joinKey JOINするKeyフィールド(default: Category.id)
 * @return array
 */
	public function bindModelCategoryLang($joinKey = 'Category.id') {
		$this->loadModels([
			'Language' => 'M17n.Language',
		]);

		if ($this->Language->isMultipleLang()) {
			$conditions = [
				'CategoriesLanguage.category_id = ' . $joinKey,
				'OR' => array(
					'CategoriesLanguage.is_translation' => false,
					'CategoriesLanguage.language_id' => Current::read('Language.id', '0'),
				),
			];
		} else {
			$conditions = [
				'CategoriesLanguage.category_id = ' . $joinKey,
				'CategoriesLanguage.language_id' => Current::read('Language.id', '0'),
			];
		}

		$belongsTo = array(
			'belongsTo' => array(
				'CategoriesLanguage' => array(
					'className' => 'Categories.CategoriesLanguage',
					'foreignKey' => false,
					'conditions' => $conditions,
					'fields' => array('id', 'language_id', 'category_id', 'name', 'is_origin', 'is_translation'),
					'order' => ''
				),
			)
		);

		return $belongsTo;
	}

}
