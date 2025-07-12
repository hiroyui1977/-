<?php
namespace SocialLogin;

class AccountLinker {
    private $registry;
    
    public function __construct($registry) {
        $this->registry = $registry;
    }
    
    public function linkOrCreateAccount(array $social_profile): int {
        $this->registry->get('load')->model('extension/social_login/module/social_login');
        $this->registry->get('load')->model('account/customer');
        
        $model = $this->registry->get('model_extension_social_login_module_social_login');
        
        // Check if social login already exists
        $existing_social = $model->getSocialLoginByProviderAndSocialId(
            $social_profile['provider'],
            $social_profile['social_id']
        );
        
        if ($existing_social) {
            // Update existing social login data
            $model->updateSocialLogin(
                $existing_social['customer_id'],
                $social_profile['provider'],
                [
                    'social_email' => $social_profile['email'],
                    'social_name' => $social_profile['name'],
                    'social_data' => $social_profile['raw_data']
                ]
            );
            return $existing_social['customer_id'];
        }
        
        // Check if customer exists by email
        if (!empty($social_profile['email'])) {
            $customer_info = $this->registry->get('model_account_customer')->getCustomerByEmail($social_profile['email']);
            
            if ($customer_info) {
                // Link existing customer to social profile
                $model->addSocialLogin([
                    'customer_id' => $customer_info['customer_id'],
                    'provider' => $social_profile['provider'],
                    'social_id' => $social_profile['social_id'],
                    'social_email' => $social_profile['email'],
                    'social_name' => $social_profile['name'],
                    'social_data' => $social_profile['raw_data']
                ]);
                return $customer_info['customer_id'];
            }
        }
        
        // Create new customer
        $customer_id = $model->createCustomerFromSocialProfile($social_profile);
        
        if ($customer_id) {
            // Link new customer to social profile
            $model->addSocialLogin([
                'customer_id' => $customer_id,
                'provider' => $social_profile['provider'],
                'social_id' => $social_profile['social_id'],
                'social_email' => $social_profile['email'],
                'social_name' => $social_profile['name'],
                'social_data' => $social_profile['raw_data']
            ]);
        }
        
        return $customer_id;
    }
}
?>