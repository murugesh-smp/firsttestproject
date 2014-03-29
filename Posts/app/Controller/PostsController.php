<?php

class PostsController extends AppController {
	 public $helpers = array('Html', 'Form', 'Session');
    public $components = array('Session');
	
	public function index()
	{
        //$this->set('title_for_layout', 'Testing for titile');
		//$this->set('posts',$this->Post->find('all'));
		
      
     $this->paginate = array(
        'recursive' => 0,
        'conditions' => array('user_id' => $this->Auth->user('id')),
    );
    $this->set('posts', $this->paginate());
	}

//     public function isAuthorized($user) {
//     // All registered users can add posts
//     if ($this->action === 'add') {
//         return true;
//     }

//     // The owner of a post can edit and delete it
//     if (in_array($this->action, array('edit', 'delete'))) {
//         $postId = $this->request->params['pass'][0];
//         if ($this->Post->isOwnedBy($postId, $user['id'])) {
//             return true;
//         }
//     }

//     return parent::isAuthorized($user);
// }

	public function view($id = null) {
		$this->layout= 'test';
		if (!$id) {
		throw new NotFoundException(__('Invalid post'));
		}
		$post = $this->Post->findById($id);
		if (!$post) {
		throw new NotFoundException(__('Invalid post'));
		}
		$this->set('post', $post);
	}

	public function add() {
        if ($this->request->is('post')) {

            $this->Post->create();
             $this->request->data['Post']['user_id'] = $this->Auth->user('id'); 
            if ($this->Post->save($this->request->data)) {
                $this->Session->setFlash(__('Your post has been saved.'));
                $this->redirect(array('action' => 'index'));
            } else {
                $this->Session->setFlash(__('Unable to add your post.'));
            }
        }
    }

    public function edit($id = null) {
    if (!$id) {
        throw new NotFoundException(__('Invalid post'));
    }

    $post = $this->Post->findById($id);
    if (!$post) {
        throw new NotFoundException(__('Invalid post'));
    }

    if ($this->request->is('post') || $this->request->is('put')) {
        $this->Post->id = $id;
        if ($this->Post->save($this->request->data)) {
            $this->Session->setFlash(__('Your post has been updated.'));
            $this->redirect(array('action' => 'index'));
        } else {
            $this->Session->setFlash(__('Unable to update your post.'));
        }
    }

    if (!$this->request->data) {
        $this->request->data = $post;
    }
}
    public function delete($id) {
    if ($this->request->is('get')) {
        throw new MethodNotAllowedException();
    }

    if ($this->Post->delete($id)) {
        $this->Session->setFlash(__('The post with id: %s has been deleted.', $id));
        $this->redirect(array('action' => 'index'));
    }
}

     function initDB() {
        $this->loadModel('Group');
    $group =& $this->User->Group;
    //Allow admins to everything
    $group->id = 1;
    $this->Acl->allow($group, 'controllers');

    //allow managers to posts and widgets
    $group->id = 2;
    $this->Acl->deny($group, 'controllers');
    $this->Acl->allow($group, 'controllers/Posts/view');
    

    //allow users to only add and edit on posts and widgets
    
    //we add an exit to avoid an ugly "missing views" error message
    echo "all done";
    exit;
}

}

?>