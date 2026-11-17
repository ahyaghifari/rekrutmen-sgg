<?php

namespace Tests\Feature;

use App\Mail\Transport\FailoverFromTransport;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\TransportInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

/** php artisan test --filter=MailFailoverTest (tanpa database). */
class MailFailoverTest extends TestCase
{
    private function fake(bool $fails): object
    {
        return new class($fails) implements TransportInterface {
            public array $sent = [];
            public function __construct(private bool $fails) {}
            public function send(RawMessage $message, ?Envelope $envelope = null): ?SentMessage
            {
                if ($this->fails) throw new TransportException('akun disuspend');
                $this->sent[] = [$message, $envelope];
                return null;
            }
            public function __toString(): string { return 'fake'; }
        };
    }

    private function mail(): Email
    {
        return (new Email)->from('utama@x.test')->to('pelamar@x.test')->subject('Tes')->text('isi');
    }

    public function test_falls_back_to_backup_with_backup_sender_when_primary_fails(): void
    {
        Cache::forget('mail.primary_failed');
        [$primary, $backup] = [$this->fake(true), $this->fake(false)];

        (new FailoverFromTransport($primary, $backup, 'cadangan@x.test', 'Rekrutmen'))->send($this->mail());

        [$msg, $env] = $backup->sent[0];
        $this->assertSame('cadangan@x.test', $msg->getFrom()[0]->getAddress());
        $this->assertSame('cadangan@x.test', $env->getSender()->getAddress());
        $this->assertSame('pelamar@x.test', $env->getRecipients()[0]->getAddress());
        $this->assertSame('Tes', $msg->getSubject());
    }

    public function test_uses_primary_untouched_when_it_works(): void
    {
        Cache::forget('mail.primary_failed');
        [$primary, $backup] = [$this->fake(false), $this->fake(false)];

        (new FailoverFromTransport($primary, $backup, 'cadangan@x.test'))->send($this->mail());

        $this->assertCount(1, $primary->sent);
        $this->assertSame('utama@x.test', $primary->sent[0][0]->getFrom()[0]->getAddress());
        $this->assertSame([], $backup->sent);
    }

    public function test_failed_primary_is_skipped_for_a_while(): void
    {
        Cache::forget('mail.primary_failed');
        [$primary, $backup] = [$this->fake(true), $this->fake(false)];
        $t = new FailoverFromTransport($primary, $backup, 'cadangan@x.test');

        $t->send($this->mail());
        $t->send($this->mail()); // utama dilewati: tidak diketuk lagi

        $this->assertCount(2, $backup->sent);
        Cache::forget('mail.primary_failed');
    }

    public function test_resilient_mailer_is_configured(): void
    {
        config(['mail.mailers.resilient.backup_from.address' => 'cadangan@x.test']);

        $this->assertInstanceOf(\Illuminate\Mail\Mailer::class, app('mail.manager')->mailer('resilient'));
    }
}
