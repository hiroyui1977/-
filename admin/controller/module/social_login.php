<?php
// ==========================================
// 最終修正版控制器
// 檔案位置：admin/controller/extension/social_login/module/social_login.php
// ==========================================

namespace Opencart\Admin\Controller\Extension\SocialLogin\Module;

class SocialLogin extends \Opencart\System\Engine\Controller {
    
    private $error = array();
    
    public function index(): void {
        if (!$this->user->hasPermission('access', 'extension/social_login/module/social_login')) {
            $this->response->redirect($this->url->link('error/permission', 'user_token=' . $this->session->data['user_token'], true));
            return;
        }
        
        $this->load->language('extension/social_login/module/social_login');
        $this->document->setTitle($this->language->get('heading_title'));
        
        $data['breadcrumbs'] = array();
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'])
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_extensions'),
            'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module')
        );
        
        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/social_login/module/social_login', 'user_token=' . $this->session->data['user_token'])
        );
        
        $data['save'] = $this->url->link('extension/social_login/module/social_login|save', 'user_token=' . $this->session->data['user_token']);
        $data['back'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module');
        
        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['text_enabled'] = $this->language->get('text_enabled');
        $data['text_disabled'] = $this->language->get('text_disabled');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_back'] = $this->language->get('button_back');
        
        $data['tab_general'] = $this->language->get('tab_general');
        $data['tab_facebook'] = $this->language->get('tab_facebook');
        $data['tab_google'] = $this->language->get('tab_google');
        $data['tab_line'] = $this->language->get('tab_line');
        
        $data['entry_status'] = $this->language->get('entry_status');
        $data['entry_facebook_app_id'] = $this->language->get('entry_facebook_app_id');
        $data['entry_facebook_app_secret'] = $this->language->get('entry_facebook_app_secret');
        $data['entry_google_client_id'] = $this->language->get('entry_google_client_id');
        $data['entry_google_client_secret'] = $this->language->get('entry_google_client_secret');
        $data['entry_line_channel_id'] = $this->language->get('entry_line_channel_id');
        $data['entry_line_channel_secret'] = $this->language->get('entry_line_channel_secret');
        
        // 配置項目列表
        $config_items = array(
            'status',
            'facebook_app_id',
            'facebook_app_secret',
            'google_client_id',
            'google_client_secret',
            'line_channel_id',
            'line_channel_secret'
        );
        
        // 統一處理配置數據
        foreach ($config_items as $item) {
            if (isset($this->request->post['module_social_login_' . $item])) {
                $data['module_social_login_' . $item] = $this->request->post['module_social_login_' . $item];
            } else {
                $data['module_social_login_' . $item] = $this->config->get('module_social_login_' . $item);
            }
        }
        
        $data['header'] = $this->load->controller('common/header');
        $data['column_left'] = $this->load->controller('common/column_left');
        $data['footer'] = $this->load->controller('common/footer');
        
        // 🔧 關鍵修正：正確的模板路徑（參考LINE模組：extension/linlihsin/module/line_login）
        $this->response->setOutput($this->load->view('extension/social_login/module/social_login', $data));
    }
    
    public function save(): void {
        $this->load->language('extension/social_login/module/social_login');
        
        $json = array();
        
        if (!$this->user->hasPermission('modify', 'extension/social_login/module/social_login')) {
            $json['error'] = $this->language->get('error_permission');
        }
        
        if (!$json) {
            $this->load->model('setting/setting');
            $this->model_setting_setting->editSetting('module_social_login', $this->request->post);
            $json['success'] = $this->language->get('text_success');
        }
        
        $this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
    }
    
    public function install(): void {
        if (!$this->user->hasPermission('modify', 'extension/social_login/module/social_login')) {
            return;
        }
        
        $this->load->model('extension/social_login/module/social_login');
        $this->model_extension_social_login_module_social_login->install();
        
        // 添加事件處理器
        $this->load->model('setting/event');
        
        $events = array(
            array(
                'code' => 'social_login_header',
                'description' => '在登入頁面添加社群登入按鈕',
                'trigger' => 'catalog/view/account/login/after',
                'action' => 'extension/social_login/module/social_login.addLoginButtons',
                'status' => 1,
                'sort_order' => 1
            ),
            array(
                'code' => 'social_login_register',
                'description' => '在註冊頁面添加社群登入按鈕',
                'trigger' => 'catalog/view/account/register/after',
                'action' => 'extension/social_login/module/social_login.addLoginButtons',
                'status' => 1,
                'sort_order' => 1
            )
        );
        
        foreach ($events as $event) {
            $data = array(
                'code' => $event['code'],
                'description' => $event['description'],
                'trigger' => $event['trigger'],
                'action' => $event['action'],
                'status' => $event['status'],
                'sort_order' => $event['sort_order']
            );
            
            $this->model_setting_event->addEvent($data);
        }
    }
    
    public function uninstall(): void {
        if (!$this->user->hasPermission('modify', 'extension/social_login/module/social_login')) {
            return;
        }
        
        $this->load->model('extension/social_login/module/social_login');
        $this->model_extension_social_login_module_social_login->uninstall();
        
        // 移除事件處理器
        $this->load->model('setting/event');
        
        $this->model_setting_event->deleteEventByCode('social_login_header');
        $this->model_setting_event->deleteEventByCode('social_login_register');
    }
    
    protected function validate(): bool {
        if (!$this->user->hasPermission('modify', 'extension/social_login/module/social_login')) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        
        return !$this->error;
    }
}