<?php

namespace App\Console\Commands;

use App\Mail\ApplicantNotificationMail;
use App\Models\ApplicationNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Kirim email pemberitahuan pelamar yang berstatus 'queued' (ditulis oleh HRIS / admin di
 * sini), lalu tandai 'sent'. Dijadwalkan tiap menit (routes/console.php).
 */
class DispatchApplicationNotifications extends Command
{
    protected $signature = 'notifications:dispatch {--limit=100}';

    protected $description = 'Kirim email pemberitahuan pelamar yang berstatus queued';

    public function handle(): int
    {
        $queued = ApplicationNotification::with('jobApplication.user')
            ->where('status', 'queued')
            ->orderBy('created_at')
            ->limit((int) $this->option('limit'))
            ->get();

        if ($queued->isEmpty()) {
            return self::SUCCESS;
        }

        $sent = 0;

        foreach ($queued as $notif) {
            $email = $notif->jobApplication?->user?->email;

            if (! $email) {
                $notif->update(['status' => 'sent', 'sent_at' => now()]); // tak ada tujuan — jangan retry selamanya
                continue;
            }

            try {
                Mail::to($email)->send(new ApplicantNotificationMail($notif));
                $notif->update(['status' => 'sent', 'sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('notifications:dispatch gagal kirim', ['id' => $notif->id, 'error' => $e->getMessage()]);
                // biarkan 'queued' — dicoba lagi di run berikutnya
            }
        }

        $this->info("{$sent}/{$queued->count()} email terkirim.");

        return self::SUCCESS;
    }
}
