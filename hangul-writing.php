<?php
$pageTitle = 'Hangul Writing Practice';
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/session.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/helpers.php';
require_once __DIR__ . '/includes/auth-check.php';

// Hangul character sets
$consonants = [
    ['char' => 'ㄱ', 'name' => 'giyeok', 'romanized' => 'g/k'],
    ['char' => 'ㄴ', 'name' => 'nieun', 'romanized' => 'n'],
    ['char' => 'ㄷ', 'name' => 'digeut', 'romanized' => 'd/t'],
    ['char' => 'ㄹ', 'name' => 'rieul', 'romanized' => 'r/l'],
    ['char' => 'ㅁ', 'name' => 'mieum', 'romanized' => 'm'],
    ['char' => 'ㅂ', 'name' => 'bieup', 'romanized' => 'b/p'],
    ['char' => 'ㅅ', 'name' => 'siot', 'romanized' => 's'],
    ['char' => 'ㅇ', 'name' => 'ieung', 'romanized' => 'ng'],
    ['char' => 'ㅈ', 'name' => 'jieut', 'romanized' => 'j'],
    ['char' => 'ㅊ', 'name' => 'chieut', 'romanized' => 'ch'],
    ['char' => 'ㅋ', 'name' => 'kieuk', 'romanized' => 'k'],
    ['char' => 'ㅌ', 'name' => 'tieut', 'romanized' => 't'],
    ['char' => 'ㅍ', 'name' => 'pieup', 'romanized' => 'p'],
    ['char' => 'ㅎ', 'name' => 'hieut', 'romanized' => 'h'],
];

$doubleConsonants = [
    ['char' => 'ㄲ', 'name' => 'ssang-giyeok', 'romanized' => 'kk'],
    ['char' => 'ㄸ', 'name' => 'ssang-digeut', 'romanized' => 'tt'],
    ['char' => 'ㅃ', 'name' => 'ssang-bieup', 'romanized' => 'pp'],
    ['char' => 'ㅆ', 'name' => 'ssang-siot', 'romanized' => 'ss'],
    ['char' => 'ㅉ', 'name' => 'ssang-jieut', 'romanized' => 'jj'],
];

$vowels = [
    ['char' => 'ㅏ', 'name' => 'a', 'romanized' => 'a'],
    ['char' => 'ㅑ', 'name' => 'ya', 'romanized' => 'ya'],
    ['char' => 'ㅓ', 'name' => 'eo', 'romanized' => 'eo'],
    ['char' => 'ㅕ', 'name' => 'yeo', 'romanized' => 'yeo'],
    ['char' => 'ㅗ', 'name' => 'o', 'romanized' => 'o'],
    ['char' => 'ㅛ', 'name' => 'yo', 'romanized' => 'yo'],
    ['char' => 'ㅜ', 'name' => 'u', 'romanized' => 'u'],
    ['char' => 'ㅠ', 'name' => 'yu', 'romanized' => 'yu'],
    ['char' => 'ㅡ', 'name' => 'eu', 'romanized' => 'eu'],
    ['char' => 'ㅣ', 'name' => 'i', 'romanized' => 'i'],
];

$compoundVowels = [
    ['char' => 'ㅐ', 'name' => 'ae', 'romanized' => 'ae'],
    ['char' => 'ㅒ', 'name' => 'yae', 'romanized' => 'yae'],
    ['char' => 'ㅔ', 'name' => 'e', 'romanized' => 'e'],
    ['char' => 'ㅖ', 'name' => 'ye', 'romanized' => 'ye'],
    ['char' => 'ㅘ', 'name' => 'wa', 'romanized' => 'wa'],
    ['char' => 'ㅙ', 'name' => 'wae', 'romanized' => 'wae'],
    ['char' => 'ㅚ', 'name' => 'oe', 'romanized' => 'oe'],
    ['char' => 'ㅝ', 'name' => 'wo', 'romanized' => 'wo'],
    ['char' => 'ㅞ', 'name' => 'we', 'romanized' => 'we'],
    ['char' => 'ㅟ', 'name' => 'wi', 'romanized' => 'wi'],
    ['char' => 'ㅢ', 'name' => 'ui', 'romanized' => 'ui'],
];

