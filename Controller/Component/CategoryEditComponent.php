<?php
/**
 * Categories Component
 *   Before use of this component, please define NetCommonsFrame component,
 *   NetCommonsRoomRole component and Category model in caller.
 *
 * @author Noriko Arai <arai@nii.ac.jp>
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @link http://www.netcommons.org NetCommons Project
 * @license http://www.netcommons.org/license.txt NetCommons License
 * @copyright Copyright 2014, NetCommons Project
 */

App::uses('Component', 'Controller');

/**
 * Categories Component
 *
 * @author Shohei Nakajima <nakajimashouhei@gmail.com>
 * @package NetCommons\Categories\Controller\Component
 */
class CategoryEditComponent extends Component {

/**
 * Called after the Controller::beforeFilter() and before the controller action
 *
 * @param Controller $controller Controller with components to startup
 * @return void
 * @throws ForbiddenException
 */
	public function startup(Controller $controller) {
		$controller->Category = ClassRegistry::init('Categories.Category');
		$controller->CategoryOrder = ClassRegistry::init('Categories.CategoryOrder');

		if ($controller->request->is(array('post', 'put'))) {
			$categories = array();
			if (! isset($controller->request->data['Categories'])) {
				$controller->request->data['Categories'] = array();
			}

			foreach ($controller->request->data['Categories'] as $post) {
				if (! isset($post['Category']['name'])) {
					continue;
				}
				$category = null;
				if (! $post['Category']['id']) {
					$category = $controller->Category->create(array(
						'id' => null,
						'key' => null,
						'name' => $post['Category']['name'],
 					));
					$category = Hash::merge($category, $controller->CategoryOrder->create(array(
						'id' => null,
						'category_key' => null,
						'weight' => $post['CategoryOrder']['weight'],
 					)));
				}
				if (isset($controller->request->data['CategoryMap'][$post['Category']['id']])) {
					$category = Hash::merge($post, $controller->request->data['CategoryMap'][$post['Category']['id']]);
				}
				$category['Category']['block_id'] = $controller->request->data['Block']['id'];
				$category['CategoryOrder']['block_key'] = $controller->request->data['Block']['key'];

				$categories[] = $category;
			}
			$controller->request->data['Categories'] = $categories;

		} else {
			$controller->request->data['Categories'] = $controller->Category->getCategories($controller->viewVars['blockId'], $controller->viewVars['roomId']);
			$controller->request->data['CategoryMap'] = Hash::combine($controller->request->data['Categories'], '{n}.Category.id', '{n}');
		}

		$this->controler = $controller;
	}
}
