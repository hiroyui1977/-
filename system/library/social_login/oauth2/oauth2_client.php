<?php
namespace SocialLogin\OAuth2;

abstract class OAuth2Client {
    protected $client_id;
    protected $client_secret;
    protected $redirect_uri;
    protected $scope;
    protected $state;
    
    public function __construct(array $config) {
        $this->client_id = $config['client_id'];
        $this->client_secret = $config['client_secret'];
        $this->redirect_uri = $config['redirect_uri'];
        $this->scope = $config['scope'] ?? [];
        $this->state = $config['state'] ?? '';
    }
    
    abstract public function getAuthorizationUrl(array $options = []): string;
    abstract public function getAccessToken(string $grant_type, array $params = []): array;
    abstract public function getResourceOwner(array $token): ResourceOwner;
    
    protected function httpRequest(string $url, array $params = [], string $method = 'GET'): array {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_USERAGENT, 'OpenCart Social Login Extension');
        
        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response === false) {
            throw new \Exception('HTTP request failed');
        }
        
        $data = json_decode($response, true);
        
        if ($http_code >= 400) {
            throw new \Exception('HTTP Error: ' . $http_code . ' - ' . ($data['error_description'] ?? 'Unknown error'));
        }
        
        return $data;
    }
    
    protected function generateState(): string {
        return bin2hex(random_bytes(32));
    }
}

class ResourceOwner {
    protected $data;
    
    public function __construct(array $data) {
        $this->data = $data;
    }
    
    public function getId(): string {
        return $this->data['id'] ?? '';
    }
    
    public function getEmail(): string {
        return $this->data['email'] ?? '';
    }
    
    public function getName(): string {
        return $this->data['name'] ?? '';
    }
    
    public function getFirstName(): string {
        return $this->data['first_name'] ?? '';
    }
    
    public function getLastName(): string {
        return $this->data['last_name'] ?? '';
    }
    
    public function getAvatar(): string {
        return $this->data['avatar'] ?? '';
    }
    
    public function toArray(): array {
        return $this->data;
    }
}
?>