<?php
/**
 * 多言語化対応
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('NetCommonsMigration', 'NetCommons.Config/Migration');

/**
 * 多言語化対応
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Categories\Config\Migration
 */
class AddTableForM17n1 extends NetCommonsMigration {

/**
 * Migration description
 *
 * @var string
 */
	public $description = 'add_table_for_m17n_1';

/**
 * Actions to be performed
 *
 * @var array $migration
 */
	public $migration = array(
		'up' => array(
		),
		'down' => array(
		),
	);

/**
 * Before migration callback
 *
 * @param string $direction Direction of migration process (up or down)
 * @return bool Should process continue
 */
	public function before($direction) {
		return true;
	}

/**
 * After migration callback
 *
 * @param string $direction Direction of migration process (up or down)
 * @return bool Should process continue
 */
	public function after($direction) {
		$CategoriesLanguage = $this->generateModel('CategoriesLanguage');

		$schema = $CategoriesLanguage->schema();
		unset($schema['id']);
		$schemaColumns = implode(', ', array_keys($schema));

		$cateTable = $CategoriesLanguage->tablePrefix . 'categories Category';
		$cateLangTable = $CategoriesLanguage->tablePrefix . 'categories_languages CategoriesLanguage';

		if ($direction === 'up') {
			$sql = 'INSERT INTO ' .
						$CategoriesLanguage->tablePrefix . 'categories_languages(' . $schemaColumns . ')' .
					' SELECT' .
						' Category.id' .
						', Category.language_id' .
						', Category.name' .
						', 1' .
						', 0' .
						', Category.created_user' .
						', Category.created' .
						', Category.modified_user' .
						', Category.modified' .
					' FROM ' . $cateTable;
		} else {
			$sql = 'UPDATE ' . $cateTable . ', ' . $cateLangTable .
					' SET Category.language_id = CategoriesLanguage.language_id, ' .
						'Category.name = CategoriesLanguage.name' .
					' WHERE Category.id = CategoriesLanguage.category_id' .
					'';
		}
		$CategoriesLanguage->query($sql);
		return true;
	}
}
