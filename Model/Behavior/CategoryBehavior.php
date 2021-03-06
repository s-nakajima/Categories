<?php
/**
 * Category Behavior
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('ModelBehavior', 'Model');

/**
 * Category Behavior
 *
 * 該当ブロックのカテゴリーを登録します。
 *
 * #### サンプルコード
 * ```
 * public $actsAs = array(
 * 	'Categories.Category'
 * )
 * ```
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Categories\Model\Behavior
 */
class CategoryBehavior extends ModelBehavior {

/**
 * afterValidate is called just after model data was validated, you can use this callback
 * to perform any data cleanup or preparation if needed
 *
 * @param Model $model Model using this behavior
 * @return mixed False will stop this event from being passed to other behaviors
 */
	public function afterValidate(Model $model) {
		if (! isset($model->data['Categories'])) {
			return true;
		}
		$model->loadModels(array(
			'Category' => 'Categories.Category',
			'CategoryOrder' => 'Categories.CategoryOrder',
			'CategoriesLanguage' => 'Categories.CategoriesLanguage',
		));

		foreach ($model->data['Categories'] as $category) {
			$model->Category->set($category['Category']);
			if (! $model->Category->validates()) {
				$model->validationErrors = Hash::merge(
					$model->validationErrors, $model->Category->validationErrors
				);
				return false;
			}

			$model->CategoriesLanguage->set($category['CategoriesLanguage']);
			if (! $model->CategoriesLanguage->validates()) {
				$model->validationErrors['category_name'] =
						$model->CategoriesLanguage->validationErrors['name'];
				return false;
			}

			$model->CategoryOrder->set($category['CategoryOrder']);
			if (! $model->CategoryOrder->validates()) {
				$model->validationErrors = Hash::merge(
					$model->validationErrors, $model->CategoryOrder->validationErrors
				);
				return false;
			}
		}

		return true;
	}

/**
 * beforeSave is called before a model is saved. Returning false from a beforeSave callback
 * will abort the save operation.
 *
 * @param Model $model Model using this behavior
 * @param array $options Options passed from Model::save().
 * @return mixed False if the operation should abort. Any other result will continue.
 * @throws InternalErrorException
 * @see Model::save()
 */
	public function beforeSave(Model $model, $options = array()) {
		parent::beforeSave($model, $options);

		if (! isset($model->data['Categories'])) {
			return true;
		}

		$model->loadModels(array(
			'Category' => 'Categories.Category',
			'CategoryOrder' => 'Categories.CategoryOrder',
			'CategoriesLanguage' => 'Categories.CategoriesLanguage',
		));

		//不要なカテゴリを削除する
		$this->_deleteCategories($model);

		//登録処理
		foreach ($model->data['Categories'] as $category) {
			$result = $model->Category->save($category['Category'], false);
			if (! $result) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			$category['CategoryOrder']['category_key'] = $result['Category']['key'];
			$category['CategoriesLanguage']['category_id'] = $result['Category']['id'];

			if (! $model->CategoryOrder->save($category['CategoryOrder'], false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}

			if (! $model->CategoriesLanguage->save($category['CategoriesLanguage'], false)) {
				throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
			}
		}
		return true;
	}

/**
 * 不要なカテゴリを削除する
 *
 * @param Model $model Model using this behavior
 * @return bool
 * @throws InternalErrorException
 */
	protected function _deleteCategories(Model $model) {
		$categoryKeys = Hash::combine($model->data['Categories'], '{n}.Category.key', '{n}.Category.key');

		$conditions = array(
			'block_id' => $model->data['Block']['id']
		);
		if ($categoryKeys) {
			$conditions[$model->Category->alias . '.key NOT'] = $categoryKeys;
		}
		$categoryIds = $model->Category->find('list', array(
			'recursive' => -1,
			'fields' => array('id', 'id'),
			'conditions' => $conditions
		));

		//Category削除処理
		if (! $model->Category->deleteAll($conditions, false)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		//CategoriesLanguage削除処理
		$conditions = array(
			'category_id' => array_values($categoryIds)
		);
		if (! $model->CategoriesLanguage->deleteAll($conditions, false)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		//CategoriesLanguage削除処理
		$conditions = array(
			'block_key' => $model->data['Block']['key']
		);
		if ($categoryKeys) {
			$conditions[$model->CategoryOrder->alias . '.category_key NOT'] = $categoryKeys;
		}
		if (! $model->CategoryOrder->deleteAll($conditions, false)) {
			throw new InternalErrorException(__d('net_commons', 'Internal Server Error'));
		}

		return true;
	}
}
