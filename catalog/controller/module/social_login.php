<?php
namespace Opencart\Catalog\Controller\Extension\SocialLogin\Module;

class SocialLogin extends \Opencart\System\Engine\Controller {
    
    public function index(): string {
        if (!$this->config->get('module_social_login_status')) {
            return '';
        }
        
        $this->load->language('extension/social_login/module/social_login');
        
        $data['facebook_login_url'] = $this->url->link('extension/social_login/module/social_login|facebook', '', true);
        $data['google_login_url'] = $this->url->link('extension/social_login/module/social_login|google', '', true);
        $data['line_login_url'] = $this->url->link('extension/social_login/module/social_login|line', '', true);
        
        $data['text_social_login'] = $this->language->get('text_social_login');
        $data['text_login_facebook'] = $this->language->get('text_login_facebook');
        $data['text_login_google'] = $this->language->get('text_login_google');
        $data['text_login_line'] = $this->language->get('text_login_line');
        $data['text_or_login_email'] = $this->language->get('text_or_login_email');
        $data['text_secure_login'] = $this->language->get('text_secure_login');
        $data['text_connecting'] = $this->language->get('text_connecting');
        $data['text_social_login_help'] = $this->language->get('text_social_login_help');
        
        return $this->load->view('extension/social_login/module/social_login', $data);
    }
    
    public function facebook(): void {
        if (!$this->config->get('module_social_login_status')) {
            $this->response->redirect($this->url->link('account/login', '', true));
            return;
        }
        
        if (!isset($this->request->get['code'])) {
            $this->initiateFacebookAuth();
        } else {
            $this->handleFacebookCallback();
        }
    }
    
    public function google(): void {
        if (!$this->config->get('module_social_login_status')) {
            $this->response->redirect($this->url->link('account/login', '', true));
            return;
        }
        
        if (!isset($this->request->get['code'])) {
            $this->initiateGoogleAuth();
        } else {
            $this->handleGoogleCallback();
        }
    }
    
    public function line(): void {
        if (!$this->config->get('module_social_login_status')) {
            $this->response->redirect($this->url->link('account/login', '', true));
            return;
        }
        
        if (!isset($this->request->get['code'])) {
            $this->initiateLineAuth();
        } else {
            $this->handleLineCallback();
        }
    }
    
    private function initiateFacebookAuth(): void {
        $facebook_app_id = $this->config->get('module_social_login_facebook_app_id');
        $facebook_app_secret = $this->config->get('module_social_login_facebook_app_secret');
        
        if (empty($facebook_app_id) || empty($facebook_app_secret)) {
            $this->load->language('extension/social_login/module/social_login');
            $this->session->data['error'] = $this->language->get('error_facebook_login_failed');
            $this->response->redirect($this->url->link('account/login', '', true));
            return;
        }
        
        $redirect_uri = $this->url->link('extension/social_login/module/social_login|facebook', '', true);
        $state = bin2hex(random_bytes(32));
        $this->session->data['oauth2_state'] = $state;
        
        $params = [
            'client_id' => $facebook_app_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => 'email,public_profile',
            'state' => $state
        ];
        
        $auth_url = 'https://www.facebook.com/v18.0/dialog/oauth?' . http_build_query($params);
        $this->response->redirect($auth_url);
    }
    
    private function handleFacebookCallback(): void {
        $this->load->language('extension/social_login/module/social_login');
        
        if (empty($this->request->get['state']) || $this->request->get['state'] !== ($this->session->data['oauth2_state'] ?? '')) {
            $this->session->data['error'] = $this->language->get('error_invalid_state');
            $this->response->redirect($this->url->link('account/login', '', true));
            return;
        }
        
        try {
            $facebook_app_id = $this->config->get('module_social_login_facebook_app_id');
            $facebook_app_secret = $this->config->get('module_social_login_facebook_app_secret');
            $redirect_uri = $this->url->link('extension/social_login/module/social_login|facebook', '', true);
            
            // Exchange code for access token
            $token_params = [
                'client_id' => $facebook_app_id,
                'client_secret' => $facebook_app_secret,
                'code' => $this->request->get['code'],
                'redirect_uri' => $redirect_uri
            ];
            
            $token_url = 'https://graph.facebook.com/v18.0/oauth/access_token';
            $token_response = $this->httpRequest($token_url, $token_params, 'POST');
            
            if (empty($token_response['access_token'])) {
                throw new \Exception('Failed to get access token from Facebook');
            }
            
            // Get user data
            $user_url = 'https://graph.facebook.com/v18.0/me?fields=id,name,email,first_name,last_name,picture&access_token=' . $token_response['access_token'];
            $user_data = $this->httpRequest($user_url);
            
            $social_profile = [
                'provider' => 'facebook',
                'social_id' => $user_data['id'],
                'email' => $user_data['email'] ?? '',
                'name' => $user_data['name'],
                'first_name' => $user_data['first_name'] ?? '',
                'last_name' => $user_data['last_name'] ?? '',
                'avatar' => $user_data['picture']['data']['url'] ?? '',
                'raw_data' => json_encode($user_data)
            ];
            
            $this->processSocialLogin($social_profile);
            
        } catch (\Exception $e) {
            $this->log->write('Facebook OAuth Error: ' . $e->getMessage());
            $this->session->data['error'] = $this->language->get('error_facebook_login_failed');
            $this->response->redirect($this->url->link('account/login', '', true));
        }
    }
    
