<?php
    class UserController extends BaseController
    {
        private $userModel;

        public function __construct()
        {
            $this->loadModel('usermodel');
            $this->userModel = new UserModel;
        }

        public function index()
        {
            $id = (isset($_GET['id']) ? intval($_GET['id']) : 0);

            $user = $this->userModel->getUserById($id);

            if ($user == null)
            {
                return $this->view('404');
            }

            $data = [
                'id' => $id,
                'title' => 'User Profile',
                'user' => $user
            ];
            
            return $this->view('user.index', $data);
        }

        public function login()
        {            
            $data = ['title' => 'Login'];

            if(isset($_POST['submit']))
            {
                $username = Func::getInput($_POST['username']);
                $password = Func::getInput(($_POST['password']));
                
                $result = $this->userModel->getLogin($username, $password);
                if($result == 'login')
                {
                    return $this->view('home.index', $data = ['CodeZ.Shop - Coding is hard? Just buy it.']);
                }
                else
                {
                    return $this->view('user.Login', $data);
                }
            }
            else
            {
                return $this->view('user.Login', $data);
            }
        }

        public function register()
        {
            $data = [
                'title' => 'Register',
                'errors' => '',
            ];
            if(isset($_POST['submit']))
            {
                $username = Func::getInput($_POST['username']);
                $password = Func::getInput(($_POST['password']));
                $fullname = Func::getInput($_POST['yourname']);
                $email = Func::getInput($_POST['email']);
                $confirmpassword =Func::getInput(($_POST['confirmpassword']));
                $user = $this->userModel->getUserByUsername($username);
                $input = [
                    'username' => $username,
                    'password' => $password,
                    'fullname' => $fullname,
                    'email' => $email,
                    'confirmpassword' => $confirmpassword,
                ];
               
                // kiểm tra có input nào bị bỏ trống không
                if(Func::isAnyEmptyValue($input))
                {   
                    $data['errors'] = MESSAGES['input_empty'];
                    return $this->view('user.register', $data);
                }

                // kiểm tra có đúng form của email hay không
                $email = filter_var($email,FILTER_SANITIZE_EMAIL);
                if(!filter_var($email,FILTER_VALIDATE_EMAIL))
                {
                    $data['errors'] = MESSAGES['emailtype_error'];
                    return $this->view('user.register', $data);
                }
                
                // kiểm tra yêu cầu về tên
                if(!Func::checkUserName($username))
                { 
                    $data['errors'] = MESSAGES['nametype_error'];
                    return $this->view('user.register', $data);
                }

                // kiểm tra "email" và "username" đã tồn tại chưa
                if(!empty($user))
                {
                    $data['errors'] = MESSAGES['username_used'];
                    return $this->view('user.register', $data);
                }

                // kiểm tra các yêu cầu về mật khẩu
                if(!Func::checkPassword($password))
                {
                    $data['errors'] = MESSAGES['passwordtype_error'];
                    return $this->view('user.register', $data);
                }

                // kiểm tra "mật khẩu" có khớp với "xác nhận mật khẩu"
                $password = md5(md5($password));
                $confirmpassword = md5(md5($confirmpassword));
                if($password!==$confirmpassword)
                { 
                    $data['errors'] = MESSAGES['password_confirmpassword_notsame'];
                    return $this->view('user.register',$data);
                }

                // Thêm vào database
                $issuccess = $this->userModel->addUser($username,$password,$fullname,$email);
                if($issuccess)
                {
                    return $this->view('user.registersuccess',$data);
                }
                else
                {
                    return $this->view('user.register',$data);
                }
            } 
            return $this->view('user.register', $data);
        }

        public function edit()
        {
            $id = (isset($_GET['id']) ? intval($_GET['id']) : 0);
            $user = $this->userModel->getUserById($id);

            if ($user == null)
            {
                return $this->view('404');
            }
               
            if(isset($_POST['submitchangeuser']))
            {      
                $fullname = htmlspecialchars($_POST['fullname']);
                $password = htmlspecialchars($_POST['password']);
                $repassword = htmlspecialchars($_POST['repassword']);
                $birthday = $_POST['birthday'];
                $website = htmlspecialchars($_POST['website']);
                $profileheading = htmlspecialchars($_POST['profileheading']);
                $about = htmlspecialchars($_POST['about']);
                $facebook = htmlspecialchars($_POST['facebook']);
                $instagram = htmlspecialchars($_POST['instagram']);
                $twitter = htmlspecialchars($_POST['twitter']);

                $error = '';
                $avatar = '';
                $avatar_message = '';
                if (!Func::uploadFile('images.users.avatars','profile-image-avatar', $avatar, $avatar_message, true))
                {
                    $error = empty($error) ? $avatar_message : $error;
                }

                if(empty($avatar)){
                    $avatar = $user['avatar'];
                }

                $errorcover = '';
                $cover = '';
                $cover_message = '';
                if (!Func::uploadFile('images.users.covers',  'profile-image-cover', $cover, $cover_message, true))
                {
                    $error = empty($errorcover) ? $cover_message : $errorcover;
                }
                if(empty($cover)){
                    $cover = $user['cover'];
                }

                $userchange = [
                    'id' => $id,
                    'fullname' => $fullname,
                    'password' => $password,
                    'repassword' => $repassword,
                    'birthday' => $birthday,
                    'website' => $website,
                    'profileheading' => $profileheading,
                    'about' => $about,
                    'facebook' => $facebook,
                    'instagram' => $instagram,
                    'twitter' => $twitter,
                    'avatar' => $avatar,
                    'cover' => $cover
                ];  
                
                if(!empty($fullname) && !empty($password) &&  !empty($repassword) &&  !empty($birthday)  &&  !empty($profileheading) 
                ){
                    if($password == $repassword)  {  
                        if(Func::isValidMd5($password) && Func::isValidMd5($password)){
                            $this->userModel->updateUserNoPass($userchange);      
                            header('Location: ' . ROUTES['user'] . '&id=' .$id. ''); 
                        }
                        else{
                            $this->userModel->updateUser($userchange);      
                            header('Location: ' . ROUTES['user'] . '&id=' .$id. ''); 
                        }
                    }     
                    else{ 
                        header('Location: ' . ROUTES['user_edit']  . '&id=' .$id. ''); 
                    }           
                }
                else{
                    header('Location: ' . ROUTES['user_edit']  . '&id=' .$id. ''); 
                }
            }
            $data = [
                'id' => $id,
                'title' => 'Edit Profile',
                'user' => $user
            ];
            return $this->view('user.edit', $data);
    
        }
    }
?>