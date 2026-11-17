<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Mailer
    |--------------------------------------------------------------------------
    |
    | This option controls the default mailer that is used to send all email
    | messages unless another mailer is explicitly specified when sending
    | the message. All additional mailers can be configured within the
    | "mailers" array. Examples of each type of mailer are provided.
    |
    */

    'default' => env('MAIL_MAILER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Mailer Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure all of the mailers used by your application plus
    | their respective settings. Several examples have been configured for
    | you and you are free to add your own as your application requires.
    |
    | Laravel supports a variety of mail "transport" drivers that can be used
    | when delivering an email. You may specify which one you're using for
    | your mailers below. You may also add additional mailers if needed.
    |
    | Supported: "smtp", "sendmail", "mailgun", "ses", "ses-v2",
    |            "postmark", "resend", "log", "array",
    |            "failover", "roundrobin"
    |
    */

    'mailers' => [

        'smtp' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_SCHEME'),
            'url' => env('MAIL_URL'),
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'timeout' => env('MAIL_TIMEOUT'), // detik; isi (mis. 15) kalau memakai cadangan supaya SMTP yang macet tidak menahan antrean lama
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        // SMTP cadangan: akun lain, dipakai otomatis lewat mailer `resilient` kalau SMTP utama gagal.
        'smtp_backup' => [
            'transport' => 'smtp',
            'scheme' => env('MAIL_BACKUP_SCHEME'),
            'host' => env('MAIL_BACKUP_HOST', '127.0.0.1'),
            'port' => env('MAIL_BACKUP_PORT', 587),
            'username' => env('MAIL_BACKUP_USERNAME'),
            'password' => env('MAIL_BACKUP_PASSWORD'),
            'timeout' => env('MAIL_TIMEOUT'),
            'local_domain' => env('MAIL_EHLO_DOMAIN', parse_url(env('APP_URL', 'http://localhost'), PHP_URL_HOST)),
        ],

        // Aktifkan dengan MAIL_MAILER=resilient (nilai MAIL_* tetap untuk akun utama, MAIL_BACKUP_* untuk cadangan).
        'resilient' => [
            'transport' => 'failover_from',
            'primary' => 'smtp',
            'backup' => 'smtp_backup',
            'backup_from' => [
                'address' => env('MAIL_BACKUP_FROM_ADDRESS'),
                'name' => env('MAIL_BACKUP_FROM_NAME', env('MAIL_FROM_NAME', 'Example')),
            ],
            'skip_minutes' => env('MAIL_PRIMARY_SKIP_MINUTES', 5), // utama yang baru gagal dilewati sekian menit
        ],

        'ses' => [
            'transport' => 'ses',
        ],

        'postmark' => [
            'transport' => 'postmark',
            // 'message_stream_id' => env('POSTMARK_MESSAGE_STREAM_ID'),
            // 'client' => [
            //     'timeout' => 5,
            // ],
        ],

        'resend' => [
            'transport' => 'resend',
        ],

        'sendmail' => [
            'transport' => 'sendmail',
            'path' => env('MAIL_SENDMAIL_PATH', '/usr/sbin/sendmail -bs -i'),
        ],

        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],

        'array' => [
            'transport' => 'array',
        ],

        'failover' => [
            'transport' => 'failover',
            'mailers' => [
                'smtp',
                'log',
            ],
        ],

        'roundrobin' => [
            'transport' => 'roundrobin',
            'mailers' => [
                'ses',
                'postmark',
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Global "From" Address
    |--------------------------------------------------------------------------
    |
    | You may wish for all emails sent by your application to be sent from
    | the same address. Here you may specify a name and address that is
    | used globally for all emails that are sent by your application.
    |
    */

    // Balasan pelamar masuk ke kotak HR (email yang menulis "Balas email ini" jadi benar). Kosong = tanpa Reply-To.
    'reply_to' => env('MAIL_REPLY_TO_ADDRESS') ? ['address' => env('MAIL_REPLY_TO_ADDRESS'), 'name' => env('MAIL_FROM_NAME', 'Example')] : null,

    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
        'name' => env('MAIL_FROM_NAME', 'Example'),
    ],

];
