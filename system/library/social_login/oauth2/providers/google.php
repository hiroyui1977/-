<?php
namespace SocialLogin\OAuth2\Providers;

use SocialLogin\OAuth2\OAuth2Client;
use SocialLogin\OAuth2\ResourceOwner;

class Google extends OAuth2Client {
    
    protected $base_url = 'https://accounts.google.com/o/oauth2/v2/auth';
    protected $token_url = 'https://oauth2.googleapis.com/token';
    protected $api_url = 'https://www.googleapis.com/oauth2/v2/userinfo';
    
    public function getAuthorizationUrl(array $options = []): string {
        $params = [
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'response_type' => 'code',
            'scope' => implode(' ', $options['scope'] ?? ['email', 'profile']),
            'state' => $options['state'] ?? $this->generateState(),
            'access_type' => $options['access_type'] ?? 'online'
        ];
        
        return $this->base_url . '?' . http_build_query($params);
    }
    
    public function getAccessToken(string $grant_type, array $params = []): array {
        $token_params = [
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret,
            'code' => $params['code'],
            'grant_type' => 'authorization_code',
            'redirect_uri' => $this->redirect_uri
        ];
        
        return $this->httpRequest($this->token_url, $token_params, 'POST');
    }
    
    public function getResourceOwner(array $token): ResourceOwner {
        $url = $this->api_url . '?access_token=' . $token['access_token'];
        
        $data = $this->httpRequest($url);
        
        return new ResourceOwner([
            'id' => $data['id'],
            'email' => $data['email'] ?? '',
            'name' => $data['name'],
            'first_name' => $data['given_name'] ?? '',
            'last_name' => $data['family_name'] ?? '',
            'avatar' => $data['picture'] ?? ''
        ]);
    }
}
?>