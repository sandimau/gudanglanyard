<?php

/**
 * Fallback bila document root server menunjuk ke folder proyek (bukan public/).
 * Jangan pakai redirect relatif "public/" — itu memicu loop di path seperti /shopee/auth/{id}.
 */
require __DIR__ . '/public/index.php';
