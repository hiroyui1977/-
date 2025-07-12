<?php
namespace SocialLogin\OAuth2\Providers;

use SocialLogin\OAuth2\OAuth2Client;
use SocialLogin\OAuth2\ResourceOwner;

class Line extends OAuth2Client {
    
    protected $base_url = 'https://access.line.me/oauth2/v2.1/authorize';
    protected $token_url = 'https://api.line.me/oauth2/v2.1/token';
    protected $api_url = 'https://api.line.me/v2/profile';
    
    public function getAuthorizationUrl(array $options = []): string {
        $params = [
            'response_type' => 'code',
            'client_id' => $this->client_id,
            'redirect_uri' => $this->redirect_uri,
            'scope' => implode(' ', $options['scope'] ?? ['profile', 'openid']),
            'state' => $options['state'] ?? $this->generateState()
        ];
        
        return $this->base_url . '?' . http_build_query($params);
    }
    
    public function getAccessToken(string $grant_type, array $params = []): array {
        $token_params = [
            'grant_type' => 'authorization_code',
            'code' => $params['code'],
            'redirect_uri' => $this->redirect_uri,
            'client_id' => $this->client_id,
            'client_secret' => $this->client_secret
        ];
        
        return $this->httpRequest($this->token_url, $token_params, 'POST');
    }
    
    public function getResourceOwner(array $token): ResourceOwner {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $token['access_token']
        ]);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        
        return new ResourceOwner([
            'id' => $data['userId'],
            'email' => '',
            'name' => $data['displayName'],
            'first_name' => $data['displayName'],
            'last_name' => '',
            'avatar' => $data['pictureUrl'] ?? ''
        ]);
    }
}