<?php
namespace Opencart\Catalog\Model\Extension\SocialLogin\Module;

class SocialLogin extends \Opencart\System\Engine\Model {
    
    public function getSocialLoginByCustomerId(int $customer_id): array {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "social_login_customer` 
            WHERE customer_id = '" . (int)$customer_id . "'
            ORDER BY date_added DESC
        ");
        
        return $query->rows;
    }
    
    public function getSocialLoginByProviderAndSocialId(string $provider, string $social_id): array {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "social_login_customer` 
            WHERE provider = '" . $this->db->escape($provider) . "' 
            AND social_id = '" . $this->db->escape($social_id) . "'
        ");
        
        return $query->row ?? [];
    }
    
    public function getSocialLoginByEmail(string $email): array {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "social_login_customer` 
            WHERE social_email = '" . $this->db->escape($email) . "'
        ");
        
        return $query->rows;
    }
    
    public function addSocialLogin(array $data): void {
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "social_login_customer` 
            SET customer_id = '" . (int)$data['customer_id'] . "', 
                provider = '" . $this->db->escape($data['provider']) . "', 
                social_id = '" . $this->db->escape($data['social_id']) . "', 
                social_email = '" . $this->db->escape($data['social_email']) . "', 
                social_name = '" . $this->db->escape($data['social_name']) . "', 
                social_data = '" . $this->db->escape($data['social_data']) . "', 
                date_added = NOW(), 
                date_modified = NOW()
        ");
    }
    
