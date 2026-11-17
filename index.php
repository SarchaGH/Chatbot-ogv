<?php
/**
 * ============================================
 * KATTA AI CHAT - Hovedside
 * ============================================
 *
 * Hovedsiden for chat-applikasjonen:
 *  - velg agent og temperatur
 *  - samtalehistorikk i sidemenyen (lagres i nettleseren din)
 *  - fargetema (farge + auto/lys/mørk) – se css/theme.css og js/theme.js
 */

require_once 'config.php';
require_once 'auth.php';

// Krev innlogging for å se denne siden
requireLogin();
?>
<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(AGENT_NAME) ?> - AI Chat</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Styles -->
    <!-- Tema: theme.js FØRST (ingen "blink" i feil farge), deretter alle farger, deretter layout -->
    <script src="<?= asset('js/theme.js') ?>"></script>
    <link rel="stylesheet" href="<?= asset('css/theme.css') ?>">
    <link rel="stylesheet" href="<?= asset('css/style.css') ?>">

</head>
<body>
    <div class="layout">

        <!-- ============ Sidemeny: historikk og tema ============ -->
        <aside class="sidebar" id="sidebar">
            <div class="sidebar__top">
                <button type="button" class="sidebar__new js-new-chat">
                    <i class="fas fa-plus"></i> Ny samtale
                </button>
                <button type="button" class="sidebar__close" id="sidebarClose" title="Lukk meny">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="sidebar__heading">Tidligere samtaler</div>
            <ul class="sidebar__list" id="chatList"></ul>
            <div class="notice" id="storageWarning" hidden>
                <i class="fas fa-triangle-exclamation"></i>
                Nettleseren tillater ikke lagring (privat modus?). Historikken forsvinner når du lukker siden.
            </div>

            <div class="sidebar__tools">
                <button type="button" class="sidebar__tool" id="exportBtn" title="Last ned all historikk som fil">
                    <i class="fas fa-download"></i> Eksporter
                </button>
                <button type="button" class="sidebar__tool" id="importBtn" title="Hent inn historikk fra fil">
                    <i class="fas fa-upload"></i> Importer
                </button>
                <input type="file" id="importFile" accept="application/json,.json" hidden>
            </div>
            <button type="button" class="sidebar__clear" id="clearAllBtn">
                <i class="fas fa-trash"></i> Slett all historikk
            </button>

            <div class="sidebar__theme">
                <div class="sidebar__heading">Fargetema</div>
                <div class="swatches" id="swatches" role="radiogroup" aria-label="Fargetema">
                    <button type="button" class="swatch" data-accent="red"    role="radio" title="Rød"></button>
                    <button type="button" class="swatch" data-accent="blue"   role="radio" title="Blå"></button>
                    <button type="button" class="swatch" data-accent="green"  role="radio" title="Grønn"></button>
                    <button type="button" class="swatch" data-accent="purple" role="radio" title="Lilla"></button>
                    <button type="button" class="swatch" data-accent="orange" role="radio" title="Oransje"></button>
                </div>
                <div class="seg" id="modeSeg" role="radiogroup" aria-label="Lys eller mørk modus">
                    <button type="button" class="seg__btn" data-mode="system" role="radio" title="Følg enheten"><i class="fas fa-circle-half-stroke"></i> Auto</button>
                    <button type="button" class="seg__btn" data-mode="light"  role="radio"><i class="fas fa-sun"></i> Lys</button>
                    <button type="button" class="seg__btn" data-mode="dark"   role="radio"><i class="fas fa-moon"></i> Mørk</button>
                </div>
            </div>
        </aside>
        <div class="sidebar__backdrop" id="backdrop"></div>

        <div class="app">
            <!-- Header -->
            <header class="header">
                <button type="button" class="header__menu" id="menuBtn" title="Åpne meny">
                    <i class="fas fa-bars"></i>
                </button>
                <div class="header__icon">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="header__info">
                    <h1 class="header__title" id="agentTitle"><?= htmlspecialchars(AGENT_NAME) ?></h1>
                    <p class="header__subtitle" id="agentSubtitle"><?= htmlspecialchars(AGENT_DESCRIPTION) ?></p>
                </div>
                <div class="header__status">
                    <span class="header__status-dot"></span>
                    Online
                </div>
                <a href="logout.php" class="header__logout" title="Logg ut">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </header>

            <!-- Verktøylinje: velg agent, juster temperatur, start ny samtale -->
            <div class="toolbar">
                <label class="toolbar__group">
                    <span class="toolbar__label">Agent</span>
                    <select id="agentSelect" class="toolbar__select">
                        <?php foreach ($AGENTS as $key => $a): ?>
                            <option value="<?= htmlspecialchars($key) ?>" <?= $key === DEFAULT_AGENT ? 'selected' : '' ?>>
                                <?= htmlspecialchars($a['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label class="toolbar__group toolbar__group--grow">
                    <span class="toolbar__label">Temperatur: <strong id="tempValue">0.7</strong></span>
                    <input type="range" id="tempSlider" class="toolbar__slider"
                           min="<?= TEMPERATURE_MIN ?>" max="<?= TEMPERATURE_MAX ?>" step="0.1" value="0.7">
                </label>
                <button type="button" class="toolbar__button js-new-chat" title="Start en ny samtale">
                    <i class="fas fa-rotate-right"></i> Ny samtale
                </button>
            </div>

            <!-- Chat Container -->
            <div class="chat" id="chat">
                <!-- Velkomstmelding (innholdet bygges av JavaScript ut fra valgt agent) -->
                <div class="welcome" id="welcome"></div>
            </div>

            <!-- Input Area -->
            <div class="input-area">
                <form class="input-area__form" id="chatForm">
                    <div class="input-area__input-wrapper">
                        <textarea
                            class="input-area__input"
                            id="messageInput"
                            placeholder=""
                            rows="1"
                            autofocus
                        ></textarea>
                    </div>
                    <button type="submit" class="input-area__button" id="sendButton">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </form>
                <p class="input-area__hint">
                    Trykk Enter for å sende • Shift+Enter for ny linje
                </p>
            </div>
        </div>
    </div>

    <script>
        // ============================================
        // CHAT JAVASCRIPT
        // ============================================

        const chat = document.getElementById('chat');
        const chatForm = document.getElementById('chatForm');
        const messageInput = document.getElementById('messageInput');
        const sendButton = document.getElementById('sendButton');
        const welcome = document.getElementById('welcome');

        // Alle agentene (fra agents.php). Systemprompten sendes IKKE til nettleseren.
        const AGENTS = <?= json_encode(
            array_map(fn($a) => [
                'name' => $a['name'],
                'description' => $a['description'],
                'temperature' => $a['temperature'],
                'welcome' => $a['welcome'],
                'placeholder' => $a['placeholder'],
                'suggestions' => $a['suggestions'],
            ], $AGENTS),
            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
        ) ?>;
        const DEFAULT_AGENT = <?= json_encode(DEFAULT_AGENT) ?>;

        const agentSelect = document.getElementById('agentSelect');
        const tempSlider = document.getElementById('tempSlider');
        const tempValue = document.getElementById('tempValue');
        const agentTitle = document.getElementById('agentTitle');
        const agentSubtitle = document.getElementById('agentSubtitle');
        const chatList = document.getElementById('chatList');

        // ---------- Lagring i nettleseren (localStorage) ----------
        // Historikken lagres bare i DENNE nettleseren på DENNE maskinen.
        // Den sendes ikke til serveren. Slettes du nettleserdata, forsvinner den.
        // Vil du flytte historikk til en annen maskin: bruk Eksporter / Importer i sidemenyen.
        const CHATS_KEY = 'katta_chats_v1';
        const MAX_CHATS = 50;   // eldste samtaler slettes når det blir flere

        // Fungerer localStorage her? (Ikke i f.eks. noen private moduser)
        const storageOk = (() => {
            try { localStorage.setItem('__t', '1'); localStorage.removeItem('__t'); return true; }
            catch (e) { return false; }
        })();

        // Gjør om det som ligger lagret til trygge, ryddige samtaler
        // (tåler skadet data og filer som er importert utenfra)
        function sanitizeChats(list) {
            if (!Array.isArray(list)) return [];
            return list
                .filter(c => c && typeof c.id === 'string' && Array.isArray(c.messages))
                .map(c => ({
                    id: c.id,
                    agent: typeof c.agent === 'string' ? c.agent : DEFAULT_AGENT,
                    temperature: Number.isFinite(+c.temperature) ? +c.temperature : 0.7,
                    title: String(c.title || 'Ny samtale').slice(0, 80),
                    created: +c.created || Date.now(),
                    updated: +c.updated || Date.now(),
                    messages: c.messages
                        .filter(m => m && (m.role === 'user' || m.role === 'assistant') && typeof m.content === 'string')
                        .map(m => ({ role: m.role, content: m.content,
                                     meta: typeof m.meta === 'string' ? m.meta : undefined }))
                }));
        }

        function readChats() {
            try { return sanitizeChats(JSON.parse(localStorage.getItem(CHATS_KEY) || '[]')); }
            catch (e) { return []; }
        }

        // Skriv til localStorage. Er det fullt (ca. 5 MB), slettes eldste samtale og vi prøver igjen.
        function writeChats() {
            if (!storageOk) return false;
            for (;;) {
                try { localStorage.setItem(CHATS_KEY, JSON.stringify(chats)); return true; }
                catch (e) {
                    let i = chats.length - 1;
                    if (chats[i] === current) i--;        // aldri slett samtalen du holder på med
                    if (i < 0) return false;
                    chats.splice(i, 1);
                }
            }
        }

        let chats = readChats();     // alle lagrede samtaler
        let current = null;          // samtalen som er åpen nå (null = ny, tom samtale)
        let busy = false;            // true mens vi venter på svar fra AI

        const storageWarning = document.getElementById('storageWarning');
        storageWarning.hidden = storageOk;

        function persist() {
            chats.sort((a, b) => b.updated - a.updated);
            if (chats.length > MAX_CHATS) chats.length = MAX_CHATS;
            if (!writeChats() && storageOk) storageWarning.hidden = false;
        }

        // Samme samtale åpen i flere faner: hold dem i synk
        window.addEventListener('storage', e => {
            if (e.key !== CHATS_KEY || busy) return;
            chats = readChats();
            if (current) {
                const same = chats.find(c => c.id === current.id);
                if (!same) { newConversation(); return; }                 // slettet i annen fane
                if (same.messages.length !== current.messages.length) { openChat(same.id); return; }
                current = same;
            }
            renderList();
        });

        // ---------- Eksporter / importer ----------
        document.getElementById('exportBtn').addEventListener('click', () => {
            if (!chats.length) { alert('Ingen samtaler å eksportere ennå.'); return; }
            const data = { app: 'katta-ai-chat', version: 1, exported: new Date().toISOString(), chats: chats };
            const a = document.createElement('a');
            a.href = URL.createObjectURL(new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' }));
            a.download = 'katta-samtaler-' + new Date().toISOString().slice(0, 10) + '.json';
            document.body.appendChild(a);
            a.click();
            a.remove();
            setTimeout(() => URL.revokeObjectURL(a.href), 1000);
        });
        const importFile = document.getElementById('importFile');
        document.getElementById('importBtn').addEventListener('click', () => importFile.click());
        importFile.addEventListener('change', () => {
            const file = importFile.files[0];
            importFile.value = '';
            if (!file) return;
            const reader = new FileReader();
            reader.onload = () => {
                try {
                    const data = JSON.parse(reader.result);
                    const incoming = sanitizeChats(Array.isArray(data) ? data : data.chats);
                    const known = new Set(chats.map(c => c.id));
                    const fresh = incoming.filter(c => !known.has(c.id));
                    chats = chats.concat(fresh);
                    persist();
                    renderList();
                    alert(fresh.length + ' nye samtaler importert.');
                } catch (e) { alert('Kunne ikke lese filen. Er det en eksportfil fra Katta AI Chat?'); }
            };
            reader.readAsText(file);
        });

        // ---------- Hjelpefunksjoner ----------
        function escapeHtml(t) {
            return String(t).replace(/&/g, '&amp;').replace(/</g, '&lt;')
                            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        // ---------- Fargetema ----------
        // Selve temaet håndteres av js/theme.js (delt med login og dokumentasjon).
        // Her oppdaterer vi bare knappene i sidemenyen.
        function renderThemeUI() {
            const t = KattaTheme.get();
            document.querySelectorAll('.swatch').forEach(s => {
                const on = s.dataset.accent === t.accent;
                s.classList.toggle('swatch--active', on);
                s.setAttribute('aria-checked', on);
            });
            document.querySelectorAll('.seg__btn').forEach(b => {
                const on = b.dataset.mode === t.mode;
                b.classList.toggle('seg__btn--active', on);
                b.setAttribute('aria-checked', on);
            });
        }
        KattaTheme.onChange(renderThemeUI);
        document.getElementById('swatches').addEventListener('click', e => {
            const s = e.target.closest('.swatch');
            if (s) KattaTheme.set({ accent: s.dataset.accent });
        });
        document.getElementById('modeSeg').addEventListener('click', e => {
            const b = e.target.closest('.seg__btn');
            if (b) KattaTheme.set({ mode: b.dataset.mode });
        });

        // ---------- Sidemeny på mobil ----------
        function setSidebar(open) { document.body.classList.toggle('sidebar-open', open); }
        document.getElementById('menuBtn').addEventListener('click', () => setSidebar(true));
        document.getElementById('sidebarClose').addEventListener('click', () => setSidebar(false));
        document.getElementById('backdrop').addEventListener('click', () => setSidebar(false));

        // ---------- Agent og velkomstskjerm ----------
        function renderWelcome() {
            const a = AGENTS[agentSelect.value];
            welcome.innerHTML = `
                <div class="welcome__icon"><i class="fas fa-robot"></i></div>
                <h2 class="welcome__title">Hei! Jeg er ${escapeHtml(a.name)} 👋</h2>
                <p class="welcome__text">${escapeHtml(a.welcome)}</p>
                <div class="welcome__suggestions">
                    ${a.suggestions.map(s => `
                        <button type="button" class="welcome__suggestion" data-message="${escapeHtml(s[1])}">
                            ${escapeHtml(s[0])}
                        </button>`).join('')}
                </div>`;
        }

        // Oppdater siden for valgt agent (navn, beskrivelse, temperatur, hjelpetekst)
        function applyAgent() {
            const a = AGENTS[agentSelect.value];
            agentTitle.textContent = a.name;
            agentSubtitle.textContent = a.description;
            document.title = a.name + ' - AI Chat';
            messageInput.placeholder = a.placeholder;
            tempSlider.value = a.temperature;
            tempValue.textContent = Number(a.temperature).toFixed(1);
            renderWelcome();
        }

        tempSlider.addEventListener('input', function() {
            tempValue.textContent = Number(this.value).toFixed(1);
        });

        // ---------- Samtaler (historikk) ----------
        // Start en ny, tom samtale
        function newConversation() {
            current = null;
            chat.querySelectorAll('.message, .typing, .error').forEach(el => el.remove());
            welcome.style.display = '';
            renderWelcome();
            renderList();
            setSidebar(false);
            messageInput.focus();
        }
        document.querySelectorAll('.js-new-chat').forEach(b => b.addEventListener('click', newConversation));

        // Bytte agent = ny samtale (ellers ville den nye agenten "arve" den gamle samtalen)
        agentSelect.addEventListener('change', function() {
            applyAgent();
            if (current) newConversation();
        });

        // Åpne en gammel samtale og fortsett der du slapp
        function openChat(id) {
            const c = chats.find(x => x.id === id);
            if (!c) return;
            current = c;
            agentSelect.value = AGENTS[c.agent] ? c.agent : DEFAULT_AGENT;
            applyAgent();
            tempSlider.value = c.temperature;
            tempValue.textContent = Number(c.temperature).toFixed(1);

            chat.querySelectorAll('.message, .typing, .error').forEach(el => el.remove());
            welcome.style.display = 'none';
            c.messages.forEach(m => addMessage(m.content, m.role === 'user' ? 'user' : 'ai', m.meta));
            renderList();
            setSidebar(false);
            messageInput.focus();
        }

        function deleteChat(id) {
            if (!confirm('Slette denne samtalen?')) return;
            chats = chats.filter(c => c.id !== id);
            persist();
            if (current && current.id === id) newConversation(); else renderList();
        }

        document.getElementById('clearAllBtn').addEventListener('click', () => {
            if (!chats.length || !confirm('Slette ALL samtalehistorikk?')) return;
            chats = [];
            persist();
            newConversation();
        });

        // Tegn listen over samtaler i sidemenyen
        function renderList() {
            chatList.innerHTML = '';
            if (!chats.length) {
                chatList.innerHTML = '<li class="sidebar__empty">Ingen samtaler ennå</li>';
                return;
            }
            chats.slice().sort((a, b) => b.updated - a.updated).forEach(c => {
                const li = document.createElement('li');
                li.className = 'sidebar__item' + (current && current.id === c.id ? ' sidebar__item--active' : '');

                const open = document.createElement('button');
                open.type = 'button';
                open.className = 'sidebar__open';
                const title = document.createElement('span');
                title.className = 'sidebar__item-title';
                title.textContent = c.title || 'Ny samtale';
                const meta = document.createElement('span');
                meta.className = 'sidebar__item-meta';
                const agentName = AGENTS[c.agent] ? AGENTS[c.agent].name : c.agent;
                meta.textContent = agentName + ' · ' +
                    new Date(c.updated).toLocaleDateString('nb-NO', { day: 'numeric', month: 'short' });
                open.append(title, meta);
                open.addEventListener('click', () => openChat(c.id));

                const del = document.createElement('button');
                del.type = 'button';
                del.className = 'sidebar__delete';
                del.title = 'Slett samtale';
                del.innerHTML = '<i class="fas fa-trash"></i>';
                del.addEventListener('click', () => deleteChat(c.id));

                li.append(open, del);
                chatList.appendChild(li);
            });
        }

        // ---------- Skriving og sending ----------
        // Auto-resize textarea
        messageInput.addEventListener('input', function() {
            this.style.height = 'auto';
            this.style.height = Math.min(this.scrollHeight, 150) + 'px';
        });

        // Håndter Enter-tast
        messageInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                chatForm.requestSubmit();
            }
        });

        // Håndter forslags-knapper (knappene lages på nytt, derfor lytter vi på velkomstboksen)
        welcome.addEventListener('click', function(e) {
            const btn = e.target.closest('.welcome__suggestion');
            if (!btn) return;
            messageInput.value = btn.dataset.message;
            chatForm.requestSubmit();
        });

        // Håndter skjema-innsending
        chatForm.addEventListener('submit', async function(e) {
            e.preventDefault();

            const message = messageInput.value.trim();
            if (!message || busy) return;

            chat.querySelectorAll('.error').forEach(el => el.remove());

            // Lag en ny samtale ved første melding
            if (!current) {
                current = {
                    id: Date.now().toString(36) + Math.random().toString(36).slice(2, 6),
                    agent: agentSelect.value,
                    temperature: parseFloat(tempSlider.value),
                    title: message.length > 45 ? message.slice(0, 45) + '…' : message,
                    created: Date.now(),
                    updated: Date.now(),
                    messages: []
                };
                chats.unshift(current);
            }
            // Husk HVILKEN samtale dette gjelder. Bytter du samtale mens vi venter på svar,
            // havner svaret likevel i riktig samtale.
            const target = current;
            target.agent = agentSelect.value;
            target.temperature = parseFloat(tempSlider.value);

            // Vis brukermeldingen og lagre den med en gang (så den ikke forsvinner ved oppdatering)
            welcome.style.display = 'none';
            addMessage(message, 'user');
            target.messages.push({ role: 'user', content: message });
            target.updated = Date.now();
            persist();
            renderList();

            messageInput.value = '';
            messageInput.style.height = 'auto';

            const typingIndicator = showTypingIndicator();
            busy = true;
            setInputState(false);

            // De 10 meldingene FØR den nye (den nye sendes i 'message')
            const history = target.messages.slice(-11, -1).map(m => ({ role: m.role, content: m.content }));

            let reply = null, errorText = null;
            try {
                const response = await fetch('api/chat.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        message: message,
                        agent: target.agent,
                        temperature: target.temperature,
                        history: history
                    })
                });
                const data = await response.json();
                if (data.error) errorText = data.error; else reply = data;
            } catch (error) {
                errorText = 'Kunne ikke koble til serveren. Prøv igjen senere.';
            }

            typingIndicator.remove();
            busy = false;

            if (reply) {
                const meta = reply.agent + ' · temperatur ' + Number(reply.temperature).toFixed(1);
                target.messages.push({ role: 'assistant', content: reply.response, meta: meta });
                target.updated = Date.now();
                persist();
                if (current === target) addMessage(reply.response, 'ai', meta);
            } else {
                failSend(target, message, errorText);
            }

            renderList();
            setInputState(true);
            if (current === target) messageInput.focus();
        });

        // Noe gikk galt: fjern meldingen igjen og legg teksten tilbake i skrivefeltet,
        // så du kan prøve på nytt uten å skrive alt om igjen.
        function failSend(target, message, errorText) {
            target.messages.pop();
            const stillOpen = current === target;
            const empty = !target.messages.length;       // samtalen ble aldri startet
            if (empty) {
                chats = chats.filter(c => c !== target);
                if (stillOpen) current = null;
            }
            persist();
            if (!stillOpen) return;
            const bubbles = chat.querySelectorAll('.message--user');
            if (bubbles.length) bubbles[bubbles.length - 1].remove();
            if (empty) welcome.style.display = '';
            messageInput.value = message;
            showError(errorText);
        }

        // ---------- Visning av meldinger ----------
        // Legg til melding i chat
        function addMessage(content, type, meta) {
            const messageDiv = document.createElement('div');
            messageDiv.className = `message message--${type}`;

            const avatar = document.createElement('div');
            avatar.className = 'message__avatar';
            avatar.innerHTML = type === 'user'
                ? '<i class="fas fa-user"></i>'
                : '<i class="fas fa-robot"></i>';

            const contentDiv = document.createElement('div');
            contentDiv.className = 'message__content';
            contentDiv.innerHTML = formatMessage(content);

            // Liten merkelapp under AI-svar: hvilken agent og temperatur som ble brukt
            if (meta) {
                const metaDiv = document.createElement('div');
                metaDiv.className = 'message__meta';
                metaDiv.textContent = meta;
                contentDiv.appendChild(metaDiv);
            }

            messageDiv.appendChild(avatar);
            messageDiv.appendChild(contentDiv);

            chat.appendChild(messageDiv);
            scrollToBottom();
        }

        // Formater melding (enkel markdown-støtte)
        function formatMessage(text) {
            // Escape HTML
            text = text.replace(/&/g, '&amp;')
                       .replace(/</g, '&lt;')
                       .replace(/>/g, '&gt;');

            // Kodeblokker
            text = text.replace(/```(\w*)\n?([\s\S]*?)```/g, '<pre><code>$2</code></pre>');

            // Inline kode
            text = text.replace(/`([^`]+)`/g, '<code>$1</code>');

            // Fet tekst
            text = text.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');

            // Kursiv
            text = text.replace(/\*([^*]+)\*/g, '<em>$1</em>');

            // Linjeskift til <br> og avsnitt
            text = text.split('\n\n').map(p => `<p>${p.replace(/\n/g, '<br>')}</p>`).join('');

            return text;
        }

        // Vis typing-indikator
        function showTypingIndicator() {
            const typing = document.createElement('div');
            typing.className = 'typing';
            typing.innerHTML = `
                <div class="typing__avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="typing__dots">
                    <span class="typing__dot"></span>
                    <span class="typing__dot"></span>
                    <span class="typing__dot"></span>
                </div>
            `;
            chat.appendChild(typing);
            scrollToBottom();
            return typing;
        }

        // Vis feilmelding
        function showError(message) {
            const errorDiv = document.createElement('div');
            errorDiv.className = 'error';
            errorDiv.innerHTML = `<i class="fas fa-exclamation-circle"></i> ${escapeHtml(message)}`;
            chat.appendChild(errorDiv);
            scrollToBottom();
        }

        // Aktiver/deaktiver input
        function setInputState(enabled) {
            messageInput.disabled = !enabled;
            sendButton.disabled = !enabled;
        }

        // Scroll til bunnen av chat
        function scrollToBottom() {
            chat.scrollTop = chat.scrollHeight;
        }

        // ---------- Start ----------
        agentSelect.value = DEFAULT_AGENT;
        applyAgent();
        renderThemeUI();
        renderList();
    </script>
</body>
</html>