$commonSyllables = [
    ['char' => '가', 'romanized' => 'ga'], ['char' => '나', 'romanized' => 'na'],
    ['char' => '다', 'romanized' => 'da'], ['char' => '라', 'romanized' => 'ra'],
    ['char' => '마', 'romanized' => 'ma'], ['char' => '바', 'romanized' => 'ba'],
    ['char' => '사', 'romanized' => 'sa'], ['char' => '아', 'romanized' => 'a'],
    ['char' => '자', 'romanized' => 'ja'], ['char' => '하', 'romanized' => 'ha'],
    ['char' => '한', 'romanized' => 'han'], ['char' => '글', 'romanized' => 'geul'],
    ['char' => '국', 'romanized' => 'guk'], ['char' => '어', 'romanized' => 'eo'],
    ['char' => '안', 'romanized' => 'an'], ['char' => '녕', 'romanized' => 'nyeong'],
];

$commonWords = [
    ['chars' => '한국', 'meaning' => 'Korea'],
    ['chars' => '한글', 'meaning' => 'Hangul'],
    ['chars' => '사람', 'meaning' => 'Person'],
    ['chars' => '감사', 'meaning' => 'Thanks'],
    ['chars' => '학교', 'meaning' => 'School'],
    ['chars' => '친구', 'meaning' => 'Friend'],
    ['chars' => '가족', 'meaning' => 'Family'],
    ['chars' => '사랑', 'meaning' => 'Love'],
    ['chars' => '음식', 'meaning' => 'Food'],
    ['chars' => '일', 'meaning' => 'Work'],
    ['chars' => '공부', 'meaning' => 'Study'],
    ['chars' => '시간', 'meaning' => 'Time'],
];

require_once __DIR__ . '/includes/header.php';
?>

