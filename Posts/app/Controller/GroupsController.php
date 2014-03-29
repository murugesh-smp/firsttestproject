<?php
App::uses('AppController', 'Controller');
/**
 * Groups Controller
 *
 * @property Group $Group
 */

class GroupsController extends AppController {
	public $components = array('Acl');
	public function beforeFilter() {
    parent::beforeFilter();

    // For CakePHP 2.0
  //  $this->Auth->allow('*');

    // For CakePHP 2.1 and up
    $this->Auth->allow();
}

/**
 * index method
 *
 * @return void
 */
	public function index() {
		$this->Group->recursive = 0;
		$this->set('groups', $this->paginate());
	}

/**
 * view method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function view($id = null) {
		if (!$this->Group->exists($id)) {
			throw new NotFoundException(__('Invalid group'));
		}
		$options = array('conditions' => array('Group.' . $this->Group->primaryKey => $id));
		$this->set('group', $this->Group->find('first', $options));
	}

/**
 * add method
 *
 * @return void
 */
	public function add() {
		if ($this->request->is('post')) {
			$this->Group->create();
			if ($this->Group->save($this->request->data)) {
				$this->Session->setFlash(__('The group has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The group could not be saved. Please, try again.'));
			}
		}
	}

/**
 * edit method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function edit($id = null) {
		if (!$this->Group->exists($id)) {
			throw new NotFoundException(__('Invalid group'));
		}
		if ($this->request->is('post') || $this->request->is('put')) {
			if ($this->Group->save($this->request->data)) {
				$this->Session->setFlash(__('The group has been saved'));
				$this->redirect(array('action' => 'index'));
			} else {
				$this->Session->setFlash(__('The group could not be saved. Please, try again.'));
			}
		} else {
			$options = array('conditions' => array('Group.' . $this->Group->primaryKey => $id));
			$this->request->data = $this->Group->find('first', $options);
		}
	}

/**
 * delete method
 *
 * @throws NotFoundException
 * @param string $id
 * @return void
 */
	public function delete($id = null) {
		$this->Group->id = $id;
		if (!$this->Group->exists()) {
			throw new NotFoundException(__('Invalid group'));
		}
		$this->request->onlyAllow('post', 'delete');
		if ($this->Group->delete()) {
			$this->Session->setFlash(__('Group deleted'));
			$this->redirect(array('action' => 'index'));
		}
		$this->Session->setFlash(__('Group was not deleted'));
		$this->redirect(array('action' => 'index'));
	}




