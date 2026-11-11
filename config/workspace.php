<?php

return [
    'mysqldump' => env('MYSQLDUMP_PATH', 'mysqldump'),
    'landing_url' => env('LANDING_URL', 'http://localhost:8000'),
    'chat_cookie' => env('CHAT_COOKIE', 'amertanala_chat'),
    'frame_origins' => explode(',', env('LANDING_FRAME_ORIGINS', 'http://localhost:8000,http://127.0.0.1:8000')),
    'default_content' => [
        'brand' => 'amertanala.',
        'title' => 'Amertanala — Undangan Digital yang Berkesan',
        'description' => 'Undangan digital yang personal dan bermakna. Temukan tema, ceritakan kisah Anda, dan bagikan kabar bahagia kepada orang tersayang.',
        'contact_email' => '',
        'intro_title' => 'Sebuah kabar bahagia.',
        'intro_emphasis' => 'Menunggu untuk dibuka.',
        'hero_title' => 'Cerita Anda.',
        'hero_emphasis' => 'Kesan yang tinggal.',
        'hero_description' => 'Undangan digital yang menyampaikan lebih dari waktu dan tempat. Hadirkan cerita Anda dengan cara yang istimewa.',
        'collection_title' => 'Setiap cerita, punya nuansanya.',
        'features_title' => 'Indah saat dibuka. Mudah saat digunakan.',
        'steps_title' => 'Rencanakan kabar bahagia Anda.',
        'steps_description' => 'Pilih tema, lengkapi detail acara, periksa pratinjau, lalu bagikan undangan kepada orang tersayang.',
        'order_title' => 'Awal cerita yang istimewa.',
        'order_button' => 'Diskusikan undangan saya',
        'footer_tagline' => 'Undangan digital · Dibuat dengan rasa',
        'chat_enabled' => true,
        'themes' => [
            ['id' => 'botanical-sage', 'name' => 'Botanical Sage', 'eyebrow' => 'THE WEDDING OF', 'names' => ['Nadia', '& Arga'], 'caption' => '12 · 12 · 2027', 'preview_url' => '', 'alt' => 'Pratinjau Botanical Sage'],
            ['id' => 'modern-editorial', 'name' => 'Modern Editorial', 'eyebrow' => 'TOGETHER WITH OUR FAMILIES', 'names' => ['Nadia & Arga'], 'caption' => 'A CELEBRATION OF LOVE', 'preview_url' => '', 'alt' => 'Pratinjau Modern Editorial'],
            ['id' => 'javanese-heritage', 'name' => 'Javanese Heritage', 'eyebrow' => 'THE WEDDING OF', 'names' => ['Nadia', '& Arga'], 'caption' => '12 · 12 · 2027', 'preview_url' => '', 'alt' => 'Pratinjau Javanese Heritage'],
        ],
        'features' => [
            ['title' => 'Sapaan personal', 'description' => 'Nama tamu dan kisah Anda dalam satu pengalaman.'],
            ['title' => 'RSVP & ucapan', 'description' => 'Konfirmasi kehadiran terkumpul rapi.'],
            ['title' => 'Jadwal, lokasi, dan galeri', 'description' => 'Detail acara yang mudah ditemukan dari ponsel.'],
        ],
    ],
];
