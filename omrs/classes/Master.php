<?php
require_once('../config.php');
Class Master extends DBConnection {
	private $settings;
	public function __construct(){
		global $_settings;
		$this->settings = $_settings;
		parent::__construct();
	}
	public function __destruct(){
		parent::__destruct();
	}
	function capture_err(){
		if(!$this->conn->error)
			return false;
		else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
			return json_encode($resp);
			exit;
		}
	}
	function save_marriage_information(){
		
		if(empty($_POST['id'])){
			$prefix = implode('',range('A','Z'));
			$prefix = substr(str_shuffle($prefix),0,3)."-";
			$i = sprintf("%'.04d",1);
			while(true){
				$code = $prefix.$i;
				$check = $this->conn->query("SELECT id FROM `registration_list` where registration_code = '{$code}'")->num_rows;
				if($check <= 0){
					break;
				}else{
					$i = sprintf("%'.04d",abs($i)+1);
				}
			}
			$_POST['registration_code'] = $code;
			$_POST['client_id'] = $this->settings->userdata('id');
		}
		if(isset($_POST['status'])){
			$get = $this->conn->query("SELECT * FROM `registration_list` where id= '{$_POST['id']}'");
			if($get->num_rows > 0){
				$res = $get->fetch_array();
				if($_POST['status'] != $res['status']){
					$_POST['action_user_id'] = $this->settings->userdata('id');
				}
			}
		}
		extract($_POST);
		$data = "";
		foreach($_POST as $k =>$v){
			if(in_array($k,array('registration_code','client_id','schedule','action_user_id','status'))){
				if(!empty($data)) $data .=",";
				$data .= " `{$k}`='{$v}' ";
			}
		}
		if(empty($id)){
			$sql = "INSERT INTO `registration_list` set {$data} ";
		}else{
			$sql = "UPDATE `registration_list` set {$data} where id = '{$id}' ";
		}
		$save = $this->conn->query($sql);
		if($save){
			$rid = !empty($id) ? $id : $this->conn->insert_id;
			$resp['status'] = 'success';
			$data = "";
			foreach($_POST as $k =>$v){
				if(!in_array($k,array('id','registration_code','client_id','schedule','action_user_id','status'))){
					if(!empty($data)) $data .=",";
					$data .= "('{$rid}','{$k}','{$v}')";
				}
			}
			if(!empty($data)){
				$this->conn->query("DELETE FROM `registration_details` where registration_id = '{$rid}' and (meta_field not IN ('groom_image',bride_image))");
				$sql2= "INSERT INTO `registration_details` (`registration_id`,`meta_field`,`meta_value`) VALUES {$data}";
				$save2 = $this->conn->query($sql2);
				if($save2){
					$resp['status']='success';
					if(empty($id)){
						$resp["msg"] ="Marriage Registration Successfully added";
					}else{
						$resp["msg"] ="Marriage Registration Details was Successfully updated";
					}
					$resp['id'] = $rid;
				}else{
					$resp['status'] = 'failed';
					$resp['err'] = $this->conn->error."[{$sql2}]";
					$resp['msg'] = "An error occured.";
					$this->conn->query("DELETE FROM `registration_details` where id = '{$rid}'");
				}
			}

		}else{
			$resp['status'] = 'failed';
			$resp['msg'] = "An error occured.";
			$resp['err'] = $this->conn->error."[{$sql}]";
		}

		if($resp['status'] =='success' && (isset($_FILES['img']))){
			foreach($_FILES['img']['tmp_name'] as $k=> $v){
				if(!empty($_FILES['img']['tmp_name'][$k])){
					$fname = "uploads/{$k}-".$rid.'.png';
					$dir_path =base_app. $fname;
					$upload = $_FILES['img']['tmp_name'][$k];
					$type = mime_content_type($upload);
					$allowed = array('image/png','image/jpeg');
					if(!in_array($type,$allowed)){
						$resp['msg'].=" {$k}'s Image failed to upload due to invalid file type.";
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
								if($uploaded_img){
									$this->conn->query("DELETE FROM `registration_details` where registration_id = '{$rid}' and meta_field='{$k}_image'");
									$this->conn->query("INSERT INTO `registration_details` (`registration_id`,`meta_field`,`meta_value`) VALUES ('{$rid}','{$k}_image',Concat('{$fname}?v=',unix_timestamp(CURRENT_TIMESTAMP))) ");
								}
						}else{
						$resp['msg'].=" {$k}'s Image failed to upload due to unkown reason.";
						}
					}
				}
			}

		}
		if($resp['status'] =='success')
		$this->settings->set_flashdata('success',$resp['msg']);
		// echo "<pre>";
		// var_dump($_FILES);
		// echo "</pre>";
		// exit;
		return json_encode($resp);
	}
	function delete_marriage_information(){
		extract($_POST);
		$get =	$this->conn->query("SELECT * FROM `registration_details` where registration_id = '{$id}' and meta_field in ('groom_image','bride_image')");
		$del = $this->conn->query("DELETE FROM `registration_list` where id = '{$id}'");
		if($del){
			$resp['status'] = 'success';
			$this->settings->set_flashdata('success',"Marriage Information has successfully deleted.");
			while($row = $get->fetch_assoc()){
				$img  = $row['meta_value'];
				$img  = explode("?",$img)[0];
				if(is_file(base_app.$img))
					unlink(base_app.$img);
			}
		}else{
			$resp['status'] = 'failed';
			$resp['error'] = $this->conn->error;
		}
		return json_encode($resp);

	}
}

$Master = new Master();
$action = !isset($_GET['f']) ? 'none' : strtolower($_GET['f']);
$sysset = new SystemSettings();
switch ($action) {
	case 'save_marriage_information':
		echo $Master->save_marriage_information();
	break;
	case 'delete_marriage_information':
		echo $Master->delete_marriage_information();
	break;
	default:
		// echo $sysset->index();
		break;
}