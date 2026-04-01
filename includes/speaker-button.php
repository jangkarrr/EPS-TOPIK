<?php
/**
 * Reusable Speaker Button Component
 * 
 * Usage:
 *   <?= speakerBtn('안녕하세요') ?>
 *   <?= speakerBtn('일하다', ['size' => 'sm', 'label' => 'Hear word']) ?>
 *   <?= speakerBtn('한국어', ['audioUrl' => '/uploads/audio/word.mp3', 'size' => 'lg']) ?>
 *   <?= speakerBtnInline('감사합니다') ?>
 *   <?= passageListenBtn('Full passage text here...') ?>
 */

/**
 * Generate a speaker button HTML
 * @param string $koreanText The Korean text to speak
 * @param array $opts Options: size, label, audioUrl, audioType, rate, module, itemId, class
 */
function speakerBtn(string $koreanText, array $opts = []): string {
    $size = $opts['size'] ?? 'md';
    $label = $opts['label'] ?? '';
    $audioUrl = $opts['audioUrl'] ?? '';
    $audioType = $opts['audioType'] ?? ($audioUrl ? 'uploaded' : 'browser_tts');
    $rate = $opts['rate'] ?? '';
    $module = $opts['module'] ?? 'phrase';
    $itemId = $opts['itemId'] ?? '';
    $extraClass = $opts['class'] ?? '';
    $showLabel = $opts['showLabel'] ?? ($size !== 'xs');
    $escaped = htmlspecialchars($koreanText, ENT_QUOTES, 'UTF-8');

    // Size classes
    $sizes = [
        'xs' => 'w-7 h-7 p-0',
        'sm' => 'h-8 px-2.5 gap-1.5 text-xs',
        'md' => 'h-9 px-3 gap-2 text-sm',
        'lg' => 'h-10 px-4 gap-2 text-sm'
    ];
    $sizeClass = $sizes[$size] ?? $sizes['md'];
    $iconSize = $size === 'xs' ? 'w-3.5 h-3.5' : ($size === 'sm' ? 'w-3.5 h-3.5' : 'w-4 h-4');

    $dataAttrs = 'data-tts-text="' . $escaped . '"';
    if ($audioUrl)  $dataAttrs .= ' data-tts-audio-url="' . htmlspecialchars($audioUrl, ENT_QUOTES, 'UTF-8') . '"';
    if ($audioType) $dataAttrs .= ' data-tts-type="' . $audioType . '"';
    if ($rate)      $dataAttrs .= ' data-tts-rate="' . (float)$rate . '"';
    if ($module)    $dataAttrs .= ' data-tts-module="' . htmlspecialchars($module, ENT_QUOTES, 'UTF-8') . '"';
    if ($itemId)    $dataAttrs .= ' data-tts-item-id="' . (int)$itemId . '"';

    $labelHtml = '';
    if ($showLabel && $label) {
        $labelHtml = '<span class="tts-label" data-default-label="' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '">' . htmlspecialchars($label, ENT_QUOTES, 'UTF-8') . '</span>';
    } elseif ($showLabel && $size !== 'xs') {
        $labelHtml = '<span class="tts-label" data-default-label="Hear">Hear</span>';
    }

    return '<button type="button" ' . $dataAttrs . ' class="tts-btn tts-idle inline-flex items-center justify-center rounded-xl font-medium transition-all duration-200 bg-blue-50 hover:bg-blue-100 text-blue-600 active:scale-95 ' . $sizeClass . ' ' . $extraClass . '" title="Listen to pronunciation">'
        . '<span class="tts-icon flex-shrink-0"><svg class="' . $iconSize . '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg></span>'
        . $labelHtml
        . '</button>';
}

/**
 * Inline speaker icon (tiny, sits beside Korean text)
 */
function speakerBtnInline(string $koreanText, array $opts = []): string {
    $opts['size'] = 'xs';
    $opts['showLabel'] = false;
    $opts['class'] = 'inline-flex align-middle ml-1 rounded-lg bg-blue-50 hover:bg-blue-100 text-blue-500 ' . ($opts['class'] ?? '');
    return speakerBtn($koreanText, $opts);
}

/**
 * Slow playback button (0.6x speed)
 */
function speakerBtnSlow(string $koreanText, array $opts = []): string {
    $opts['rate'] = $opts['rate'] ?? 0.6;
    $opts['label'] = $opts['label'] ?? 'Slow';
    $opts['size'] = $opts['size'] ?? 'sm';
    $opts['class'] = 'bg-amber-50 hover:bg-amber-100 text-amber-600 ' . ($opts['class'] ?? '');
    return speakerBtn($koreanText, $opts);
}

/**
 * "Listen to passage" button (larger, prominent)
 */
function passageListenBtn(string $koreanText, array $opts = []): string {
    $opts['size'] = $opts['size'] ?? 'lg';
    $opts['label'] = $opts['label'] ?? 'Listen to passage';
    $opts['class'] = 'w-full justify-center bg-gradient-to-r from-blue-50 to-indigo-50 hover:from-blue-100 hover:to-indigo-100 text-blue-700 border border-blue-100 ' . ($opts['class'] ?? '');
    return speakerBtn($koreanText, $opts);
}

/**
 * Output the TTS config JSON block + script tag for the page
 * Call once in the header/footer
 */
function ttsScriptBlock(): string {
    require_once __DIR__ . '/tts-helpers.php';
    $cfg = getTTSConfig();
    $json = json_encode($cfg, JSON_UNESCAPED_UNICODE);
    return '<script id="tts-config" type="application/json">' . $json . '</script>'
         . "\n" . '<script src="' . APP_URL . '/assets/js/korean-tts.js"></script>'
         . "\n" . '<style>'
         . '.tts-btn:disabled{opacity:0.5;cursor:wait;}'
         . '.tts-btn.tts-playing{background:linear-gradient(135deg,#DBEAFE,#E0E7FF);box-shadow:0 0 0 2px rgba(99,102,241,0.3);}'
         . '.tts-btn.tts-playing .tts-icon svg{animation:tts-pulse 1s ease-in-out infinite;}'
         . '.tts-btn.tts-error{background:#FEF2F2;color:#DC2626;}'
         . '@keyframes tts-pulse{0%,100%{opacity:1;}50%{opacity:0.4;}}'
         . '</style>';
}