<div class="max-w-7xl mx-auto">
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6">
        <div>
            <p class="text-sm text-gray-400 mt-0.5">Practice writing Korean characters on the whiteboard</p>
        </div>
        <div class="flex items-center gap-2 text-xs text-gray-400">
            <span class="hidden sm:inline">Shortcuts:</span>
            <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-mono">P</kbd> Pen
            <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-mono">E</kbd> Eraser
            <kbd class="px-1.5 py-0.5 bg-gray-100 rounded text-[10px] font-mono">Ctrl+Z</kbd> Undo
        </div>
    </div>

    <div class="grid lg:grid-cols-[1fr_320px] gap-6">
        <!-- Canvas Area -->
        <div>
            <!-- Toolbar -->
            <div class="bg-white rounded-t-2xl border border-gray-100 border-b-0 px-4 py-3 flex flex-wrap items-center gap-3">
                <!-- Tools -->
                <div class="flex items-center gap-1.5 pr-3 border-r border-gray-100">
                    <button data-tool="pen" onclick="HangulCanvas.setTool('pen')" class="p-2.5 rounded-xl bg-blue-50 ring-2 ring-blue-500 transition hover:bg-blue-50" title="Pen (P)">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                    </button>
                    <button data-tool="eraser" onclick="HangulCanvas.setTool('eraser')" class="p-2.5 rounded-xl transition hover:bg-gray-100" title="Eraser (E)">
                        <svg class="w-5 h-5 text-gray-600" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19.5 8.5l-8 8-4.5.5.5-4.5 8-8m4 4l-4-4m4 4l1.5-1.5a2.121 2.121 0 000-3l-1-1a2.121 2.121 0 00-3 0L15.5 4.5"/></svg>
                    </button>
                </div>

                <!-- Pen Size -->
                <div class="flex items-center gap-2 pr-3 border-r border-gray-100">
                    <input type="range" id="penSizeRange" min="1" max="16" value="4" oninput="HangulCanvas.setPenSize(this.value)" class="w-20 h-1.5 rounded-lg appearance-none bg-gray-200 accent-blue-600 cursor-pointer">
                    <span id="penSizeLabel" class="text-xs text-gray-500 w-8">4px</span>
                </div>

                <!-- Colors -->
                <div class="flex items-center gap-1.5 pr-3 border-r border-gray-100">
                    <?php
                    $colors = [
                        '#1e293b' => 'Black',
                        '#dc2626' => 'Red',
                        '#2563eb' => 'Blue',
                        '#16a34a' => 'Green',
                        '#9333ea' => 'Purple',
                        '#ea580c' => 'Orange'
                    ];
                    foreach ($colors as $hex => $name):
                    ?>
                    <button data-color="<?= $hex ?>" onclick="HangulCanvas.setPenColor('<?= $hex ?>')"
                        class="w-7 h-7 rounded-full border-2 border-white shadow-sm transition hover:scale-110 <?= $hex === '#1e293b' ? 'ring-2 ring-offset-2 ring-blue-500' : '' ?>"
                        style="background:<?= $hex ?>" title="<?= $name ?>"></button>
                    <?php endforeach; ?>
                </div>

                <!-- Actions -->
                <div class="flex items-center gap-1.5 ml-auto">
                    <button id="toggleGuideBtn" onclick="HangulCanvas.toggleGuide()" class="p-2 rounded-xl bg-blue-100 text-blue-700 transition hover:bg-blue-200 text-xs font-medium" title="Toggle guide character">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </button>
                    <button id="undoBtn" onclick="HangulCanvas.undo()" class="p-2 rounded-xl hover:bg-gray-100 transition disabled:opacity-30" title="Undo (Ctrl+Z)" disabled>
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a5 5 0 015 5v2M3 10l4-4m-4 4l4 4"/></svg>
                    </button>
                    <button id="redoBtn" onclick="HangulCanvas.redo()" class="p-2 rounded-xl hover:bg-gray-100 transition disabled:opacity-30" title="Redo (Ctrl+Y)" disabled>
                        <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a5 5 0 00-5 5v2m15-7l-4-4m4 4l-4 4"/></svg>
                    </button>
                    <button onclick="HangulCanvas.clearCanvas()" class="p-2 rounded-xl hover:bg-red-50 hover:text-red-600 transition text-gray-600" title="Clear (Del)">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <button onclick="HangulCanvas.downloadImage()" class="p-2 rounded-xl hover:bg-green-50 hover:text-green-600 transition text-gray-600" title="Save as image">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    </button>
                </div>
            </div>

            <!-- Canvas -->
            <div class="bg-white rounded-b-2xl border border-gray-100 border-t-0 overflow-hidden" style="height: 520px;" id="canvasContainer">
                <canvas id="hangulCanvas" class="w-full h-full"></canvas>
            </div>

            <!-- Current character info -->
            <div id="charInfo" class="mt-3 hidden">
                <div class="bg-white rounded-2xl border border-gray-100 p-4 flex items-center gap-4">
                    <div class="w-16 h-16 rounded-xl bg-blue-50 flex items-center justify-center">
                        <span id="charInfoChar" class="text-3xl font-bold text-blue-600 korean-text"></span>
                    </div>
                    <div>
                        <p class="text-sm font-semibold text-gray-900" id="charInfoName"></p>
                        <p class="text-xs text-gray-400" id="charInfoRoman"></p>
                    </div>
                    <button onclick="speakCurrentChar()" class="ml-auto p-2.5 rounded-xl bg-blue-50 hover:bg-blue-100 transition text-blue-600" title="Hear pronunciation">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M18.364 5.636a9 9 0 010 12.728M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707A1 1 0 0112 5.586v12.828a1 1 0 01-1.707.707L5.586 15z"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Character Reference Panel -->
        <div class="space-y-4 overflow-y-auto lg:max-h-[680px] pr-1">
            <!-- Tab Navigation -->
            <div class="bg-white rounded-2xl border border-gray-100 p-1.5 flex gap-1">
                <button onclick="switchTab('consonants')" data-tab="consonants" class="char-tab flex-1 py-2 rounded-xl text-xs font-semibold transition bg-blue-600 text-white">Consonants</button>
                <button onclick="switchTab('vowels')" data-tab="vowels" class="char-tab flex-1 py-2 rounded-xl text-xs font-semibold transition text-gray-500 hover:bg-gray-50">Vowels</button>
                <button onclick="switchTab('syllables')" data-tab="syllables" class="char-tab flex-1 py-2 rounded-xl text-xs font-semibold transition text-gray-500 hover:bg-gray-50">Syllables</button>
                <button onclick="switchTab('words')" data-tab="words" class="char-tab flex-1 py-2 rounded-xl text-xs font-semibold transition text-gray-500 hover:bg-gray-50">Words</button>
            </div>

            <!-- Consonants -->
            <div id="tab-consonants" class="char-panel">
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Basic Consonants</h3>
                    <div class="grid grid-cols-5 gap-2">
                        <?php foreach ($consonants as $c): ?>
                        <button onclick="selectChar('<?= $c['char'] ?>', '<?= $c['name'] ?>', '<?= $c['romanized'] ?>')"
                            class="char-btn group p-2 rounded-xl border border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition text-center cursor-pointer">
                            <span class="korean-text text-xl font-bold text-gray-800 group-hover:text-blue-600 block"><?= $c['char'] ?></span>
                            <span class="text-[10px] text-gray-400"><?= $c['romanized'] ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-4 mt-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Double Consonants</h3>
                    <div class="grid grid-cols-5 gap-2">
                        <?php foreach ($doubleConsonants as $c): ?>
                        <button onclick="selectChar('<?= $c['char'] ?>', '<?= $c['name'] ?>', '<?= $c['romanized'] ?>')"
                            class="char-btn group p-2 rounded-xl border border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition text-center cursor-pointer">
                            <span class="korean-text text-xl font-bold text-gray-800 group-hover:text-blue-600 block"><?= $c['char'] ?></span>
                            <span class="text-[10px] text-gray-400"><?= $c['romanized'] ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Vowels -->
            <div id="tab-vowels" class="char-panel hidden">
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Basic Vowels</h3>
                    <div class="grid grid-cols-5 gap-2">
                        <?php foreach ($vowels as $v): ?>
                        <button onclick="selectChar('<?= $v['char'] ?>', '<?= $v['name'] ?>', '<?= $v['romanized'] ?>')"
                            class="char-btn group p-2 rounded-xl border border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition text-center cursor-pointer">
                            <span class="korean-text text-xl font-bold text-gray-800 group-hover:text-blue-600 block"><?= $v['char'] ?></span>
                            <span class="text-[10px] text-gray-400"><?= $v['romanized'] ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-gray-100 p-4 mt-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Compound Vowels</h3>
                    <div class="grid grid-cols-5 gap-2">
                        <?php foreach ($compoundVowels as $v): ?>
                        <button onclick="selectChar('<?= $v['char'] ?>', '<?= $v['name'] ?>', '<?= $v['romanized'] ?>')"
                            class="char-btn group p-2 rounded-xl border border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition text-center cursor-pointer">
                            <span class="korean-text text-xl font-bold text-gray-800 group-hover:text-blue-600 block"><?= $v['char'] ?></span>
                            <span class="text-[10px] text-gray-400"><?= $v['romanized'] ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Syllables -->
            <div id="tab-syllables" class="char-panel hidden">
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Common Syllables</h3>
                    <div class="grid grid-cols-4 gap-2">
                        <?php foreach ($commonSyllables as $s): ?>
                        <button onclick="selectChar('<?= $s['char'] ?>', '', '<?= $s['romanized'] ?>')"
                            class="char-btn group p-2.5 rounded-xl border border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition text-center cursor-pointer">
                            <span class="korean-text text-2xl font-bold text-gray-800 group-hover:text-blue-600 block"><?= $s['char'] ?></span>
                            <span class="text-[10px] text-gray-400"><?= $s['romanized'] ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Words -->
            <div id="tab-words" class="char-panel hidden">
                <div class="bg-white rounded-2xl border border-gray-100 p-4">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Common Words</h3>
                    <div class="grid grid-cols-2 gap-2">
                        <?php foreach ($commonWords as $w): ?>
                        <button onclick="selectChar('<?= $w['chars'] ?>', '<?= $w['meaning'] ?>', '')"
                            class="char-btn group p-3 rounded-xl border border-gray-100 hover:border-blue-300 hover:bg-blue-50 transition text-center cursor-pointer">
                            <span class="korean-text text-xl font-bold text-gray-800 group-hover:text-blue-600 block"><?= $w['chars'] ?></span>
                            <span class="text-[10px] text-gray-400"><?= $w['meaning'] ?></span>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Free Write -->
            <div class="bg-white rounded-2xl border border-gray-100 p-4">
                <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-3">Custom Character</h3>
                <div class="flex gap-2">
                    <input type="text" id="customChar" placeholder="Type any Hangul..." maxlength="6"
                        class="flex-1 px-3 py-2 rounded-xl border border-gray-200 text-base korean-text text-center focus:outline-none focus:border-blue-300 focus:ring-2 focus:ring-blue-100">
                    <button onclick="selectCustomChar()" class="px-4 py-2 bg-blue-600 text-white rounded-xl text-sm font-medium hover:bg-blue-700 transition">
                        Set
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= APP_URL ?>/assets/js/hangul-canvas.js"></script>
<script>
    // Initialize canvas
    HangulCanvas.init('hangulCanvas');

    let currentChar = '';

    function selectChar(char, name, romanized) {
        currentChar = char;
        HangulCanvas.setGuide(char);

        // Update char info bar
        const info = document.getElementById('charInfo');
        info.classList.remove('hidden');
        document.getElementById('charInfoChar').textContent = char;
        document.getElementById('charInfoName').textContent = name || char;
        document.getElementById('charInfoRoman').textContent = romanized ? 'Romanized: ' + romanized : '';

        // Highlight selected button
        document.querySelectorAll('.char-btn').forEach(b => {
            b.classList.remove('border-blue-500', 'bg-blue-50');
            b.classList.add('border-gray-100');
        });
        event.currentTarget.classList.remove('border-gray-100');
        event.currentTarget.classList.add('border-blue-500', 'bg-blue-50');
    }

    function selectCustomChar() {
        const input = document.getElementById('customChar');
        const char = input.value.trim();
        if (!char) return;
        currentChar = char;
        HangulCanvas.setGuide(char);

        const info = document.getElementById('charInfo');
        info.classList.remove('hidden');
        document.getElementById('charInfoChar').textContent = char;
        document.getElementById('charInfoName').textContent = char;
        document.getElementById('charInfoRoman').textContent = '';
    }

    function speakCurrentChar() {
        if (currentChar && typeof KoreanTTS !== 'undefined') {
            KoreanTTS.speak(currentChar, { type: 'browser_tts', rate: 0.8 });
        }
    }

    function switchTab(tab) {
        document.querySelectorAll('.char-panel').forEach(p => p.classList.add('hidden'));
        document.getElementById('tab-' + tab).classList.remove('hidden');
        document.querySelectorAll('.char-tab').forEach(t => {
            t.classList.remove('bg-blue-600', 'text-white');
            t.classList.add('text-gray-500');
        });
        document.querySelector(`[data-tab="${tab}"]`).classList.remove('text-gray-500');
        document.querySelector(`[data-tab="${tab}"]`).classList.add('bg-blue-600', 'text-white');
    }

    // Custom char input: allow Enter key
    document.getElementById('customChar').addEventListener('keydown', (e) => {
        if (e.key === 'Enter') selectCustomChar();
    });
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