    private function initiateGoogleAuth(): void {
        $google_client_id = $this->config->get('module_social_login_google_client_id');
        $google_client_secret = $this->config->get('module_social_login_google_client_secret');
        
        if (empty($google_client_id) || empty($google_client_secret)) {
            $this->load->language('extension/social_login/module/social_login');
            $this->session->data['error'] = $this->language->get('error_google_login_failed');
            $this->response->redirect($this->url->link('account/login', '', true));
            return;
        }
        
        $redirect_uri = $this->url->link('extension/social_login/module/social_login|google', '', true);
        $state = bin2hex(random_bytes(32));
        $this->session->data['oauth2_state'] = $state;
        
        $params = [
            'client_id' => $google_client_id,
            'redirect_uri' => $redirect_uri,
            'response_type' => 'code',
            'scope' => 'email profile',
            'state' => $state,
            'access_type' => 'offline'
        ];
        
        $auth_url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
        $this->response->redirect($auth_url);
    }
    
    private function handleGoogleCallback(): void {
        $this->load->language('extension/social_login/module/social_login');
        
        if (empty($this->request->get['state']) || $this->request->get['state'] !== ($this->session->data['oauth2_state'] ?? '')) {
            $this->session->data['error'] = $this->language->get('error_invalid_state');
            $this->response->redirect($this->url->link('account/login', '', true));
            return;
        }
        
        try {
            $google_client_id = $this->config->get('module_social_login_google_client_id');
            $google_client_secret = $this->config->get('module_social_login_google_client_secret');
            $redirect_uri = $this->url->link('extension/social_login/module/social_login|google', '', true);
            
            // Exchange code for access token
            $token_params = [
                'client_id' => $google_client_id,
                'client_secret' => $google_client_secret,
                'code' => $this->request->get['code'],
                'grant_type' => 'authorization_code',
                'redirect_uri' => $redirect_uri
            ];
            
            $token_url = 'https://oauth2.googleapis.com/token';
            $token_response = $this->httpRequest($token_url, $token_params, 'POST');
            
            if (empty($token_response['access_token'])) {
                throw new \Exception('Failed to get access token from Google');
            }
            
            // Get user data
            $user_url = 'https://www.googleapis.com/oauth2/v2/userinfo?access_token=' . $token_response['access_token'];
            $user_data = $this->httpRequest($user_url);
            
            $social_profile = [
                'provider' => 'google',
                'social_id' => $user_data['id'],
                'email' => $user_data['email'] ?? '',
                'name' => $user_data['name'],
                'first_name' => $user_data['given_name'] ?? '',
                'last_name' => $user_data['family_name'] ?? '',
                'avatar' => $user_data['picture'] ?? '',
                'raw_data' => json_encode($user_data)
            ];
            
            $this->processSocialLogin($social_profile);
            
        } catch (\Exception $e) {
            $this->log->write('Google OAuth Error: ' . $e->getMessage());
            $this->session->data['error'] = $this->language->get('error_google_login_failed');
            $this->response->redirect($this->url->link('account/login', '', true));
        }
    }
    
    private function initiateLineAuth(): void {
        $line_channel_id = $this->config->get('module_social_login_line_channel_id');
        $line_channel_secret = $this->config->get('module_social_login_line_channel_secret');
        
        if (empty($line_channel_id) || empty($line_channel_secret)) {
            $this->load->language('extension/social_login/module/social_login');
            $this->session->data['error'] = $this->language->get('error_line_login_failed');
            $this->response->redirect($this->url->link('account/login', '', true));
            return;
        }
        
        $redirect_uri = $this->url->link('extension/social_login/module/social_login|line', '', true);
        $state = bin2hex(random_bytes(32));
        $this->session->data['oauth2_state'] = $state;
        
        $params = [
            'response_type' => 'code',
            'client_id' => $line_channel_id,
            'redirect_uri' => $redirect_uri,
            'scope' => 'profile openid email',
            'state' => $state
        ];
        
        $auth_url = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query($params);
        $this->response->redirect($auth_url);
    }
    
