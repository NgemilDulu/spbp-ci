<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Control_User extends CI_Controller {

    public function __construct() {

        parent::__construct();
        
        $this->load->helper('url');
        $this->load->helper('form');
        $this->load->library('form_validation');
        $this->load->model('model_user');
        $this->load->model('model_admin');
        $this->load->database();
        $this->load->library('session');
    }

	public function index() {
		$this->load->view('home');
    }
    
    public function view($page = 'home') {

        if(!file_exists(APPPATH.'views/'.$page.'.php')) {
            show_404();
        }

        if ($page == 'catalogue') {

            $data['filter'] = "all";

            $result['result'] = $this->model_user->getBook($data);

            if ($result['result'] == false) {

                $data['error_message'] = "Sorry, books not found";
                $this->load->view($page, $data);

            } else {
                $this->load->view($page, $result);
            }
        } else {
            $this->load->view($page);
        }
    }

    public function viewDetails($isbn) {

        if (isset($_SESSION['logged_in'])) {

            $data = array (
                'username' => $_SESSION['logged_in'],
                'isbn' => $isbn
            );
        } else {

            $data = array (
                'username' => "",
                'isbn' => $isbn
            );
        }

        $result = $this->model_user->getDetails($data);

        if ($result == false) {
            show_404();
        } else {
            $this->load->view('detail', $result);
        }
    }

    public function login() {

        $this->form_validation->set_rules('username', 'Username', 'trim|required|min_length[4]|max_length[50]');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');

        if ($this->form_validation->run() == false) {
            
            $data = array(
                'loginSuccess' => false,
                'error_message' => ""
            );
            $this->load->view('home', $data);

        } else {

            $data = array(
                'username' => $this->input->post('username'),
                'password' => $this->input->post('password')
            );

            $result = $this->model_user->validateUser($data);

            if ($result == true) {

                $result = $this->model_user->getUserInformation($this->input->post('username'));

                if ($result[0]->privilege == 'member') {

                    $this->session->set_userdata('logged_in', $result[0]->username);
                    $this->session->set_userdata('nickname', $result[0]->nickname);
    
                    $data['loginSuccess'] = true;
                    $this->load->view('home', $data);

                } else {

                    $this->session->set_userdata('logged_in_admin', $result[0]->username);
                    $this->session->set_userdata('nickname', $result[0]->nickname);
    
                    $result['result'] = $this->model_admin->getUser();

                    $this->load->view('dashboard', $result);
                }
            } else {

                $data = array(
                    'loginSuccess' => false,
                    'error_message' => "Username or password wrong, please try again"
                );
                $this->load->view('home', $data);

            }
        }
    }

    public function signUp() {

        $this->form_validation->set_rules('nickname', 'Nickname', 'trim|required|min_length[4]|max_length[50]');
        $this->form_validation->set_rules('username', 'Username', 'trim|required|min_length[4]|max_length[50]');
        $this->form_validation->set_rules('password', 'Password', 'trim|required|min_length[6]');
        $this->form_validation->set_rules('confirmPassword', 'Repeat Password', 'trim|required|min_length[6]|matches[password]');

        if ($this->form_validation->run() == false) {

            $data = array(
                'signUpSuccess' => false,
                'error_message' => ""
            ); 
            $this->load->view('home', $data);

        } else {

            $data = array(
                'username' => $this->input->post('username'),
                'nickname' => $this->input->post('nickname'),
                'password' => password_hash($this->input->post('password'), PASSWORD_DEFAULT),
                'privilege' => 'member'
            );

            $result = $this->model_user->createUser($data);

            if ($result == true) {
                
                $data['signUpSuccess'] = true;
                $this->load->view('home', $data);

            } else {

                $data = array(
                    'signUpSuccess' => false,
                    'error_message' => "The username that you want to use is already registered in our system, Please try again with different username"
                );
                $this->load->view('home', $data);

            }
        }
    }

    public function logout() {

        $this->session->sess_destroy();
        redirect('home');
    }

    public function editProfile($username) {

        $this->form_validation->set_rules('nickname', 'Nickname', 'trim|required|min_length[4]|max_length[50]');
        $this->form_validation->set_rules('oldPassword', 'Old Password', 'trim|required|min_length[6]');
        $this->form_validation->set_rules('newPassword', 'New Password', 'trim|required|min_length[6]|differs[oldPassword]');

        if ($this->form_validation->run() == false) {
            
            $data = array(
                'editProfileSuccess' => false,
                'error_message' => ""
            ); 
            $this->load->view('home', $data);

        } else {

            $data = array(
                'username' => $username,
                'nickname' => $this->input->post('nickname'),
                'oldPassword' => $this->input->post('oldPassword'),
                'newPassword' => $this->input->post('newPassword'),
            );

            $result = $this->model_user->updateProfile($data);

            if ($result == true) {

                $data['editProfileSuccess'] = true;

                $result = $this->model_user->getUserInformation($username);
                
                $this->session->sess_destroy();
                $this->session->set_userdata('logged_in', $result[0]->username);
                $this->session->set_userdata('nickname', $result[0]->nickname);

                $this->load->view('home', $data);

            } else {

                $data = array(
                    'editProfileSuccess' => false,
                    'error_message' => "The old password that you entered must be the same with your previous password, Please try again"
                );
                $this->load->view('home', $data);

            }
        }
    }

    public function book($isbn) {

        $data = array(
            'username' => $_SESSION['logged_in'],
            'isbn' => $isbn,
            'type' => "Peminjaman",
            'verified' => 0
        );

        $result = $this->model_user->createTransaction($data);

        if ($result == true) {

            $data['bookSuccess'] = true;
            $this->load->view('home', $data);

        } else {

            $data = array(
                'bookSuccess' => false,
                'error_message' => "Kamu sedang meminjam buku ini, pengguna hanya diizinkan meminjam 1 buku dengan judul yang sama"
            );
            $this->load->view('home', $data);

        }
    }

    public function return($isbn) {

        $data = array(
            'username' => $_SESSION['logged_in'],
            'isbn' => $isbn,
            'type' => "Pengembalian",
            'verified' => 0
        );

        $result = $this->model_user->createTransaction($data);

        if ($result == true) {
            
            $data['bookSuccess'] = true;
            $this->load->view('home', $data);

        } else {
            
            $data = array(
                'bookSuccess' => false,
                'error_message' => "Kamu sedang meminjam buku ini, pengguna hanya diizinkan meminjam 1 buku dengan judul yang sama"
            );
            $this->load->view('home', $data);

        }
    }

    public function donate() {

        $this->form_validation->set_rules('title', 'Title', 'trim|required');
        $this->form_validation->set_rules('isbn', 'ISBN', 'trim|required|integer');
        $this->form_validation->set_rules('category', 'Category', 'trim|required|alpha');
        $this->form_validation->set_rules('language', 'Language', 'trim|required|alpha');
        $this->form_validation->set_rules('penulis', 'Penulis', 'trim|required|alpha_numeric_spaces');
        $this->form_validation->set_rules('penerbit', 'Penerbit', 'trim|required|alpha_numeric_spaces');
        $this->form_validation->set_rules('tanggal', 'Tanggal', 'trim|required');
        $this->form_validation->set_rules('halaman', 'Halaman', 'trim|required|numeric');
        $this->form_validation->set_rules('deskripsi', 'Deskripsi', 'trim|required');

        $config['upload_path'] = realpath(APPPATH .'../assets/img/cover');;
        $config['allowed_types'] = 'gif|jpg|png|jpeg';

        $this->load->library('upload', $config);

        if ($this->form_validation->run() == false) {
            
            $data = array(
                'donateSuccess' => false,
                'error_message' => ""
            ); 
            $this->load->view('home', $data);

        } else {

            if (!$this->upload->do_upload('file')) {

                $data = array(
                    'donateSuccess' => false,
                    'error_message' => $this->upload->display_errors()
                );

                $this->load->view('home', $data);

            } else {

                $upload_data = $this->upload->data();
                
                $data = array(
                    'isbn' => $this->input->post('isbn'),
                    'image' => $upload_data['file_name'],
                    'judul' => $this->input->post('title'),
                    'deskripsi' => $this->input->post('deskripsi'),                    
                    'penulis' => $this->input->post('penulis'),
                    'penerbit' => $this->input->post('penerbit'),                    
                    'tgl_terbit' => $this->input->post('tanggal'),
                    'halaman' => $this->input->post('halaman'),
                    'kategori' => $this->input->post('category'),
                    'bahasa' => $this->input->post('language'),
                    'stock' => 0
                );
                
                $result = $this->model_user->createBook($data);
                
                if ($result == true) {
                    
                    $data = array(
                        'username' => $_SESSION['logged_in'],
                        'isbn' => $this->input->post('isbn'),
                        'type' => "Donasi",
                        'verified' => 0
                    );
    
                    $result = $this->model_user->createTransaction($data);
    
                    if ($result == true) {

                        $data['donateSuccess'] = true;
                        $this->load->view('home', $data);

                    } else {

                        $data = array(
                            'donateSuccess' => false,
                            'error_message' => "Kamu sudah mengajukan donasi buku ini, harap tunggu konfirmasi dari kami"
                        );
                        $this->load->view('home', $data);

                    }
                } else {

                    $data = array(
                        'donateSuccess' => false,
                        'error_message' => ""
                    );
                    $this->load->view('home', $data);
                }
            }
        }
    }
}