<?php
session_start();

// Load configuration
// require_once 'config.php'; 

$darkmode = $_COOKIE["darkmode"] ?? null;
$github_token = $_SESSION['github_token'] ?? null;
$github_user = $_SESSION['github_user'] ?? null;
?>
<!DOCTYPE html>
<html lang="pt-BR">

<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>GitHub Contribution Message Generator</title>
   <style>
      body {
         font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Helvetica, Arial, sans-serif;
         margin: 0;
         padding: 20px;
         background: <?php echo $darkmode === "on" ? '#0d1117' : '#ffffff'; ?>;
         color: <?php echo $darkmode === "on" ? '#c9d1d9' : '#24292f'; ?>;
         transition: background 0.3s, color 0.3s;
      }

      .container {
         max-width: 1200px;
         margin: 0 auto;
         position: relative;
      }

      /* --- Flag Switcher Styles --- */
      .lang-switch {
         position: absolute;
         top: 0;
         right: 0;
         display: flex;
         gap: 10px;
      }

      .lang-btn {
         background: none;
         border: none;
         cursor: pointer;
         font-size: 24px;
         opacity: 0.5;
         transition: opacity 0.2s, transform 0.2s;
         filter: grayscale(100%);
      }

      .lang-btn:hover,
      .lang-btn.active {
         opacity: 1;
         filter: grayscale(0%);
         transform: scale(1.1);
      }

      h1 {
         text-align: center;
         margin-bottom: 30px;
         margin-top: 10px;
      }

      .github-auth {
         text-align: center;
         margin-bottom: 30px;
         padding: 20px;
         background: <?php echo $darkmode === "on" ? '#161b22' : '#f6f8fa'; ?>;
         border-radius: 8px;
      }

      .github-auth.connected {
         background: <?php echo $darkmode === "on" ? '#1a472a' : '#dafbe1'; ?>;
      }

      .btn {
         background: #238636;
         color: white;
         border: none;
         padding: 10px 20px;
         border-radius: 6px;
         cursor: pointer;
         font-size: 14px;
         font-weight: 500;
         transition: background 0.2s;
      }

      .btn:hover {
         background: #2ea043;
      }

      .btn:disabled {
         background: #6e7681;
         cursor: not-allowed;
      }

      .btn-secondary {
         background: #21262d;
         color: #c9d1d9;
         text-decoration: none;
         display: inline-block;
         margin-top: 10px;
         padding: 8px 16px;
         border-radius: 6px;
         font-size: 12px;
      }

      .btn-secondary:hover {
         background: #30363d;
      }

      .main-content {
         display: grid;
         grid-template-columns: 1fr;
         gap: 30px;
      }

      @media (min-width: 768px) {
         .main-content {
            grid-template-columns: 1fr 2fr;
         }
      }

      .input-section,
      .preview-section {
         background: <?php echo $darkmode === "on" ? '#161b22' : '#f6f8fa'; ?>;
         padding: 20px;
         border-radius: 8px;
      }

      label {
         display: block;
         margin-bottom: 8px;
         font-weight: 500;
      }

      input[type="text"],
      select {
         width: 100%;
         padding: 10px;
         border: 1px solid <?php echo $darkmode === "on" ? '#30363d' : '#d0d7de'; ?>;
         border-radius: 6px;
         background: <?php echo $darkmode === "on" ? '#0d1117' : '#ffffff'; ?>;
         color: <?php echo $darkmode === "on" ? '#c9d1d9' : '#24292f'; ?>;
         font-size: 14px;
         box-sizing: border-box;
      }

      .contribution-grid {
         display: inline-block;
         border: 1px solid <?php echo $darkmode === "on" ? '#30363d' : '#d0d7de'; ?>;
         border-radius: 6px;
         padding: 10px;
         background: <?php echo $darkmode === "on" ? '#0d1117' : '#ffffff'; ?>;
         overflow-x: auto;
         max-width: 100%;
      }

      .grid-container {
         display: flex;
         gap: 3px;
      }

      .grid-week {
         display: flex;
         flex-direction: column;
         gap: 3px;
      }

      .grid-day {
         width: 11px;
         height: 11px;
         border-radius: 2px;
         background: <?php echo $darkmode === "on" ? '#161b22' : '#ebedf0'; ?>;
         transition: background 0.2s;
      }

      .grid-day.level-0 {
         background: <?php echo $darkmode === "on" ? '#161b22' : '#ebedf0'; ?>;
      }

      .grid-day.level-1 {
         background: #0e4429;
      }

      .grid-day.level-2 {
         background: #006d32;
      }

      .grid-day.level-3 {
         background: #26a641;
      }

      .grid-day.level-4 {
         background: #39d353;
      }

      .info {
         margin-top: 15px;
         padding: 10px;
         background: <?php echo $darkmode === "on" ? '#1c2128' : '#fff8c5'; ?>;
         border-radius: 6px;
         font-size: 13px;
      }

      .status {
         margin-top: 10px;
         padding: 10px;
         border-radius: 6px;
         font-size: 14px;
      }

      .status.success {
         background: #dafbe1;
         color: #1a7f37;
      }

      .status.error {
         background: #ffebe9;
         color: #cf222e;
      }

      .status.info {
         background: #ddf4ff;
         color: #0969da;
      }

      .loading-tip {
         font-style: italic;
         margin-top: 10px;
         font-size: 0.9em;
         color: <?php echo $darkmode === "on" ? '#8b949e' : '#57606a'; ?>;
         min-height: 1.2em;
         /* Prevent layout jump */
         text-align: center;
      }

      /* Classe para animar a entrada da dica */
      .fade-in-up {
         animation: fadeInUp 0.5s ease-out forwards;
      }

      @keyframes fadeInUp {
         from {
            opacity: 0;
            transform: translateY(10px);
         }

         to {
            opacity: 1;
            transform: translateY(0);
         }
      }

      .progress-bar-container {
         background: #e0e0e0;
         border-radius: 10px;
         overflow: hidden;
         margin-top: 10px;
         height: 25px;
      }

      .progress-bar-fill {
         background: #238636;
         height: 100%;
         width: 0%;
         transition: width 0.3s ease;
         display: flex;
         align-items: center;
         justify-content: center;
         color: white;
         font-weight: bold;
         font-size: 12px;
      }

      /* --- Flag Switcher Styles --- */
      .lang-switch {
         position: absolute;
         top: 0;
         right: 0;
         display: flex;
         gap: 10px;
      }

      .lang-btn {
         background: none;
         border: none;
         cursor: pointer;
         padding: 0;
         /* Remove padding padrão */
         opacity: 0.5;
         transition: opacity 0.2s, transform 0.2s;
         filter: grayscale(100%);
      }

      /* Estilo novo para a imagem da bandeira */
      .lang-btn img {
         width: 32px;
         height: auto;
         border-radius: 4px;
         display: block;
         box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
      }

      .lang-btn:hover,
      .lang-btn.active {
         opacity: 1;
         filter: grayscale(0%);
         transform: scale(1.1);
      }
   </style>