	function build_acl() {
    if (!Configure::read('debug')) {
        return $this->_stop();
    }
    $log = array();

    $aco = & $this->Acl->Aco;
    $root = $aco->node('controllers');
    if (!$root) {
        $aco->create(array('parent_id' => null, 'model' => null, 'alias' => 'controllers'));
        $root = $aco->save();
        $root['Aco']['id'] = $aco->id;
        $log[] = 'Created Aco node for controllers';
    } else {
        $root = $root[0];
    }

    App::uses('File', 'Utility');
    $ControllersFresh = App::objects('Controller');

    foreach ($ControllersFresh as $cnt) {
        $Controllers[] = str_replace('Controller', '', $cnt);
    }
    $appIndex = array_search('App', $Controllers);
    if ($appIndex !== false) {
        unset($Controllers[$appIndex]);
    }
    $baseMethods = get_class_methods('Controller');
    $baseMethods[] = 'build_acl';

    $appcontr = get_class_methods('AppController');

    foreach ($appcontr as $appc) {
        $baseMethods[] = $appc;
    }

    $baseMethods = array_unique($baseMethods);

    $Plugins = $this->_getPluginControllerNames();
    $Controllers = array_merge($Controllers, $Plugins);

    // look at each controller in app/controllers
    foreach ($Controllers as $ctrlName) {
        $methods = $this->_getClassMethods($this->_getPluginControllerPath($ctrlName));

        // Do all Plugins First
        if ($this->_isPlugin($ctrlName)) {
            $pluginNode = $aco->node('controllers/' . $this->_getPluginName($ctrlName));
            if (!$pluginNode) {
                $aco->create(array('parent_id' => $root['Aco']['id'], 'model' => null, 'alias' => $this->_getPluginName($ctrlName)));
                $pluginNode = $aco->save();
                $pluginNode['Aco']['id'] = $aco->id;
                $log[] = 'Created Aco node for ' . $this->_getPluginName($ctrlName) . ' Plugin';
            }
        }
        // find / make controller node
        $controllerNode = $aco->node('controllers/' . $ctrlName);
        if (!$controllerNode) {
            if ($this->_isPlugin($ctrlName)) {
                $pluginNode = $aco->node('controllers/' . $this->_getPluginName($ctrlName));
                $aco->create(array('parent_id' => $pluginNode['0']['Aco']['id'], 'model' => null, 'alias' => $this->_getPluginControllerName($ctrlName)));
                $controllerNode = $aco->save();
                $controllerNode['Aco']['id'] = $aco->id;
                $log[] = 'Created Aco node for ' . $this->_getPluginControllerName($ctrlName) . ' ' . $this->_getPluginName($ctrlName) . ' Plugin Controller';
            } else {
                $aco->create(array('parent_id' => $root['Aco']['id'], 'model' => null, 'alias' => $ctrlName));
                $controllerNode = $aco->save();
                $controllerNode['Aco']['id'] = $aco->id;
                $log[] = 'Created Aco node for ' . $ctrlName;
            }
        } else {
            $controllerNode = $controllerNode[0];
        }

        //clean the methods. to remove those in Controller and private actions.
        foreach ($methods as $k => $method) {
            if (strpos($method, '_', 0) === 0) {
                unset($methods[$k]);
                continue;
            }
            if (in_array($method, $baseMethods)) {
                unset($methods[$k]);
                continue;
            }
            $methodNode = $aco->node('controllers/' . $ctrlName . '/' . $method);
            if (!$methodNode) {
                $aco->create(array('parent_id' => $controllerNode['Aco']['id'], 'model' => null, 'alias' => $method));
                $methodNode = $aco->save();
                $log[] = 'Created Aco node for ' . $method;
            }
        }
    }
    if (count($log) > 0) {
        debug($log);
    }

    exit;
}

function _getClassMethods($ctrlName = null) {
    if($this->_isPlugin($ctrlName)){
        App::uses($this->_getPluginControllerName ($ctrlName), $this->_getPluginName ($ctrlName). 'Controller');
    }
    else
        App::uses($ctrlName . 'Controller', 'Controller');


    if (strlen(strstr($ctrlName, '.')) > 0) {
        // plugin's controller
        $ctrlName = str_replace('Controller', '', $this->_getPluginControllerName ($ctrlName));
    }
    $ctrlclass = $ctrlName . 'Controller';
    $methods = get_class_methods($ctrlclass);

    // Add scaffold defaults if scaffolds are being used
    $properties = get_class_vars($ctrlclass);
    if (array_key_exists('scaffold', $properties)) {
        if ($properties['scaffold'] == 'admin') {
            $methods = array_merge($methods, array('admin_add', 'admin_edit', 'admin_index', 'admin_view', 'admin_delete'));
        } else {
            $methods = array_merge($methods, array('add', 'edit', 'index', 'view', 'delete'));
        }
    }
    return $methods;
}

function _isPlugin($ctrlName = null) {
    $arr = String::tokenize($ctrlName, '.');
    if (count($arr) > 1) {
        return true;
    } else {
        return false;
    }
}

function _getPluginControllerPath($ctrlName = null) {
    $arr = String::tokenize($ctrlName, '/');
    if (count($arr) == 2) {
        return $arr[0] . '.' . $arr[1];
    } else {
        return $arr[0];
    }
}

function _getPluginName($ctrlName = null) {
    $arr = String::tokenize($ctrlName, '.');
    if (count($arr) == 2) {
        return $arr[0];
    } else {
        return false;
    }
}

function _getPluginControllerName($ctrlName = null) {
    $arr = String::tokenize($ctrlName, '/');
    if (count($arr) == 2) {
        return $arr[1];
    } else {
        return false;
    }
}

/**
 * Get the names of the plugin controllers ...
 *
 * This function will get an array of the plugin controller names, and
 * also makes sure the controllers are available for us to get the
 * method names by doing an App::import for each plugin controller.
 *
 * @return array of plugin names.
 *
 *
 */
function _getPluginControllerNames() {
    App::uses('Folder', 'Utility');
    $folder = & new Folder();
    $folder->cd(APP . 'Plugin');

    // Get the list of plugins
    $Plugins = $folder->read();
    $Plugins = $Plugins[0];
    $arr = array();

    // Loop through the plugins
    foreach ($Plugins as $pluginName) {
        // Change directory to the plugin
        $didCD = $folder->cd(APP . 'Plugin' . DS . $pluginName . DS . 'Controller');
        if ($didCD) {
            // Get a list of the files that have a file name that ends
            // with controller.php
            $files = $folder->findRecursive('.*Controller\.php');

            // Loop through the controllers we found in the plugins directory
            foreach ($files as $fileName) {
                // Get the base file name
                $file = basename($fileName);

                // Get the controller name
                //$file = Inflector::camelize(substr($file, 0, strlen($file) - strlen('Controller.php')));
                if (!preg_match('/^' . Inflector::humanize($pluginName) . 'App/', $file)) {
                    $file = str_replace('.php', '', $file);

                    /// Now prepend the Plugin name ...
                    // This is required to allow us to fetch the method names.
                    $arr[] = Inflector::humanize($pluginName) . "." . $file;
                }

            }
        }
    }


    return $arr;
    }




}
