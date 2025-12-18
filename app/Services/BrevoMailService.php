<?php

namespace App\Services;

use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;

class BrevoMailService
{
    protected $client;
    protected $apiKey;
    protected $brevoURL;

    public function __construct()
    {
        $this->client = new Client();
        $this->apiKey = env('BREVO_API_KEY');
        $this->brevoURL = env('BREVO_URL');
    }

    public function sendPasswordResetEmail($to, $name, $resetUrl)
    {
        try {
            $response = $this->client->post($this->brevoURL, [
                'headers' => [
                    'accept' => 'application/json',
                    'api-key' => $this->apiKey,
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'sender' => [
                        'name' => 'Abadicomm System',
                        'email' => 'noreply@abadicomm.id',
                    ],
                    'to' => [
                        [
                            'email' => $to,
                            'name' => $name,
                        ],
                    ],
                    'subject' => 'Reset Password - AbadiComm',
                    'htmlContent' => $this->getEmailTemplate($name, $resetUrl),
                ],
            ]);

            return $response->getStatusCode() === 201;
        } catch (\Exception $e) {
            Log::error('Brevo email error: ' . $e->getMessage());
            return false;
        }
    }

    protected function getEmailTemplate($name, $resetUrl)
    {
        return "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background-color: #2563eb; color: white; padding: 20px; text-align: center; }
                    .content { background-color: #f9fafb; padding: 30px; }
                    .button { display: inline-block; padding: 12px 24px; background-color: #2563eb; color: white; text-decoration: none; border-radius: 6px; margin: 20px 0; }
                    .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 12px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1>AbadiComm</h1>
                    </div>
                    <div class='content'>
                        <h2>Reset Password Request</h2>
                        <p>Hello {$name},</p>
                        <p>Kami menerima permintaan untuk reset password akun Anda. Klik tombol di bawah untuk melanjutkan:</p>
                        <a href='{$resetUrl}' class='button' style='background-color: #dc2626;'>Reset Password</a>
                        <p>Link ini akan kadaluarsa dalam 60 menit.</p>
                        <p>Jika Anda tidak meminta reset password, abaikan email ini.</p>
                        <p>Atau copy link berikut ke browser Anda:</p>
                        <p style='word-break: break-all; color: #6b7280;'>{$resetUrl}</p>
                    </div>
                    <div class='footer'>
                        <p>© " . date('Y') . " AbadiComm. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
    }
}
