<?php
namespace Opencart\Admin\Model\Extension\SocialLogin\Module;

class SocialLogin extends \Opencart\System\Engine\Model {
    
    public function install(): void {
        $this->db->query("
            CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "social_login_customer` (
                `customer_id` int(11) NOT NULL,
                `provider` varchar(50) NOT NULL,
                `social_id` varchar(255) NOT NULL,
                `social_email` varchar(255) DEFAULT NULL,
                `social_name` varchar(255) DEFAULT NULL,
                `social_data` text DEFAULT NULL,
                `date_added` datetime NOT NULL,
                `date_modified` datetime NOT NULL,
                PRIMARY KEY (`customer_id`, `provider`),
                KEY `social_id` (`social_id`),
                KEY `social_email` (`social_email`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    }
    
    public function uninstall(): void {
        $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "social_login_customer`");
    }
    
    // 取得所有社群登入記錄
    public function getSocialLogins(array $data = []): array {
        $sql = "
            SELECT slc.*, 
                   CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                   c.email as customer_email,
                   c.status as customer_status
            FROM `" . DB_PREFIX . "social_login_customer` slc
            LEFT JOIN `" . DB_PREFIX . "customer` c ON (slc.customer_id = c.customer_id)
            WHERE 1=1
        ";
        
        if (!empty($data['filter_provider'])) {
            $sql .= " AND slc.provider = '" . $this->db->escape($data['filter_provider']) . "'";
        }
        
        if (!empty($data['filter_customer_name'])) {
            $sql .= " AND CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_customer_name']) . "%'";
        }
        
        if (!empty($data['filter_social_email'])) {
            $sql .= " AND slc.social_email LIKE '%" . $this->db->escape($data['filter_social_email']) . "%'";
        }
        
        $sort_data = [
            'customer_name',
            'slc.provider',
            'slc.social_email',
            'slc.date_added'
        ];
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY slc.date_added";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
    
    // 取得社群登入記錄總數
    public function getTotalSocialLogins(array $data = []): int {
        $sql = "
            SELECT COUNT(*) AS total
            FROM `" . DB_PREFIX . "social_login_customer` slc
            LEFT JOIN `" . DB_PREFIX . "customer` c ON (slc.customer_id = c.customer_id)
            WHERE 1=1
        ";
        
        if (!empty($data['filter_provider'])) {
            $sql .= " AND slc.provider = '" . $this->db->escape($data['filter_provider']) . "'";
        }
        
        if (!empty($data['filter_customer_name'])) {
            $sql .= " AND CONCAT(c.firstname, ' ', c.lastname) LIKE '%" . $this->db->escape($data['filter_customer_name']) . "%'";
        }
        
        if (!empty($data['filter_social_email'])) {
            $sql .= " AND slc.social_email LIKE '%" . $this->db->escape($data['filter_social_email']) . "%'";
        }
        
        $query = $this->db->query($sql);
        
        return (int)$query->row['total'];
    }
    
    // 取得社群登入統計
    public function getSocialLoginStats(): array {
        $query = $this->db->query("
            SELECT provider, COUNT(*) as count 
            FROM `" . DB_PREFIX . "social_login_customer`
            GROUP BY provider
            ORDER BY count DESC
        ");
        
        return $query->rows;
    }
    
    // 取得特定客戶的社群登入記錄
    public function getSocialLoginsByCustomerId(int $customer_id): array {
        $query = $this->db->query("
            SELECT * FROM `" . DB_PREFIX . "social_login_customer` 
            WHERE customer_id = '" . (int)$customer_id . "'
            ORDER BY date_added DESC
        ");
        
        return $query->rows;
    }
    
    // 刪除社群登入記錄
    public function deleteSocialLogin(int $customer_id, string $provider): void {
        $this->db->query("
            DELETE FROM `" . DB_PREFIX . "social_login_customer` 
            WHERE customer_id = '" . (int)$customer_id . "' 
            AND provider = '" . $this->db->escape($provider) . "'
        ");
    }
    
    // 取得總社群登入用戶數
    public function getTotalSocialCustomers(): int {
        $query = $this->db->query("
            SELECT COUNT(DISTINCT customer_id) as total 
            FROM `" . DB_PREFIX . "social_login_customer`
        ");
        
        return (int)$query->row['total'];
    }
    
    // 取得月度新增社群登入統計
    public function getMonthlySocialLoginStats(int $year = null): array {
        if (!$year) {
            $year = date('Y');
        }
        
        $query = $this->db->query("
            SELECT 
                MONTH(date_added) as month,
                provider,
                COUNT(*) as count
            FROM `" . DB_PREFIX . "social_login_customer`
            WHERE YEAR(date_added) = '" . (int)$year . "'
            GROUP BY MONTH(date_added), provider
            ORDER BY month ASC, provider ASC
        ");
        
        return $query->rows;
    }
    
    // 檢查是否有孤立的社群登入記錄（客戶已被刪除）
    public function getOrphanedSocialLogins(): array {
        $query = $this->db->query("
            SELECT slc.*
            FROM `" . DB_PREFIX . "social_login_customer` slc
            LEFT JOIN `" . DB_PREFIX . "customer` c ON (slc.customer_id = c.customer_id)
            WHERE c.customer_id IS NULL
        ");
        
        return $query->rows;
    }
    
    // 清理孤立的社群登入記錄
    public function cleanOrphanedSocialLogins(): int {
        $this->db->query("
            DELETE slc FROM `" . DB_PREFIX . "social_login_customer` slc
            LEFT JOIN `" . DB_PREFIX . "customer` c ON (slc.customer_id = c.customer_id)
            WHERE c.customer_id IS NULL
        ");
        
        return $this->db->countAffected();
    }
    
    // 取得特定社群平台的用戶列表
    public function getCustomersByProvider(string $provider, array $data = []): array {
        $sql = "
            SELECT slc.*, 
                   CONCAT(c.firstname, ' ', c.lastname) as customer_name,
                   c.email as customer_email,
                   c.status as customer_status,
                   c.date_added as customer_date_added
            FROM `" . DB_PREFIX . "social_login_customer` slc
            LEFT JOIN `" . DB_PREFIX . "customer` c ON (slc.customer_id = c.customer_id)
            WHERE slc.provider = '" . $this->db->escape($provider) . "'
        ";
        
        $sort_data = [
            'customer_name',
            'slc.social_email',
            'slc.date_added',
            'c.date_added'
        ];
        
        if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
            $sql .= " ORDER BY " . $data['sort'];
        } else {
            $sql .= " ORDER BY slc.date_added";
        }
        
        if (isset($data['order']) && ($data['order'] == 'DESC')) {
            $sql .= " DESC";
        } else {
            $sql .= " ASC";
        }
        
        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }
            
            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }
            
            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }
        
        $query = $this->db->query($sql);
        
        return $query->rows;
    }
}