    private function handleLineCallback(): void {
        $this->load->language('extension/social_login/module/social_login');
        
        if (empty($this->request->get['state']) || $this->request->get['state'] !== ($this->session->data['oauth2_state'] ?? '')) {
            $this->session->data['error'] = $this->language->get('error_invalid_state');
            $this->response->redirect($this->url->link('account/login', '', true));
            return;
        }
        
        try {
            $line_channel_id = $this->config->get('module_social_login_line_channel_id');
            $line_channel_secret = $this->config->get('module_social_login_line_channel_secret');
            $redirect_uri = $this->url->link('extension/social_login/module/social_login|line', '', true);
            
            // Exchange code for access token
            $token_params = [
                'grant_type' => 'authorization_code',
                'code' => $this->request->get['code'],
                'redirect_uri' => $redirect_uri,
                'client_id' => $line_channel_id,
                'client_secret' => $line_channel_secret
            ];
            
            $token_url = 'https://api.line.me/oauth2/v2.1/token';
            $token_response = $this->httpRequest($token_url, $token_params, 'POST');
            
            if (empty($token_response['access_token'])) {
                throw new \Exception('Failed to get access token from LINE');
            }
            
            // Verify ID token and get user data
            if (!empty($token_response['id_token'])) {
                $id_verify_params = [
                    'id_token' => $token_response['id_token'],
                    'client_id' => $line_channel_id
                ];
                
                $verify_url = 'https://api.line.me/oauth2/v2.1/verify';
                $user_data = $this->httpRequest($verify_url, $id_verify_params, 'POST');
            } else {
                // Fallback to profile API
                $user_data = $this->httpRequestWithAuth('https://api.line.me/v2/profile', $token_response['access_token']);
                $user_data['sub'] = $user_data['userId']; // Normalize field name
                $user_data['name'] = $user_data['displayName'];
            }
            
            $social_profile = [
                'provider' => 'line',
                'social_id' => $user_data['sub'] ?? $user_data['userId'],
                'email' => $user_data['email'] ?? '',
                'name' => $user_data['name'] ?? $user_data['displayName'],
                'first_name' => $user_data['name'] ?? $user_data['displayName'],
                'last_name' => '',
                'avatar' => $user_data['picture'] ?? $user_data['pictureUrl'] ?? '',
                'raw_data' => json_encode($user_data)
            ];
            
            $this->processSocialLogin($social_profile);
            
        } catch (\Exception $e) {
            $this->log->write('LINE OAuth Error: ' . $e->getMessage());
            $this->session->data['error'] = $this->language->get('error_line_login_failed');
            $this->response->redirect($this->url->link('account/login', '', true));
        }
    }
    
