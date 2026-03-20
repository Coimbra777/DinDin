<?php

namespace App\Helpers;

class ApiMailHelper
{
    /**
     * Envia e-mail via API externa configurada.
     *
     * @param  string|array  $mensagem
     * @param  string|false  $destinatarios
     * @param  string|false  $fromName
     * @param  string|false  $fromMail
     * @return mixed Resposta da API ou código HTTP em caso de erro
     */
    public static function sendEmail(
        string $assunto,
        $mensagem,
        $destinatarios = false,
        $fromName = false,
        $fromMail = false
    ) {
        if (! config('mail_api.enabled')) {
            return false;
        }

        $data = self::buildBasePayload($assunto, $mensagem);
        $data['destinatarios'] = $destinatarios ?: config('mail_api.default_recipient');
        $data['from_name'] = $fromName ?: config('mail_api.from_name');
        $data['from'] = $fromMail ?: config('mail_api.from_address');

        return self::executeRequest(config('mail_api.url'), $data);
    }

    /**
     * Envia e-mail com anexo via API externa.
     */
    public static function sendEmailFile(string $assunto, $mensagem, $file = false)
    {
        if (! config('mail_api.enabled')) {
            return false;
        }

        $data = self::buildBasePayload($assunto, $mensagem);
        $data['tem_arquivo'] = 1;
        $data['destinatarios'] = config('mail_api.default_recipient');
        $data['from_name'] = config('mail_api.from_name');
        $data['from'] = config('mail_api.from_address');

        return self::executeRequest(config('mail_api.url'), $data);
    }

    /**
     * Envia arquivo para e-mail existente na API.
     */
    public static function sendFile(string $emailId, $files)
    {
        if (! config('mail_api.enabled')) {
            return false;
        }

        $baseUrl = config('mail_api.base_url') ?: rtrim(config('mail_api.url'), '/automacao/');
        $url = rtrim($baseUrl, '/')."/arquivo/{$emailId}/?authenticator=".config('mail_api.authenticator');
        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_HEADER => 0,
            CURLOPT_VERBOSE => 0,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'Mozilla/4.0 (compatible;)',
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
        ]);

        $filesArray = is_array($files) ? $files : [$files];
        $post = [];
        foreach ($filesArray as $key => $file) {
            $post[$key] = curl_file_create(
                $file->getPathName(),
                $file->getMimeType(),
                $file->getClientOriginalName()
            );
        }

        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
        $response = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return $status === 200 ? $response : $status;
    }

    /**
     * Envia contato para CRM/Marketing (opcional).
     * Configure MAIL_CRM_API_URL e MAIL_CRM_API_AUTH no .env.
     */
    public static function sendToCrm(array $tags, string $firstname, string $email): ?string
    {
        $url = config('mail_api.crm_url');
        if (! $url) {
            return null;
        }

        $data = json_encode([
            'tags' => $tags,
            'firstname' => $firstname,
            'email' => $email,
        ]);

        $curl = curl_init();
        $headers = ['Content-Type: application/json'];
        if ($auth = config('mail_api.crm_auth')) {
            $headers[] = "Authorization: Basic {$auth}";
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $data,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return $response ?: null;
    }

    /**
     * @deprecated Use sendToCrm() em vez disso
     */
    public static function sendFunil(array $tags, string $firstname, string $email): ?string
    {
        return self::sendToCrm($tags, $firstname, $email);
    }

    private static function buildBasePayload(string $assunto, $mensagem): array
    {
        $htmlMsg = is_array($mensagem)
            ? implode('<br>', array_map(fn ($k, $v) => "{$k}: {$v}", array_keys($mensagem), $mensagem))
            : $mensagem;

        return [
            'authenticator' => config('mail_api.authenticator'),
            'id_assunto' => '',
            'assunto' => $assunto,
            'port' => config('mail.port', '587'),
            'mailer' => config('mail.driver', 'smtp'),
            'host' => config('mail.host', 'smtp.gmail.com'),
            'senha_from' => config('mail_api.password'),
            'mensagem' => mb_convert_encoding($htmlMsg, 'UTF-8', 'UTF-8'),
        ];
    }

    private static function executeRequest(string $url, array $data)
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
            CURLOPT_RETURNTRANSFER => 1,
        ]);

        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return $status === 200 ? $response : $status;
    }
}
