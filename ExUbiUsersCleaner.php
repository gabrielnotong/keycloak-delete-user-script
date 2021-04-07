<?php

const DELETE_BASE_URL = "https://authentification.prod.service.2cloud.app/auth/admin/realms/%s/users/%s";
const CSV_SEPARATOR = ',';
const ERROR_LOG_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'error.txt';
const SUCCESS_LOG_FILE = __DIR__ . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'success.txt';
const ERROR_TEXT = "Error: %s, suppression de %s du royaume %s ayant l'id %s";
const SUCCESS_TEXT = "%s ayant l'id %s supprimé du royaume %s avec succès";


class ExUbiUsersCleaner
{
    private string $filePath;
    private string $tokenUrl;

    public function __construct(string $filePath, string $tokenUrl)
    {
        $this->filePath = $filePath;
        $this->tokenUrl = $tokenUrl;
    }

    public function getTokenFromKeycloak(): string {
        $curl = curl_init($this->tokenUrl);
    
        curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING => '',
        CURLOPT_MAXREDIRS => 10,
        CURLOPT_TIMEOUT => 0,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST => 'POST',
        CURLOPT_POSTFIELDS => 'client_secret=3eeb8039-f0ca-42f8-b2cf-c21fd95fe06c&client_id=admin-cli&grant_type=client_credentials',
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/x-www-form-urlencoded'
        ],
        ]);
    
        $response = curl_exec($curl);
        curl_close($curl);
    
        $result = json_decode($response, true);
    
        return $result['access_token'];
    }

    function cleanExUbiUsers(string $token): void {
        $resource = fopen($this->filePath, 'r');
        
        while($line = fgets($resource)) {
            $userData = explode(CSV_SEPARATOR, $line);
            $realName = $userData[0];
            $userId = $userData[5];
            $deleteUrl = sprintf(DELETE_BASE_URL, $realName, $userId);

            try {
                $this->delete($deleteUrl, $token);
                $success = sprintf(SUCCESS_TEXT, $userData[1], $userId, $realName);
                $this->log(SUCCESS_LOG_FILE, $success);
            } catch(Exception $e) {
                $error = sprintf(ERROR_TEXT, $e->getMessage(), $userData[1], $realName, $userId);
                $this->log(ERROR_LOG_FILE, $error);
            }
        }
    }
    
    private function delete(string $url, string $token): string {

        $curl = curl_init($url);
    
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FAILONERROR => true,
            CURLOPT_TIMEOUT => 180,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token"
            ],
        ]);
    
        $response = curl_exec($curl);
        curl_close($curl);
    
       return $response;
    }

    private function log(string $filePath, string $message) {
        file_put_contents($filePath, $message . PHP_EOL, FILE_APPEND);
    }
}