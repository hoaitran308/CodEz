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
            $data = [
                'title' => 'Login'
            ];

            return $this->view('user.login', $data);
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
                if(!Func::isValidUserName($username))
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
                if(!Func::isValidPassword($password))
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
                $user_id = $this->userModel->addUser($username, $password, $fullname, $email);
                if($user_id)
                {
                    // copy default avatar and cover to user
                    $avatar = Func::copyFileFromTo('images/defaults/avatar.png', 'images.users.avatars', $user_id);
                    $cover = Func::copyFileFromTo('images/defaults/cover.jpg', 'images.users.covers', $user_id);
                    $this->userModel->updateUserByStringQuery($user_id, "`avatar` = '$avatar', `cover` = '$cover'");
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
            $_SESSION['user_id'] = 1; // change later

            $id = (isset($_GET['id']) ? intval($_GET['id']) : 0);

            $user = $this->userModel->getUserById($id);

            if ($user == null || $id != $_SESSION['user_id'])
            {
                return $this->view('404');
            }
               
            if(isset($_POST['submitchangeuser']))
            {      
                $fullname = htmlspecialchars($_POST['fullname']);
                $password = htmlspecialchars($_POST['password']);
                $repassword = htmlspecialchars($_POST['repassword']);
                $birthday = $_POST['birthday'];
                $website = $_POST['website'];
                $profileheading = htmlspecialchars($_POST['profileheading']);
                $about = htmlspecialchars($_POST['about']);
                $facebook = $_POST['facebook'];
                $instagram = $_POST['instagram'];
                $twitter = $_POST['twitter'];

                $avatar = $user['avatar'];
                Func::uploadFile('images.users.avatars','profile-image-avatar', $avatar, $avatar_message, true);

                $cover = $user['cover'];
                Func::uploadFile('images.users.covers',  'profile-image-cover', $cover, $cover_message, true);

                if (!Func::isAnyEmptyValue([$website]) && !Func::isValidWebsite($website))
                {
                    return header('Location: ' . ROUTES['user'] . '&id=' .$id. ''); 
                }

                if (!Func::isAnyEmptyValue([$facebook]) && !Func::isContain('facebook.com', $facebook))
                {
                    return header('Location: ' . ROUTES['user'] . '&id=' .$id. ''); 
                }

                if (!Func::isAnyEmptyValue([$instagram]) && !Func::isContain('instagram.com', $instagram))
                {
                    return header('Location: ' . ROUTES['user'] . '&id=' .$id. ''); 
                }

                if (!Func::isAnyEmptyValue([$twitter]) && !Func::isContain('twitter.com', $twitter))
                {
                    return header('Location: ' . ROUTES['user'] . '&id=' .$id. ''); 
                }
                
                if (Func::isAnyEmptyValue([$fullname, $password, $repassword]) || $password !== $repassword)
                {
                    return header('Location: ' . ROUTES['user'] . '&id=' .$id. ''); 
                }

                $user_changed = [
                    'id' => $id,
                    'fullname' => $fullname,
                    'password' => $password,
                    'repassword' => $repassword,
                    'birthday' => $birthday,
                    'website' => htmlspecialchars($website),
                    'profileheading' => $profileheading,
                    'about' => $about,
                    'facebook' => htmlspecialchars($facebook),
                    'instagram' => htmlspecialchars($instagram),
                    'twitter' => htmlspecialchars($twitter),
                    'avatar' => $avatar,
                    'cover' => $cover
                ]; 
                
                if (Func::isValidMd5($password))
                {
                    $this->userModel->updateUserNoPass($user_changed);      
                    return header('Location: ' . ROUTES['user'] . '&id=' .$id. ''); 
                }

                $this->userModel->updateUser($user_changed);      
                return header('Location: ' . ROUTES['user'] . '&id=' .$id. '');
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