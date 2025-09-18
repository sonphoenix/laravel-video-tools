<?php

return [
    'ffmpeg_binaries'  => env('FFMPEG_BINARIES', 'ffmpeg'),
    'ffprobe_binaries' => env('FFPROBE_BINARIES', 'ffprobe'),
    'timeout'          => 3600,
    'threads'          => 12,
];