</head>


<body <?php echo $darkmode === "on" ? 'data-theme="dark"' : ""; ?>>
   <div class="container">

      <div class="lang-switch">
         <button class="lang-btn" onclick="setLanguage('en')" id="btn-en" title="English">
            <img src="https://flagcdn.com/w40/us.png" alt="USA">
         </button>
         <button class="lang-btn" onclick="setLanguage('pt')" id="btn-pt" title="Português">
            <img src="https://flagcdn.com/w40/br.png" alt="Brasil">
         </button>
      </div>

      <h1>🎨 <span data-i18n="title">GitHub Contribution Message Generator</span></h1>

      <div class="github-auth <?php echo $github_token ? 'connected' : ''; ?>">
         <?php if ($github_token && $github_user) : ?>
            <p>✅ <span data-i18n="connected_as">Conectado como:</span> <strong><?php echo htmlspecialchars($github_user['login']); ?></strong></p>
            <p style="font-size: 12px; margin-top: 5px;">Token: <?php echo substr($github_token, 0, 10); ?>...</p>
            <a href="logout.php" class="btn btn-secondary" data-i18n="logout">Desconectar</a>
         <?php else : ?>
            <p data-i18n="connect_msg">Conecte-se ao GitHub para gerar commits automaticamente</p>
            <a href="auth.php" class="btn" data-i18n="connect_btn">Conectar com GitHub</a>
         <?php endif; ?>
      </div>

      <div class="main-content">
         <div class="input-section">
            <h2 data-i18n="settings">Configurações</h2>
            <form id="messageForm">
               <label for="message" data-i18n="label_message">Mensagem (máx. 10 caracteres):</label>
               <input type="text" id="message" name="message" maxlength="10" placeholder="Ex: 2026" value="2026">

               <label for="username" style="margin-top: 15px;" data-i18n="label_repo">Nome do Repositório:</label>
               <input type="text" id="username" name="username" placeholder="user/repo" value="<?php echo $github_user ? htmlspecialchars($github_user['login']) . '/git-draw' : ''; ?>">

               <div style="display: flex; gap: 10px; margin-top: 15px;">
                  <div style="flex: 1;">
                     <label for="year" data-i18n="label_year">Ano:</label>
                     <select id="year" name="year">
                        <?php
                        $currentYear = date('Y');
                        for ($y = $currentYear; $y >= 2021; $y--) {
                           $selected = ($y == $currentYear) ? 'selected' : '';
                           echo "<option value='$y' $selected>$y</option>";
                        }
                        ?>
                     </select>
                  </div>
                  <div style="flex: 1;">
                     <label for="intensity" data-i18n="label_intensity">Intensidade:</label>
                     <select id="intensity" name="intensity" onchange="generatePreview()">
                        <option value="1" data-i18n="intensity_1">1</option>
                        <option value="2" data-i18n="intensity_2">2</option>
                        <option value="3" data-i18n="intensity_3">3</option>
                        <option value="4" selected data-i18n="intensity_4">4</option>
                     </select>
                  </div>
               </div>

               <div style="margin-top: 20px;">
                  <button type="button" class="btn" id="generateBtn" onclick="generateCommits(event)" <?php echo $github_token ? '' : 'disabled'; ?> style="width: 100%;">
                     <span data-i18n="btn_generate">Gerar Commits</span>
                  </button>
               </div>
            </form>
         </div>

         <div class="preview-section">
            <h2>Preview</h2>
            <div class="contribution-grid">
               <div class="grid-container" id="gridContainer"></div>
            </div>
            <div class="info" id="info">
            </div>

            <div id="status" class="status" style="display: none; margin-top: 15px;">
               <div id="statusText"></div>
               <div class="progress-bar-container">
                  <div id="progressBar" class="progress-bar-fill">0%</div>
               </div>
               <div id="loadingTip" class="loading-tip"></div>
            </div>

         </div>
      </div>
   </div>

   <script>
      // --- TRANSLATION CONFIGURATION ---
      const TRANSLATIONS = {
         pt: {
            title: "Gerador de Mensagens GitHub",
            connected_as: "Conectado como:",
            logout: "Desconectar",
            connect_msg: "Conecte-se ao GitHub para gerar commits automaticamente",
            connect_btn: "Conectar com GitHub",
            settings: "Configurações",
            label_message: "Mensagem (máx. 10 caracteres):",
            label_repo: "Nome do Repositório:",
            label_year: "Ano:",
            label_intensity: "Intensidade:",
            intensity_1: "1",
            intensity_2: "2",
            intensity_3: "3",
            intensity_4: "4",
            btn_generate: "Gerar Commits",
            preview_instructions_title: "Instruções:",
            preview_instructions_1: "1. Digite a mensagem e escolha a intensidade.",
            preview_instructions_2: "2. Conecte-se ao GitHub e clique em gerar.",
            info_message: "Mensagem:",
            info_active_days: "Dias ativos:",
            info_intensity: "Intensidade:",
            info_commits: "Total de commits estimados:",
            info_default: "Digite uma mensagem para ver o preview",
            status_connecting: "Conectando ao mainframe...",
            status_error: "Erro:",
            status_connection_lost: "Conexão perdida.",
            status_success: "Missão cumprida!",
            status_processing: "Processando...",
            err_fill_fields: "Preencha todos os campos obrigatórios.",
            btn_processing: "Processando...",
            tips: [
               "Dica: Commits verdes deixam seu perfil mais saudável (segundo boatos).",
               "Compilando a paciência...",
               "Dica: Git reset --hard é perigoso, mas divertido.",
               "Alimentando os Octocats...",
               "Gerando commits enquanto você toma um café.",
               "Sabia que você pode desenhar qualquer coisa no grid?",
               "Não desligue o console... digo, o navegador.",
               "Escovando os bits...",
               "Commitando mensagens subliminares.",
               "Se der erro, a culpa é do cache.",
               "Contando pixels...",
               "Dica: Use intensidade máxima para mensagens mais visíveis."
            ]
         },
         en: {
            title: "GitHub Contribution Message Generator",
            connected_as: "Connected as:",
            logout: "Logout",
            connect_msg: "Connect to GitHub to generate commits automatically",
            connect_btn: "Connect with GitHub",
            settings: "Settings",
            label_message: "Message (max 10 chars):",
            label_repo: "Repository Name:",
            label_year: "Year:",
            label_intensity: "Intensity:",
            intensity_1: "1",
            intensity_2: "2",
            intensity_3: "3",
            intensity_4: "4",
            btn_generate: "Generate Commits",
            preview_instructions_title: "Instructions:",
            preview_instructions_1: "1. Type a message and choose intensity.",
            preview_instructions_2: "2. Connect to GitHub and click generate.",
            info_message: "Message:",
            info_active_days: "Active days:",
            info_intensity: "Intensity:",
            info_commits: "Estimated total commits:",
            info_default: "Type a message to see the preview",
            status_connecting: "Connecting to mainframe...",
            status_error: "Error:",
            status_connection_lost: "Connection lost.",
            status_success: "Mission Accomplished!",
            status_processing: "Processing...",
            err_fill_fields: "Please fill in all required fields.",
            btn_processing: "Processing...",
            tips: [
               "Tip: Green commits make your profile healthier (rumor has it).",
               "Compiling patience...",
               "Tip: Git reset --hard is dangerous, but fun.",
               "Feeding the Octocats...",
               "Generating commits while you grab a coffee.",
               "Did you know you can draw anything on the grid?",
               "Don't turn off the console... I mean, the browser.",
               "Brushing the bits...",
               "Committing subliminal messages.",
               "If it breaks, blame the cache.",
               "Counting pixels...",
               "Tip: Use max intensity for better visibility."
            ]
         }
      };

      let currentLang = 'en';

      // --- CORE LOGIC ---
      const WEEKS = 52;
      const DAYS_PER_WEEK = 7;
      let tipInterval;

      const CHAR_PATTERNS = {
         '0': ['01110', '10001', '10001', '10001', '10001', '10001', '01110'],
         '1': ['00100', '01100', '00100', '00100', '00100', '00100', '01110'],
         '2': ['01110', '10001', '00001', '00010', '00100', '01000', '11111'],
         '3': ['01110', '10001', '00001', '00110', '00001', '10001', '01110'],
         '4': ['00010', '00110', '01010', '10010', '11111', '00010', '00010'],
         '5': ['11111', '10000', '10000', '11110', '00001', '10001', '01110'],
         '6': ['01110', '10001', '10000', '11110', '10001', '10001', '01110'],
         '7': ['11111', '00001', '00010', '00100', '01000', '01000', '01000'],
         '8': ['01110', '10001', '10001', '01110', '10001', '10001', '01110'],
         '9': ['01110', '10001', '10001', '01111', '00001', '10001', '01110'],
         'A': ['01110', '10001', '10001', '11111', '10001', '10001', '10001'],
         'B': ['11110', '10001', '10001', '11110', '10001', '10001', '11110'],
         'C': ['01110', '10001', '10000', '10000', '10000', '10001', '01110'],
         'D': ['11110', '10001', '10001', '10001', '10001', '10001', '11110'],
         'E': ['11111', '10000', '10000', '11110', '10000', '10000', '11111'],
         'F': ['11111', '10000', '10000', '11110', '10000', '10000', '10000'],
         'G': ['01110', '10001', '10000', '10111', '10001', '10001', '01110'],
         'H': ['10001', '10001', '10001', '11111', '10001', '10001', '10001'],
         'I': ['01110', '00100', '00100', '00100', '00100', '00100', '01110'],
         'J': ['00111', '00010', '00010', '00010', '00010', '10010', '01100'],
         'K': ['10001', '10010', '10100', '11000', '10100', '10010', '10001'],
         'L': ['10000', '10000', '10000', '10000', '10000', '10000', '11111'],
         'M': ['10001', '11011', '10101', '10001', '10001', '10001', '10001'],
         'N': ['10001', '11001', '10101', '10011', '10001', '10001', '10001'],
         'O': ['01110', '10001', '10001', '10001', '10001', '10001', '01110'],
         'P': ['11110', '10001', '10001', '11110', '10000', '10000', '10000'],
         'Q': ['01110', '10001', '10001', '10001', '10101', '10011', '01111'],
         'R': ['11110', '10001', '10001', '11110', '10100', '10010', '10001'],
         'S': ['01110', '10001', '10000', '01110', '00001', '10001', '01110'],
         'T': ['11111', '00100', '00100', '00100', '00100', '00100', '00100'],
         'U': ['10001', '10001', '10001', '10001', '10001', '10001', '01110'],
         'V': ['10001', '10001', '10001', '10001', '10001', '01010', '00100'],
         'W': ['10001', '10001', '10001', '10001', '10101', '11011', '10001'],
         'X': ['10001', '01010', '00100', '00100', '00100', '01010', '10001'],
         'Y': ['10001', '10001', '01010', '00100', '00100', '00100', '00100'],
         'Z': ['11111', '00001', '00010', '00100', '01000', '10000', '11111'],
         ' ': ['00000', '00000', '00000', '00000', '00000', '00000', '00000']
      };

      let gridData = Array(WEEKS).fill(null).map(() => Array(DAYS_PER_WEEK).fill(0));

      // --- I18N FUNCTIONS ---
      function detectLanguage() {
         const userLang = navigator.language || navigator.userLanguage;
         if (userLang.toLowerCase().startsWith('pt')) {
            setLanguage('pt');
         } else {
            setLanguage('en');
         }
      }

      function setLanguage(lang) {
         currentLang = lang;
         const t = TRANSLATIONS[lang];

         // Update simple text elements
         document.querySelectorAll('[data-i18n]').forEach(el => {
            const key = el.getAttribute('data-i18n');
            if (t[key]) el.textContent = t[key];
         });

         // Toggle active buttons
         document.getElementById('btn-pt').classList.toggle('active', lang === 'pt');
         document.getElementById('btn-en').classList.toggle('active', lang === 'en');

         // Refresh preview info text
         generatePreview();
      }

      // --- GRID & PREVIEW FUNCTIONS ---
      function initGrid() {
         const container = document.getElementById('gridContainer');
         container.innerHTML = '';

         for (let week = 0; week < WEEKS; week++) {
            const weekDiv = document.createElement('div');
            weekDiv.className = 'grid-week';
            for (let day = 0; day < DAYS_PER_WEEK; day++) {
               const dayDiv = document.createElement('div');
               dayDiv.className = 'grid-day level-0';
               dayDiv.dataset.week = week;
               dayDiv.dataset.day = day;
               weekDiv.appendChild(dayDiv);
            }
            container.appendChild(weekDiv);
         }
      }

      function updateGrid() {
         for (let week = 0; week < WEEKS; week++) {
            for (let day = 0; day < DAYS_PER_WEEK; day++) {
               const dayDiv = document.querySelector(`[data-week="${week}"][data-day="${day}"]`);
               // FIX: Verificar se o elemento existe antes de tentar acessar className
               if (dayDiv) {
                  const level = gridData[week][day];
                  dayDiv.className = `grid-day level-${level}`;
               }
            }
         }
      }

      function drawCharacter(char, startWeek) {
         const pattern = CHAR_PATTERNS[char.toUpperCase()] || CHAR_PATTERNS[' '];
         const charWidth = pattern[0].length;
         const intensity = parseInt(document.getElementById('intensity').value);

         for (let row = 0; row < 7; row++) {
            for (let col = 0; col < charWidth; col++) {
               const week = startWeek + col;
               if (week >= 0 && week < WEEKS) {
                  if (pattern[row] && pattern[row][col] === '1') {
                     gridData[week][row] = intensity;
                  }
               }
            }
         }
      }

      function generatePreview() {
         const message = document.getElementById('message').value.toUpperCase();
         gridData = Array(WEEKS).fill(null).map(() => Array(DAYS_PER_WEEK).fill(0));

         if (message) {
            const charWidth = 6;
            const totalWidth = message.length * charWidth;
            const startWeek = Math.floor((WEEKS - totalWidth) / 2);
            for (let i = 0; i < message.length; i++) {
               drawCharacter(message[i], startWeek + (i * charWidth));
            }
         }
         updateGrid();

         const info = document.getElementById('info');
         const intensity = document.getElementById('intensity').value;
         const t = TRANSLATIONS[currentLang];

         if (message) {
            const activeDays = (gridData.flat().filter(level => level > 0).length);
            const totalCommits = activeDays * intensity;
            info.innerHTML = `
                    <strong>${t.info_message}</strong> ${message}<br>
                    <strong>${t.info_active_days}</strong> ${activeDays}<br>
                    <strong>${t.info_intensity}</strong> ${t['intensity_'+intensity]}<br>
                    <strong>${t.info_commits}</strong> ${totalCommits}
                `;
         } else {
            info.innerHTML = `<strong>${t.preview_instructions_title}</strong><br>${t.preview_instructions_1}<br>${t.preview_instructions_2}<br><br><em>${t.info_default}</em>`;
         }
      }

      function updateLoadingTip() {
         const tipElement = document.getElementById('loadingTip');
         if (tipElement) {
            // 1. Remove Animation Class
            tipElement.classList.remove('fade-in-up');

            // 2. Trigger Reflow (magic to restart animation)
            void tipElement.offsetWidth;

            // 3. Change Text
            const tips = TRANSLATIONS[currentLang].tips;
            const randomTip = tips[Math.floor(Math.random() * tips.length)];
            tipElement.textContent = randomTip;

            // 4. Add Animation Class
            tipElement.classList.add('fade-in-up');
         }
      }

      async function generateCommits(event) {
         const message = document.getElementById('message').value;
         const repo = document.getElementById('username').value;
         const year = document.getElementById('year').value;

         const statusDiv = document.getElementById('status');
         const statusText = document.getElementById('statusText');
         const progressBar = document.getElementById('progressBar');
         const loadingTip = document.getElementById('loadingTip');
         const t = TRANSLATIONS[currentLang];

         if (!message || !repo) {
            statusDiv.className = 'status error';
            statusText.textContent = t.err_fill_fields;
            statusDiv.style.display = 'block';
            return;
         }

         const generateBtn = event.target;
         const originalText = generateBtn.textContent;
         generateBtn.disabled = true;
         generateBtn.textContent = t.btn_processing;

         // Reset UI for new run
         statusDiv.className = 'status info';
         statusDiv.style.display = 'block';
         statusText.innerHTML = `⏳ ${t.status_connecting}`;
         progressBar.style.width = '0%';
         progressBar.textContent = '0%';

         // Set initial tip immediately
         const tips = TRANSLATIONS[currentLang].tips;
         loadingTip.textContent = tips[Math.floor(Math.random() * tips.length)];
         loadingTip.classList.add('fade-in-up');

         // Start tip interval
         if (tipInterval) clearInterval(tipInterval);
         tipInterval = setInterval(updateLoadingTip, 4000);

         try {
            const eventSource = new EventSource('generate_commits.php?' + new URLSearchParams({
               message: message,
               repo: repo,
               year: year,
               gridData: JSON.stringify(gridData)
            }));

            eventSource.onmessage = function(event) {
               const data = JSON.parse(event.data);

               if (data.type === 'progress') {
                  const percentage = data.percentage || 0;
                  const msg = data.message || t.status_processing;

                  // Only update progress bar and status text
                  // DO NOT TOUCH loadingTip here to avoid animation reset
                  statusText.innerHTML = `⏳ ${msg}`;
                  progressBar.style.width = `${percentage}%`;
                  progressBar.textContent = `${percentage}%`;

               } else if (data.type === 'complete') {
                  eventSource.close();
                  clearInterval(tipInterval);
                  loadingTip.textContent = ''; // Clear tips

                  if (data.success) {
                     statusDiv.className = 'status success';
                     statusText.innerHTML = `✅ ${data.message}<br><strong>${t.status_success}</strong>`;
                  } else {
                     statusDiv.className = 'status error';
                     statusText.innerHTML = `❌ ${t.status_error} ${data.message}`;
                  }

                  generateBtn.disabled = false;
                  generateBtn.textContent = originalText;

               } else if (data.type === 'error') {
                  eventSource.close();
                  clearInterval(tipInterval);
                  statusDiv.className = 'status error';
                  statusText.textContent = `❌ ${t.status_error} ${data.message}`;
                  generateBtn.disabled = false;
                  generateBtn.textContent = originalText;
               }
            };

            eventSource.onerror = function(error) {
               if (error.readyState === EventSource.CLOSED) {
                  eventSource.close();
                  clearInterval(tipInterval);
                  statusDiv.className = 'status error';
                  statusText.textContent = `❌ ${t.status_connection_lost}`;
                  generateBtn.disabled = false;
                  generateBtn.textContent = originalText;
               }
            };

         } catch (error) {
            clearInterval(tipInterval);
            statusDiv.className = 'status error';
            statusText.textContent = `❌ JS Error: ${error.message}`;
            generateBtn.disabled = false;
            generateBtn.textContent = originalText;
         }
      }

      // FIX: Invertemos a ordem. Primeiro cria o grid (initGrid), DEPOIS tenta traduzir/preencher (detectLanguage)
      document.addEventListener('DOMContentLoaded', function() {
         initGrid(); // 1. Cria os elementos do grid no DOM
         detectLanguage(); // 2. Detecta linguagem e atualiza os textos/cores do grid

         document.getElementById('message').addEventListener('input', generatePreview);
      });
   </script>
</body>

</html>