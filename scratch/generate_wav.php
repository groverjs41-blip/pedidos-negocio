<?php

function generateWavDataUri(array $frequencies, float $duration = 0.6): string {
    $sampleRate = 44100;
    $numSamples = (int)($sampleRate * $duration);
    $dataSize = $numSamples * 2;
    $fileSize = 44 + $dataSize;

    $header = pack('N', 0x52494646); // "RIFF"
    $header .= pack('V', $fileSize - 8);
    $header .= pack('N', 0x57415645); // "WAVE"
    $header .= pack('N', 0x666d7420); // "fmt "
    $header .= pack('V', 16);          // Subchunk1Size (16 for PCM)
    $header .= pack('v', 1);           // AudioFormat (1 for PCM)
    $header .= pack('v', 1);           // NumChannels (1 mono)
    $header .= pack('V', $sampleRate); // SampleRate
    $header .= pack('V', $sampleRate * 2); // ByteRate
    $header .= pack('v', 2);           // BlockAlign
    $header .= pack('v', 16);          // BitsPerSample

    $header .= pack('N', 0x64617461); // "data"
    $header .= pack('V', $dataSize);

    $samples = '';
    for ($i = 0; $i < $numSamples; $i++) {
        $t = $i / $sampleRate;
        $val = 0.0;
        foreach ($frequencies as $f) {
            $freq = $f['freq'];
            $startTime = $f['start'] ?? 0.0;
            $decay = $f['decay'] ?? 4.0;
            $vol = $f['vol'] ?? 0.5;

            if ($t >= $startTime) {
                $elapsed = $t - $startTime;
                $envelope = exp(-$decay * $elapsed);
                $val += sin(2 * M_PI * $freq * $elapsed) * $envelope * $vol;
            }
        }
        $val = max(-1.0, min(1.0, $val));
        $pcm = (int)($val * 32767);
        $samples .= pack('v', $pcm);
    }

    return 'data:audio/wav;base64,' . base64_encode($header . $samples);
}

$kitchenBell = generateWavDataUri([
    ['freq' => 880,  'start' => 0.0, 'decay' => 4.0, 'vol' => 0.6],
    ['freq' => 1320, 'start' => 0.0, 'decay' => 5.0, 'vol' => 0.3],
    ['freq' => 1760, 'start' => 0.0, 'decay' => 6.0, 'vol' => 0.15],
], 0.7);

$deliveryChime = generateWavDataUri([
    ['freq' => 523.25, 'start' => 0.0, 'decay' => 5.0, 'vol' => 0.5],
    ['freq' => 659.25, 'start' => 0.18, 'decay' => 4.0, 'vol' => 0.6],
], 0.85);

$jsContent = "// Auto-generated Base64 PCM WAV Audio Chimes for 100% reliable cross-browser playback\n";
$jsContent .= "export const KITCHEN_BELL_WAV = " . json_encode($kitchenBell) . ";\n";
$jsContent .= "export const DELIVERY_CHIME_WAV = " . json_encode($deliveryChime) . ";\n";

file_put_contents('/var/www/resources/js/audio_sounds.js', $jsContent);
echo "Audio sounds JS generated successfully!\n";
