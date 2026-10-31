<?php

namespace App\Support;

/**
 * Uji sambungan SMTP tahap demi tahap TANPA kredensial dan tanpa library tambahan:
 * DNS -> TCP -> sapaan server (banner) -> EHLO -> STARTTLS/TLS.
 */
class SmtpProbe
{
    /** @return array<int, array{step: string, ok: bool, detail: string}> */
    public static function run(string $host, int $port, int $timeout = 8): array
    {
        $steps = [];
        $add = function (string $step, bool $ok, string $detail) use (&$steps) {
            $steps[] = ['step' => $step, 'ok' => $ok, 'detail' => $detail];

            return $ok;
        };

        // 1. DNS
        $isIp = (bool) filter_var($host, FILTER_VALIDATE_IP);
        $ip = $isIp ? $host : gethostbyname($host);
        $resolved = $isIp || $ip !== $host;
        if (! $add('DNS', $resolved, $isIp ? "Alamat IP langsung ({$host})" : ($resolved ? "{$host} => {$ip}" : "Host \"{$host}\" tidak ditemukan. Periksa MAIL_HOST dan koneksi internet."))) {
            return $steps;
        }

        // 2. TCP
        $implicitTls = $port === 465;
        $ctx = stream_context_create();
        $errno = 0;
        $errstr = '';
        $fp = @stream_socket_client(($implicitTls ? 'ssl://' : 'tcp://') . "{$host}:{$port}", $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
        if (! $add('Sambungan ' . ($implicitTls ? 'TLS' : 'TCP'), (bool) $fp, $fp ? "Tersambung ke {$host}:{$port}" : "Gagal ({$errno}) {$errstr}")) {
            return $steps;
        }
        stream_set_timeout($fp, $timeout);

        // 3. Banner
        $banner = trim((string) fgets($fp, 512));
        if (! $add('Sapaan server', str_starts_with($banner, '220'), $banner !== '' ? $banner : 'Server tidak membalas (kemungkinan diblokir firewall/antivirus).')) {
            fclose($fp);

            return $steps;
        }

        // 4. EHLO
        fwrite($fp, "EHLO clover.local\r\n");
        $caps = '';
        while (($line = fgets($fp, 512)) !== false) {
            $caps .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $add('EHLO', str_contains($caps, '250'), trim(str_replace("\r\n", ' | ', $caps)) ?: 'Tidak ada balasan');

        // 5. STARTTLS (port 587)
        if (! $implicitTls) {
            if (stripos($caps, 'STARTTLS') === false) {
                $add('STARTTLS', false, 'Server tidak menawarkan STARTTLS pada port ini.');
            } else {
                fwrite($fp, "STARTTLS\r\n");
                $reply = trim((string) fgets($fp, 512));
                if (str_starts_with($reply, '220')) {
                    $ok = @stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                    $err = error_get_last()['message'] ?? '';
                    $add('STARTTLS', (bool) $ok, $ok ? 'Sambungan aman terbentuk (sertifikat valid).' : 'TLS gagal: ' . $err);
                } else {
                    $add('STARTTLS', false, 'Server menolak STARTTLS: ' . $reply);
                }
            }
        }

        fclose($fp);

        return $steps;
    }
}
