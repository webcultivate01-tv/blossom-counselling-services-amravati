<?php

/**
 * Minimal SMTP client (implicit TLS on 465, or STARTTLS on 587) for Gmail.
 * Credentials come from SMTP_* values in .env.
 */
class Mailer
{
    /** @var resource */
    private $socket;

    public static function isConfigured(): bool
    {
        return SMTP_USER !== '' && SMTP_PASS !== ''
            && SMTP_USER !== 'your-gmail@gmail.com' && SMTP_PASS !== 'your-16-char-app-password';
    }

    public static function send(string $to, string $subject, string $html, string $text): void
    {
        (new self())->deliver($to, $subject, $html, $text);
    }

    private function deliver(string $to, string $subject, string $html, string $text): void
    {
        if (!self::isConfigured()) {
            throw new RuntimeException('SMTP is not configured. Set SMTP_USER and SMTP_PASS in admin/.env');
        }

        $implicit = SMTP_PORT === 465;
        $this->socket = @stream_socket_client(
            ($implicit ? 'ssl://' : 'tcp://') . SMTP_HOST . ':' . SMTP_PORT,
            $errno,
            $errstr,
            15
        );
        if (!$this->socket) {
            throw new RuntimeException("SMTP connect failed: $errstr ($errno)");
        }
        stream_set_timeout($this->socket, 15);

        $this->expect(220);
        $this->cmd('EHLO localhost', 250);

        if (!$implicit) {
            $this->cmd('STARTTLS', 220);
            if (!stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new RuntimeException('SMTP STARTTLS failed');
            }
            $this->cmd('EHLO localhost', 250);
        }

        $this->cmd('AUTH LOGIN', 334);
        $this->cmd(base64_encode(SMTP_USER), 334);
        $this->cmd(base64_encode(SMTP_PASS), 235);
        $this->cmd('MAIL FROM:<' . SMTP_USER . '>', 250);
        $this->cmd('RCPT TO:<' . $to . '>', [250, 251]);
        $this->cmd('DATA', 354);

        $boundary = 'b' . bin2hex(random_bytes(8));
        $headers = [
            'Date: ' . date('r'),
            'From: =?UTF-8?B?' . base64_encode(SMTP_FROM_NAME) . '?= <' . SMTP_USER . '>',
            'To: <' . $to . '>',
            'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"$boundary\"",
        ];
        $body = "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text))
            . "--$boundary\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html))
            . "--$boundary--\r\n";

        fwrite($this->socket, implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n");
        $this->expect(250);

        $this->cmd('QUIT', 221);
        fclose($this->socket);
    }

    private function cmd(string $line, int|array $expect): void
    {
        fwrite($this->socket, $line . "\r\n");
        $this->expect($expect);
    }

    private function expect(int|array $codes): void
    {
        $response = '';
        while (($line = fgets($this->socket, 515)) !== false) {
            $response .= $line;
            if (strlen($line) < 4 || $line[3] === ' ') {
                break;
            }
        }

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, (array) $codes, true)) {
            throw new RuntimeException('SMTP error: ' . trim($response));
        }
    }
}