    public function updateSocialLogin(int $customer_id, string $provider, array $data): void {
        $this->db->query("
            UPDATE `" . DB_PREFIX . "social_login_customer` 
            SET social_email = '" . $this->db->escape($data['social_email']) . "', 
                social_name = '" . $this->db->escape($data['social_name']) . "', 
                social_data = '" . $this->db->escape($data['social_data']) . "', 
                date_modified = NOW() 
            WHERE customer_id = '" . (int)$customer_id . "' 
            AND provider = '" . $this->db->escape($provider) . "'
        ");
    }
    
    public function deleteSocialLogin(int $customer_id, string $provider): void {
        $this->db->query("
            DELETE FROM `" . DB_PREFIX . "social_login_customer` 
            WHERE customer_id = '" . (int)$customer_id . "' 
            AND provider = '" . $this->db->escape($provider) . "'
        ");
    }
    
    public function createCustomerFromSocialProfile(array $social_profile): int {
        // 產生隨機密碼
        $password = $this->generateRandomPassword();
        
        // 處理 email - 如果沒有 email，產生一個假的
        $email = $social_profile['email'];
        if (empty($email)) {
            $email = 'noemail_' . $social_profile['social_id'] . '@' . $social_profile['provider'] . '.social';
        }
        
        // 檢查 email 是否已存在
        $existing_customer = $this->checkEmailExists($email);
        if ($existing_customer) {
            // 如果 email 已存在，添加隨機數字
            $email = 'social_' . time() . '_' . rand(1000, 9999) . '@' . $social_profile['provider'] . '.social';
        }
        
        $customer_data = [
            'customer_group_id' => $this->config->get('config_customer_group_id') ?: 1,
            'firstname' => $this->sanitizeName($social_profile['first_name'] ?: $social_profile['name']),
            'lastname' => $this->sanitizeName($social_profile['last_name'] ?: ''),
            'email' => $email,
            'telephone' => '',
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'newsletter' => 0,
            'status' => 1,
            'safe' => 1,
            'code' => ''
        ];
        
        // 建立客戶記錄
        $this->db->query("
            INSERT INTO `" . DB_PREFIX . "customer` 
            SET customer_group_id = '" . (int)$customer_data['customer_group_id'] . "',
                firstname = '" . $this->db->escape($customer_data['firstname']) . "',
                lastname = '" . $this->db->escape($customer_data['lastname']) . "',
                email = '" . $this->db->escape($customer_data['email']) . "',
                telephone = '" . $this->db->escape($customer_data['telephone']) . "',
                password = '" . $this->db->escape($customer_data['password']) . "',
                newsletter = '" . (int)$customer_data['newsletter'] . "',
                status = '" . (int)$customer_data['status'] . "',
                safe = '" . (int)$customer_data['safe'] . "',
                code = '" . $this->db->escape($customer_data['code']) . "',
                date_added = NOW()
        ");
        
        $customer_id = $this->db->getLastId();
        
        // 如果有頭像 URL，儲存頭像資訊
        if (!empty($social_profile['avatar']) && $customer_id) {
            $this->saveCustomerAvatar($customer_id, $social_profile['avatar'], $social_profile['provider']);
        }
        
        return $customer_id;
    }
    
    private function checkEmailExists(string $email): bool {
        $query = $this->db->query("
            SELECT customer_id FROM `" . DB_PREFIX . "customer` 
            WHERE email = '" . $this->db->escape($email) . "'
        ");
        
        return $query->num_rows > 0;
    }
    
    private function sanitizeName(string $name): string {
        // 移除特殊字符，只保留字母、數字、空格和基本標點
        $name = preg_replace('/[^\p{L}\p{N}\s\-\'\.]/u', '', $name);
        $name = trim($name);
        
        // 如果名字太短或為空，設定預設值
        if (mb_strlen($name) < 1) {
            $name = 'Social User';
        }
        
        // 限制長度
        if (mb_strlen($name) > 32) {
            $name = mb_substr($name, 0, 32);
        }
        
        return $name;
    }
    
    private function saveCustomerAvatar(int $customer_id, string $avatar_url, string $provider): void {
        try {
            // 建立目錄
            $avatar_dir = DIR_IMAGE . 'customer_avatars/';
            if (!is_dir($avatar_dir)) {
                mkdir($avatar_dir, 0777, true);
            }
            
            // 取得檔案擴展名
            $path_info = pathinfo(parse_url($avatar_url, PHP_URL_PATH));
            $extension = $path_info['extension'] ?? 'jpg';
            
            // 設定檔案名稱
            $filename = $provider . '_' . $customer_id . '_' . time() . '.' . $extension;
            $filepath = $avatar_dir . $filename;
            
            // 下載並儲存頭像
            $context = stream_context_create([
                'http' => [
                    'timeout' => 10,
                    'user_agent' => 'OpenCart Social Login',
                    'follow_location' => true,
                    'max_redirects' => 3
                ]
            ]);
            
            $avatar_data = file_get_contents($avatar_url, false, $context);
            if ($avatar_data && file_put_contents($filepath, $avatar_data)) {
                // 儲存頭像路徑到客戶資料
                $custom_field = json_decode($this->getCustomerCustomField($customer_id) ?? '{}', true);
                $custom_field['social_avatar'] = 'customer_avatars/' . $filename;
                
                $this->db->query("
                    UPDATE `" . DB_PREFIX . "customer` 
                    SET custom_field = '" . $this->db->escape(json_encode($custom_field)) . "'
                    WHERE customer_id = '" . (int)$customer_id . "'
                ");
            }
        } catch (\Exception $e) {
            // 頭像下載失敗不影響註冊流程，只記錄錯誤
            error_log('Social Login Avatar Error: ' . $e->getMessage());
        }
    }
    
    private function getCustomerCustomField(int $customer_id): ?string {
        $query = $this->db->query("
            SELECT custom_field FROM `" . DB_PREFIX . "customer` 
            WHERE customer_id = '" . (int)$customer_id . "'
        ");
        
        return $query->row['custom_field'] ?? null;
    }
    
    private function generateRandomPassword(): string {
        return bin2hex(random_bytes(16));
    }
    
    // 取得客戶的社群登入統計
    public function getCustomerSocialStats(int $customer_id): array {
        $query = $this->db->query("
            SELECT provider, COUNT(*) as count 
            FROM `" . DB_PREFIX . "social_login_customer` 
            WHERE customer_id = '" . (int)$customer_id . "'
            GROUP BY provider
        ");
        
        return $query->rows;
    }
    
    // 檢查社群 ID 是否已被使用
    public function isSocialIdUsed(string $provider, string $social_id): bool {
        $query = $this->db->query("
            SELECT customer_id FROM `" . DB_PREFIX . "social_login_customer` 
            WHERE provider = '" . $this->db->escape($provider) . "' 
            AND social_id = '" . $this->db->escape($social_id) . "'
        ");
        
        return $query->num_rows > 0;
    }
    
    // 取得客戶的社群頭像
    public function getCustomerSocialAvatar(int $customer_id): ?string {
        $custom_field = $this->getCustomerCustomField($customer_id);
        if ($custom_field) {
            $data = json_decode($custom_field, true);
            return $data['social_avatar'] ?? null;
        }
        return null;
    }
    
    // 連結現有客戶到社群帳號
    public function linkCustomerToSocial(int $customer_id, array $social_profile): bool {
        // 檢查是否已經連結
        $existing = $this->getSocialLoginByProviderAndSocialId(
            $social_profile['provider'], 
            $social_profile['social_id']
        );
        
        if ($existing) {
            return false; // 已經被其他帳號連結
        }
        
        // 檢查客戶是否存在
        $this->load->model('account/customer');
        $customer_info = $this->model_account_customer->getCustomer($customer_id);
        
        if (!$customer_info) {
            return false; // 客戶不存在
        }
        
        // 新增社群連結
        $this->addSocialLogin([
            'customer_id' => $customer_id,
            'provider' => $social_profile['provider'],
            'social_id' => $social_profile['social_id'],
            'social_email' => $social_profile['email'],
            'social_name' => $social_profile['name'],
            'social_data' => $social_profile['raw_data']
        ]);
        
        return true;
    }
    
    // 解除客戶與社群帳號的連結
    public function unlinkCustomerFromSocial(int $customer_id, string $provider): bool {
        // 檢查是否存在連結
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "social_login_customer` 
            WHERE customer_id = '" . (int)$customer_id . "' 
            AND provider = '" . $this->db->escape($provider) . "'
        ");
        
        if ($query->num_rows > 0) {
            $this->deleteSocialLogin($customer_id, $provider);
            return true;
        }
        
        return false;
    }
    
    // 更新社群登入的最後使用時間
    public function updateLastLoginTime(int $customer_id, string $provider): void {
        $this->db->query("
            UPDATE `" . DB_PREFIX . "social_login_customer` 
            SET date_modified = NOW() 
            WHERE customer_id = '" . (int)$customer_id . "' 
            AND provider = '" . $this->db->escape($provider) . "'
        ");
    }
    
    // 取得社群登入方式的數量（用於安全檢查）
    public function getSocialLoginCount(int $customer_id): int {
        $query = $this->db->query("
            SELECT COUNT(*) as total 
            FROM `" . DB_PREFIX . "social_login_customer` 
            WHERE customer_id = '" . (int)$customer_id . "'
        ");
        
        return (int)$query->row['total'];
    }
}