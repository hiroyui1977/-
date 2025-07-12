<?php
namespace SocialLogin\OAuth2\Providers;

use SocialLogin\OAuth2\OAuth2Client;
use SocialLogin\OAuth2\ResourceOwner;

class Facebook extends OAuth2Client {
    
    protected $base_url = 'https://www.facebook.com/v13.0/dialog/oauth';
    protected $token_url = 'https://graph.facebook.com/v13.0/oauth/access_token';
    protected $api_url = 'https://graph.facebook.com/v13.0/me';
    
    public function getAuthorizationUrl(array $options = []): string {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => implode(',', $options['scope'] ?? ['email', 'public_profile']),
            'state' => $options['state'] ?? $this->generateState()
        ];
        
        return $this->base_url . '?' . http_build_query($params);
    }
    
    public function getAccessToken(string $grant_type, array $params = []): array {
        $token_params = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $params['code'],
            'redirect_uri' => $this->redirect_uri
        ];
        
        return $this->httpRequest($this->token_url, $token_params, 'POST');
    }
    
    public function getResourceOwner(array $token): ResourceOwner {
        $url = $this->api_url . '?fields=id,name,email,first_name,last_name,picture&access_token=' . $token['access_token'];
        
        $data = $this->httpRequest($url);
        
        return new ResourceOwner([
            'id' => $data['id'],
            'email' => $data['email'] ?? '',
            'name' => $data['name'],
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'avatar' => $data['picture']['data']['url'] ?? ''
        ]);
    }
}
?>