<?php
require_once('../config.php');
Class Users extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	public function save_users(){
		extract($_POST);
		$oid = $id;
		$data = '';
		if(isset($oldpassword)){
			if(md5($oldpassword) != $this->settings->userdata('password')){
				return 4;
			}
		}
		$chk = $this->conn->query("SELECT * FROM `users` where username ='{$username}' ".($id>0? " and id!= '{$id}' " : ""))->num_rows;
		if($chk > 0){
			return 3;
			exit;
		}
		foreach($_POST as $k => $v){
			if(in_array($k,array('firstname','middlename','lastname','username','type'))){
				if(!empty($data)) $data .=" , ";
				$data .= " {$k} = '{$v}' ";
			}
		}
		if(!empty($password)){
			$password = md5($password);
			if(!empty($data)) $data .=" , ";
			$data .= " `password` = '{$password}' ";
		}

		if(empty($id)){
			$qry = $this->conn->query("INSERT INTO users set {$data}");
			if($qry){
				$id = $this->conn->insert_id;
				$this->settings->set_flashdata('success','User Details successfully saved.');
				$resp['status'] = 1;
			}else{
				$resp['status'] = 2;
			}

		}else{
			$qry = $this->conn->query("UPDATE users set $data where id = {$id}");
			if($qry){
				$this->settings->set_flashdata('success','User Details successfully updated.');
				if($id == $this->settings->userdata('id')){
					foreach($_POST as $k => $v){
						if($k != 'id'){
							if(!empty($data)) $data .=" , ";
							$this->settings->set_userdata($k,$v);
						}
					}
					
				}
				$resp['status'] = 1;
			}else{
				$resp['status'] = 2;
			}
			
		}
		if($resp['status'] == 1){
			$data="";
			foreach($_POST as $k => $v){
				if(!in_array($k,array('id','firstname','middlename','lastname','username','password','type','oldpassword'))){
					if(!empty($data)) $data .=", ";
					$v = $this->conn->real_escape_string($v);
					$data .= "('{$id}','{$k}', '{$v}')";
				}
			}
			if(!empty($data)){
				$this->conn->query("DELETE * FROM `user_meta` where user_id = '{$id}' ");
				$save = $this->conn->query("INSERT INTO `user_meta` (user_id,`meta_field`,`meta_value`) VALUES {$data}");
				if(!$save){
						$resp['status'] = 2;
					if(empty($oid)){
						$this->conn->query("DELETE * FROM `users` where id = '{$id}' ");
					}
				}
			}
		}
		
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = 'uploads/avatar-'.$id.'.png';
			$dir_path =base_app. $fname;
			$upload = $_FILES['img']['tmp_name'];
			$type = mime_content_type($upload);
			$allowed = array('image/png','image/jpeg');
			if(!in_array($type,$allowed)){
				$resp['msg'].=" But Image failed to upload due to invalid file type.";
			}else{
				$new_height = 200; 
				$new_width = 200; 
		
				list($width, $height) = getimagesize($upload);
				$t_image = imagecreatetruecolor($new_width, $new_height);
				imagealphablending( $t_image, false );
				imagesavealpha( $t_image, true );
				$gdImg = ($type == 'image/png')? imagecreatefrompng($upload) : imagecreatefromjpeg($upload);
				imagecopyresampled($t_image, $gdImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				if($gdImg){
						if(is_file($dir_path))
						unlink($dir_path);
						$uploaded_img = imagepng($t_image,$dir_path);
						imagedestroy($gdImg);
						imagedestroy($t_image);
				}else{
				$resp['msg'].=" But Image failed to upload due to unkown reason.";
				}
			}
			if(isset($uploaded_img)){
				$this->conn->query("UPDATE users set `avatar` = CONCAT('{$fname}','?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$id}' ");
				if($id == $this->settings->userdata('id')){
						$this->settings->set_userdata('avatar',$fname);
				}
			}
		}
		if(isset($resp['msg']))
		$this->settings->set_flashdata('success',$resp['msg']);
		return  $resp['status'];
	}
	public function delete_users(){
		extract($_POST);
		$avatar = $this->conn->query("SELECT avatar FROM users where id = '{$id}'")->fetch_array()['avatar'];
		$qry = $this->conn->query("DELETE FROM users where id = $id");
		if($qry){
			$avatar = explode("?",$avatar)[0];
			$this->settings->set_flashdata('success','User Details successfully deleted.');
			if(is_file(base_app.$avatar))
				unlink(base_app.$avatar);
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
		}
		return json_encode($resp);
	}
	public function save_client(){
		extract($_POST);
		$oid = $id;
		$data = '';
		if(isset($oldpassword)){
			if(md5($oldpassword) != $this->settings->userdata('password')){
				return 4;
			}
		}
		$chk = $this->conn->query("SELECT * FROM `clients` where email ='{$email}' ".($id>0? " and id!= '{$id}' " : ""))->num_rows;
		if($chk > 0){
			return json_encode(["status"=>'failed',"msg"=>"Email Already Exists."]);
			exit;
		}
		foreach($_POST as $k => $v){
			if(!in_array($k,array('id','password','cpassword','cur_password'))){
				if(!empty($data)) $data .=" , ";
				$data .= " {$k} = '{$v}' ";
			}
		}
		if(isset($password) && !empty($password)){
			$password = md5($password);
			if(!empty($data)) $data .=" , ";
			$data .= " `password` = '{$password}' ";
		}

		if(empty($id)){
			$qry = $this->conn->query("INSERT INTO clients set {$data}");
			if($qry){
				$id = $this->conn->insert_id;
				if($this->settings->userdata('id'))
				$resp['msg'] ='Client Details successfully saved.';
				else
				$resp['msg'] ='You Account has Successfully registered.';
				$resp['status'] = 'success';
				$resp['id'] = $id;
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = 'An error occured. Error: '.$this->conn->error;
			}

		}else{
			if(isset($cur_password) && md5($cur_password) != $this->settings->userdata('password')){
				$resp['status'] = 'failed';
				$resp['msg'] = 'Current Password is Incorrect.';
				return json_encode($resp);
			}
			$qry = $this->conn->query("UPDATE clients set $data where id = {$id}");
			if($qry){
				$resp['msg'] = 'User Details successfully updated.';
				if($this->settings->userdata('login_type') != 1 && $id == $this->settings->userdata('id')){
					foreach($_POST as $k => $v){
						if($k == 'password' && empty($v))
						continue;
						else
						$v = md5($v);
						if($k != 'id'){
							if(!empty($data)) $data .=" , ";
							$this->settings->set_userdata($k,$v);
						}
					}
					$this->settings->set_userdata('fullname',"{$lastname}, {$firstname} {$middlename}");
					
				}
				$resp['status'] = 'success';
				$resp['id'] = $id;
			}else{
				$resp['status'] = 'failed';
				$resp['msg'] = 'An error occured. Error: '.$this->conn->error;
				$resp['sql'] = "UPDATE users set $data where id = {$id}";
			}
		}
		if(isset($_FILES['img']) && $_FILES['img']['tmp_name'] != ''){
			$fname = 'uploads/client-'.$id.'.png';
			$dir_path =base_app. $fname;
			$upload = $_FILES['img']['tmp_name'];
			$type = mime_content_type($upload);
			$allowed = array('image/png','image/jpeg');
			if(!in_array($type,$allowed)){
				$resp['msg'].=" But Image failed to upload due to invalid file type.";
			}else{
				$new_height = 200; 
				$new_width = 200; 
		
				list($width, $height) = getimagesize($upload);
				$t_image = imagecreatetruecolor($new_width, $new_height);
				imagealphablending( $t_image, false );
				imagesavealpha( $t_image, true );
				$gdImg = ($type == 'image/png')? imagecreatefrompng($upload) : imagecreatefromjpeg($upload);
				imagecopyresampled($t_image, $gdImg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
				if($gdImg){
						if(is_file($dir_path))
						unlink($dir_path);
						$uploaded_img = imagepng($t_image,$dir_path);
						imagedestroy($gdImg);
						imagedestroy($t_image);
				}else{
				$resp['msg'].=" But Image failed to upload due to unkown reason.";
				}
			}
			if(isset($uploaded_img)){
				$this->conn->query("UPDATE clients set `avatar` = CONCAT('{$fname}','?v=',unix_timestamp(CURRENT_TIMESTAMP)) where id = '{$id}' ");
				if($this->settings->userdata('login_type') != 1 && $id == $this->settings->userdata('id')){
						$this->settings->set_userdata('avatar',$fname."?v=".(time()));
				}
			}
		}
		if(isset($resp['msg']) && $resp['status'] == 'success')
		$this->settings->set_flashdata('success',$resp['msg']);
		return  json_encode($resp);
	}
	public function delete_client(){
		extract($_POST);
		$avatar = $this->conn->query("SELECT avatar FROM clients where id = '{$id}'")->fetch_array()['avatar'];
		$qry = $this->conn->query("DELETE FROM clients where id = $id");
		if($qry){
			$avatar = explode("?",$avatar)[0];
			$this->settings->set_flashdata('success','Client has successfully deleted.');
			if(is_file(base_app.$avatar))
				unlink(base_app.$avatar);
			$resp['status'] = 'success';
		}else{
			$resp['status'] = 'failed';
		}
		return json_encode($resp);
	}
	
}

$users = new users();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
switch ($action) {
	case 'save':
		echo $users->save_users();
	break;;
	case 'delete':
		echo $users->delete_users();
	break;
	case 'save_client':
		echo $users->save_client();
	break;;
	case 'delete_client':
		echo $users->delete_client();
	break;
	default:
		// echo $sysset->index();
		break;
}