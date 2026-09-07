<?php
/**
 * Minimal SMTP client.
 *
 * The admin Settings → Mail tab collected host, port, username, password and
 * encryption, but nothing ever read them: mail was sent with a bare mail()
 * call and a hardcoded From address. On shared hosting that usually means the
 * message either never leaves or lands in spam, with no way to tell.
 *
 * This speaks enough SMTP to authenticate and deliver a single HTML message,
 * with no dependency to install. When SMTP is not configured it returns null
 * so the caller can fall back to mail().
 */

/**
 * Deliver one message over SMTP.
 *
 * @return array{ok:bool,error:string}|null  null when SMTP is not configured
 */
function smtpSend(string $to, string $subject, string $htmlBody, array $cfg): ?array
{
    $host = trim((string)($cfg['host'] ?? ''));
    $user = trim((string)($cfg['user'] ?? ''));
    $pass = (string)($cfg['pass'] ?? '');
    if ($host === '') return null;                 // not configured

    $port = (int)($cfg['port'] ?? 587);
    if ($port <= 0) $port = 587;
    $enc  = strtolower(trim((string)($cfg['encryption'] ?? 'tls')));
    $from      = trim((string)($cfg['from'] ?? $user));
    $fromName  = trim((string)($cfg['from_name'] ?? ''));
    $replyTo   = trim((string)($cfg['reply_to'] ?? ''));
    $timeout   = (int)($cfg['timeout'] ?? 12);

    if ($from === '') return ['ok' => false, 'error' => 'No from address configured.'];

    /* Implicit TLS on 465, STARTTLS otherwise. */
    $transport = ($enc === 'ssl' || $port === 465) ? 'ssl://' : 'tcp://';

    $ctx = stream_context_create(['ssl' => [
        'verify_peer'       => true,
        'verify_peer_name'  => true,
        'SNI_enabled'       => true,
    ]]);

    $fp = @stream_socket_client($transport . $host . ':' . $port, $errno, $errstr,
                                $timeout, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) return ['ok' => false, 'error' => "Connect failed: $errstr"];
    stream_set_timeout($fp, $timeout);

    /* Read one reply, following multi-line continuations. */
    $read = static function () use ($fp): string {
        $out = '';
        while (($line = fgets($fp, 1024)) !== false) {
            $out .= $line;
            /* "250-text" continues, "250 text" ends */
            if (strlen($line) < 4 || $line[3] !== '-') break;
        }
        return $out;
    };
    $code = static fn(string $r): int => (int)substr(trim($r), 0, 3);

    $say = static function (string $cmd) use ($fp, $read): string {
        fwrite($fp, $cmd . "\r\n");
        return $read();
    };

    $fail = static function (string $why) use ($fp): array {
        @fclose($fp);
        return ['ok' => false, 'error' => $why];
    };

    if ($code($read()) !== 220) return $fail('Server did not greet us.');

    $ehloHost = $_SERVER['SERVER_NAME'] ?? 'localhost';
    $r = $say('EHLO ' . $ehloHost);
    if ($code($r) !== 250) return $fail('EHLO refused: ' . trim($r));

    if ($transport === 'tcp://' && $enc !== 'none') {
        $r = $say('STARTTLS');
        if ($code($r) !== 220) return $fail('STARTTLS refused: ' . trim($r));
        if (!@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            return $fail('TLS negotiation failed.');
        }
        /* The handshake resets the session, so greet again. */
        $r = $say('EHLO ' . $ehloHost);
        if ($code($r) !== 250) return $fail('EHLO after TLS refused.');
    }

    if ($user !== '') {
        $r = $say('AUTH LOGIN');
        if ($code($r) !== 334) return $fail('AUTH not accepted: ' . trim($r));
        $r = $say(base64_encode($user));
        if ($code($r) !== 334) return $fail('Username rejected.');
        $r = $say(base64_encode($pass));
        if ($code($r) !== 235) return $fail('Login failed — check the username and password.');
    }

    $r = $say('MAIL FROM:<' . $from . '>');
    if ($code($r) !== 250) return $fail('Sender rejected: ' . trim($r));

    $r = $say('RCPT TO:<' . $to . '>');
    if (!in_array($code($r), [250, 251], true)) return $fail('Recipient rejected: ' . trim($r));

    $r = $say('DATA');
    if ($code($r) !== 354) return $fail('DATA refused: ' . trim($r));

    $fromHeader = $fromName !== ''
        ? sprintf('=?UTF-8?B?%s?= <%s>', base64_encode($fromName), $from)
        : $from;

    $headers = [
        'Date: ' . date('r'),
        'From: ' . $fromHeader,
        'To: ' . $to,
        'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=',
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        'Message-ID: <' . bin2hex(random_bytes(12)) . '@' . $ehloHost . '>',
    ];
    if ($replyTo !== '') $headers[] = 'Reply-To: ' . $replyTo;

    /* Base64 in 76-char lines sidesteps SMTP line-length limits and the
       leading-dot escaping rule entirely. */
    $body = chunk_split(base64_encode($htmlBody), 76, "\r\n");

    fwrite($fp, implode("\r\n", $headers) . "\r\n\r\n" . $body . "\r\n.\r\n");
    $r = $read();
    if ($code($r) !== 250) return $fail('Message rejected: ' . trim($r));

    $say('QUIT');
    @fclose($fp);
    return ['ok' => true, 'error' => ''];
}

/** The mail settings an admin filled in, in the shape smtpSend() wants. */
function smtpConfigFromSettings(): array
{
    $s = getAllSettings();
    $pick = static fn(array $keys, string $default = ''): string => (function () use ($keys, $s, $default) {
        foreach ($keys as $k) {
            $v = trim((string)($s[$k] ?? ''));
            if ($v !== '') return $v;
        }
        return $default;
    })();

    return [
        'host'       => $pick(['smtp_host']),
        'port'       => (int)($pick(['smtp_port'], '587')),
        'user'       => $pick(['smtp_user']),
        'pass'       => (string)($s['smtp_pass'] ?? ''),
        'encryption' => $pick(['smtp_encryption', 'smtp_secure'], 'tls'),
        'from'       => $pick(['smtp_from', 'mail_from', 'smtp_user', 'site_email'], 'noreply@appsgain.in'),
        'from_name'  => $pick(['smtp_from_name', 'mail_from_name', 'site_name'], 'Appsgain Technologies'),
        'reply_to'   => $pick(['site_email', 'contact_email'], ''),
    ];
}
