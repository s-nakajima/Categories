<?php
/**
 * Element of Categories edit form
 *   - $categories:
 *       The results data of Category->getCategories(), and The formatter is camelized data.
 *   - $cancelUrl: Cancel url.
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */
?>

<div ng-hide="categories.length">
	<p><?php echo __d('categories', 'No category.'); ?></p>
</div>

<div class="pre-scrollable" ng-show="categories.length">
	<article class="form-group" ng-repeat="c in categories track by $index">
		<div class="input-group input-group-sm">
			<div class="input-group-btn">
				<button type="button" class="btn btn-default"
						ng-click="move('up', $index)" ng-disabled="$first">
					<span class="glyphicon glyphicon-arrow-up"></span>
				</button>

				<button type="button" class="btn btn-default"
						ng-click="move('down', $index)" ng-disabled="$last">
					<span class="glyphicon glyphicon-arrow-down"></span>
				</button>
			</div>

			<input type="hidden" name="data[Categories][{{$index}}][Category][id]" ng-value="c.category.id">
			<input type="hidden" name="data[Categories][{{$index}}][CategoryOrder][weight]" ng-value="{{$index + 1}}">
			<input type="text" name="data[Categories][{{$index}}][CategoriesLanguage][name]"
					ng-model="c.categoriesLanguage.name" class="form-control" required autofocus>

			<div class="input-group-btn">
				<button type="button" class="btn btn-default" tooltip="<?php echo __d('net_commons', 'Delete'); ?>"
						ng-click="delete($index)">
					<span class="glyphicon glyphicon-remove"> </span>
				</button>
			</div>
		</div>
	</article>
</div>
