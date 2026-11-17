<?php

namespace App\Mail\Transport;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;

/**
 * Kirim lewat SMTP utama; kalau gagal (akun disuspend, auth ditolak, koneksi putus) beralih ke SMTP cadangan dengan alamat
 * pengirim (From) cadangan. Beda dengan mailer `failover` bawaan Laravel yang memakai satu From untuk semua transport,
 * padahal SMTP cadangan umumnya hanya mau mengirim atas nama akunnya sendiri.
 *
 * Utama yang baru gagal dilewati selama $skipMinutes menit, supaya akun yang sedang bermasalah tidak terus diketuk setiap email.
 */
class FailoverFromTransport implements TransportInterface
{
    private const SKIP_KEY = 'mail.primary_failed';

    public function __construct(
        private TransportInterface $primary,
        private TransportInterface $backup,
        private string $backupFromAddress,
        private ?string $backupFromName = null,
        private int $skipMinutes = 5,
    ) {}

    public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
    {
        if (! Cache::has(self::SKIP_KEY)) {
            try {
                return $this->primary->send($message, $envelope);
            } catch (TransportExceptionInterface $e) {
                Log::warning('Email: SMTP utama gagal, beralih ke cadangan.', ['error' => $e->getMessage()]);
                if ($this->skipMinutes > 0) {
                    Cache::put(self::SKIP_KEY, true, now()->addMinutes($this->skipMinutes));
                }
            }
        }

        return $this->backup->send(...$this->forBackup($message, $envelope));
    }

    /** @return array{0: RawMessage, 1: ?Envelope} */
    private function forBackup(RawMessage $message, ?Envelope $envelope): array
    {
        if (! $message instanceof Email) {
            return [$message, $envelope];
        }

        $recipients = ($envelope ?? Envelope::create($message))->getRecipients();
        $copy = clone $message;
        $copy->getHeaders()->remove('Sender');
        $copy->getHeaders()->remove('Return-Path');
        $copy->from(new Address($this->backupFromAddress, (string) $this->backupFromName));

        return [$copy, new Envelope(new Address($this->backupFromAddress), $recipients)];
    }

    public function __toString(): string
    {
        return 'failover-from://'.$this->primary.' '.$this->backup;
    }
}