    private function processSocialLogin(array $social_profile): void {
        $this->load->model('extension/social_login/module/social_login');
        $this->load->language('extension/social_login/module/social_login');
        
        // Check if social login already exists
        $existing_social = $this->model_extension_social_login_module_social_login->getSocialLoginByProviderAndSocialId(
            $social_profile['provider'],
            $social_profile['social_id']
        );
        
        if ($existing_social) {
            // Update existing social login data
            $this->model_extension_social_login_module_social_login->updateSocialLogin(
                $existing_social['customer_id'],
                $social_profile['provider'],
                [
                    'social_email' => $social_profile['email'],
                    'social_name' => $social_profile['name'],
                    'social_data' => $social_profile['raw_data']
                ]
            );
            $customer_id = $existing_social['customer_id'];
        } else {
            // Check if customer exists by email
            if (!empty($social_profile['email'])) {
                $this->load->model('account/customer');
                $customer_info = $this->model_account_customer->getCustomerByEmail($social_profile['email']);
                
                if ($customer_info) {
                    // Link existing customer to social profile
                    $this->model_extension_social_login_module_social_login->addSocialLogin([
                        'customer_id' => $customer_info['customer_id'],
                        'provider' => $social_profile['provider'],
                        'social_id' => $social_profile['social_id'],
                        'social_email' => $social_profile['email'],
                        'social_name' => $social_profile['name'],
                        'social_data' => $social_profile['raw_data']
                    ]);
                    $customer_id = $customer_info['customer_id'];
                } else {
                    // Create new customer
                    $customer_id = $this->model_extension_social_login_module_social_login->createCustomerFromSocialProfile($social_profile);
                    
                    if ($customer_id) {
                        // Link new customer to social profile
                        $this->model_extension_social_login_module_social_login->addSocialLogin([
                            'customer_id' => $customer_id,
                            'provider' => $social_profile['provider'],
                            'social_id' => $social_profile['social_id'],
                            'social_email' => $social_profile['email'],
                            'social_name' => $social_profile['name'],
                            'social_data' => $social_profile['raw_data']
                        ]);
                    }
                }
            } else {
                // No email provided, create customer without email
                $customer_id = $this->model_extension_social_login_module_social_login->createCustomerFromSocialProfile($social_profile);
                
                if ($customer_id) {
                    $this->model_extension_social_login_module_social_login->addSocialLogin([
                        'customer_id' => $customer_id,
                        'provider' => $social_profile['provider'],
                        'social_id' => $social_profile['social_id'],
                        'social_email' => $social_profile['email'],
                        'social_name' => $social_profile['name'],
                        'social_data' => $social_profile['raw_data']
                    ]);
                }
            }
        }
        
        if ($customer_id) {
            // Log the customer in using OpenCart 4.0 session system
            $this->load->model('account/customer');
            $customer_info = $this->model_account_customer->getCustomer($customer_id);
            
            if ($customer_info && $customer_info['status']) {
                // 使用 OpenCart 4.0 的客戶登入方式
                $this->session->data['customer_id'] = $customer_id;
                
                // 建立客戶 token
                $this->session->data['customer_token'] = oc_token(26);
                
                // 新增客戶詳細資料到 session
                $this->session->data['customer'] = [
                    'customer_id'       => $customer_info['customer_id'],
                    'customer_group_id' => $customer_info['customer_group_id'],
                    'firstname'         => $customer_info['firstname'],
                    'lastname'          => $customer_info['lastname'],
                    'email'             => $customer_info['email'],
                    'telephone'         => $customer_info['telephone'],
                    'custom_field'      => json_decode($customer_info['custom_field'] ?? '[]', true)
                ];
                
                $this->session->data['success'] = $this->language->get('text_login_success');
                
                // Redirect to account page
                $this->response->redirect($this->url->link('account/account', 'customer_token=' . $this->session->data['customer_token'], true));
                return;
            }
        }
        
        // If we get here, something went wrong
        $this->session->data['error'] = $this->language->get('error_social_login_failed');
        $this->response->redirect($this->url->link('account/login', '', true));
    }
    
    private function httpRequest(string $url, array $params = [], string $method = 'GET'): array {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'OpenCart Social Login Extension');
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Content-Type: application/x-www-form-urlencoded'
            ]);
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new \Exception('HTTP request failed: ' . $curl_error);
        }
        
        $data = json_decode($response, true);
        
        if ($http_code >= 400) {
            throw new \Exception('HTTP Error: ' . $http_code . ' - ' . ($data['error_description'] ?? $response));
        }
        
        return $data ?? [];
    }
    
    private function httpRequestWithAuth(string $url, string $access_token): array {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $access_token
        ]);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        if ($response === false) {
            throw new \Exception('HTTP request failed: ' . $curl_error);
        }
        
        $data = json_decode($response, true);
        
        if ($http_code >= 400) {
            throw new \Exception('HTTP Error: ' . $http_code . ' - ' . ($data['error_description'] ?? $response));
        }
        
        return $data ?? [];
    }
    
    // 事件處理方法 - 在登入和註冊頁面添加社群登入按鈕
    public function addLoginButtons(string &$route, array &$data, mixed &$output): void {
        if (!$this->config->get('module_social_login_status')) {
            return;
        }
        
        // 載入社群登入模組
        $social_login_html = $this->index();
        
        if (!empty($social_login_html)) {
            // 在登入表單前添加社群登入按鈕
            $search_patterns = [
                // 尋找登入表單的開始
                '<form action="' . $this->url->link('account/login', '', true) . '"',
                '<form id="form-login"',
                '<div class="card-body">',
                '<div class="row">'
            ];
            
            foreach ($search_patterns as $pattern) {
                if (strpos($output, $pattern) !== false) {
                    $output = str_replace($pattern, $social_login_html . $pattern, $output);
                    break;
                }
            }
        }
    }
}