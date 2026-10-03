/**
 * MCP Agent IA - Interactive Chat & Batch Publisher
 * Handles: Chat UI, Batch Checklist, Drip-Feed Auto-Publishing
 */
(function() {
    'use strict';

    // --- State ---
    let conversationHistory = [];
    let batchTasks = [];
    let isBatchRunning = false;

    // --- DOM Refs ---
    const chatHistory    = document.getElementById('mcp-chat-history');
    const chatInput      = document.getElementById('mcp-agent-input');
    const sendBtn        = document.getElementById('mcp-send-to-agent');
    const taskListEl     = document.getElementById('mcp-task-list');
    const batchCard      = document.getElementById('mcp-batch-tasks-card');
    const runBatchBtn    = document.getElementById('mcp-run-batch');
    const intervalSelect = document.getElementById('mcp-batch-interval');

    // Config from wp_localize_script
    const REST_BASE  = (typeof mcpAdmin !== 'undefined') ? mcpAdmin.restUrl : '/wp-json/';
    const NONCE      = (typeof mcpAdmin !== 'undefined') ? mcpAdmin.nonce  : '';
    const CHAT_URL   = REST_BASE + 'mcp-listeo/v1/chat';
    const CREATE_URL = REST_BASE + 'mcp-listeo/v1/auto-create';

    // =========================================================
    // CHAT FUNCTIONS
    // =========================================================

    function appendMessage(role, html) {
        const wrap = document.createElement('div');
        wrap.className = 'mcp-message ' + (role === 'agent' ? 'agent-message' : 'user-message');
        wrap.innerHTML = `
            <div class="mcp-avatar">${role === 'agent' ? '🤖' : '👤'}</div>
            <div class="mcp-text">${html}</div>
        `;
        chatHistory.appendChild(wrap);
        chatHistory.scrollTop = chatHistory.scrollHeight;
        return wrap;
    }

    function appendLoadingIndicator() {
        const id = 'loading-' + Date.now();
        const wrap = document.createElement('div');
        wrap.id = id;
        wrap.className = 'mcp-message agent-message';
        wrap.innerHTML = `
            <div class="mcp-avatar">🤖</div>
            <div class="mcp-text mcp-typing">
                <span></span><span></span><span></span>
            </div>
        `;
        chatHistory.appendChild(wrap);
        chatHistory.scrollTop = chatHistory.scrollHeight;
        return id;
    }

    async function sendMessage() {
        const text = chatInput.value.trim();
        if (!text || isBatchRunning) return;

        appendMessage('user', escapeHtml(text));
        chatInput.value = '';
        sendBtn.disabled = true;

        const loadingId = appendLoadingIndicator();

        try {
            const res = await fetch(CHAT_URL, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
                body: JSON.stringify({ message: text, history: conversationHistory })
            });
            const data = await res.json();
            removeElement(loadingId);

            if (!res.ok) {
                appendMessage('agent', '❌ Error: ' + (data.message || 'No se pudo conectar.'));
                return;
            }

            const reply = data.reply || '';
            conversationHistory.push({ role: 'user', text: text });

            // --- Check if AI returned a batch_checklist JSON ---
            const parsed = tryParseBatchChecklist(reply);
            if (parsed) {
                conversationHistory.push({ role: 'agent', text: parsed.message });
                appendMessage('agent', escapeHtml(parsed.message));
                renderBatchChecklist(parsed.tasks);
            } else {
                conversationHistory.push({ role: 'agent', text: reply });
                appendMessage('agent', escapeHtml(reply).replace(/\n/g, '<br>'));
            }

        } catch (err) {
            removeElement(loadingId);
            appendMessage('agent', '❌ Error de red. Revisa la consola del navegador.');
            console.error('[MCP Agent]', err);
        } finally {
            sendBtn.disabled = false;
            chatInput.focus();
        }
    }

    // =========================================================
    // BATCH CHECKLIST FUNCTIONS
    // =========================================================

    function tryParseBatchChecklist(text) {
        // Find JSON block with regex to be more robust
        const match = text.match(/```json\s*([\s\S]*?)\s*```/) || text.match(/({[\s\S]*"type"\s*:\s*"batch_checklist"[\s\S]*})/);
        if (match) {
            try {
                const cleaned = match[1] || match[0];
                const obj = JSON.parse(cleaned.trim());
                if (obj && obj.type === 'batch_checklist' && Array.isArray(obj.tasks)) {
                    return obj;
                }
            } catch(e) { console.error('JSON Parse Error', e); }
        }
        return null;
    }

    function renderBatchChecklist(tasks) {
        batchTasks = tasks.map((t, i) => ({ ...t, id: 'task-' + i, checked: true }));
        taskListEl.innerHTML = `
            <div class="mcp-task-header">
                <input type="checkbox" id="mcp-select-all" checked>
                <label for="mcp-select-all"><strong>Seleccionar Todos</strong></label>
            </div>
            <div id="mcp-task-items-container"></div>
        `;

        const container = taskListEl.querySelector('#mcp-task-items-container');
        batchTasks.forEach(task => {
            const item = document.createElement('div');
            item.className = 'mcp-task-item';
            item.id = 'task-wrap-' + task.id;
            item.innerHTML = `
                <input type="checkbox" id="${task.id}" checked data-taskid="${task.id}" class="mcp-task-check">
                <label for="${task.id}">
                    <span class="mcp-task-name">${escapeHtml(task.title)}</span>
                    <small class="mcp-task-cat">${escapeHtml(getCategoryLabel(task.category))}</small>
                </label>
                <span class="mcp-task-status" id="status-${task.id}"></span>
                <button class="mcp-task-remove" data-taskid="${task.id}" title="Eliminar tarea">✕</button>
            `;
            container.appendChild(item);
        });

        // Select All handler
        const selectAll = document.getElementById('mcp-select-all');
        selectAll.addEventListener('change', (e) => {
            const isChecked = e.target.checked;
            taskListEl.querySelectorAll('.mcp-task-check').forEach(cb => {
                cb.checked = isChecked;
            });
        });

        // Remove button listeners
        taskListEl.querySelectorAll('.mcp-task-remove').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const id = e.currentTarget.dataset.taskid;
                removeTask(id);
            });
        });

        batchCard.style.display = 'block';
        appendMessage('agent', `📋 <strong>${batchTasks.length} sitios</strong> están listos en la lista. Desmarca los que no quieras publicar y presiona <em>"Publicar Seleccionados"</em>.`);
    }

    function removeTask(taskId) {
        batchTasks = batchTasks.filter(t => t.id !== taskId);
        removeElement('task-wrap-' + taskId);
        if (batchTasks.length === 0) {
            batchCard.style.display = 'none';
            appendMessage('agent', 'Tu lista está vacía. Dime qué otro contenido quieres preparar.');
        }
    }

    // =========================================================
    // DRIP-FEED BATCH PUBLISHER
    // =========================================================

    async function runBatchPublisher() {
        if (isBatchRunning) return;

        const selected = batchTasks.filter(t => {
            const cb = document.getElementById(t.id);
            return cb && cb.checked;
        });

        if (selected.length === 0) {
            appendMessage('agent', '⚠️ No hay tareas seleccionadas. Marca al menos una para continuar.');
            return;
        }

        const delaySeconds = parseInt(intervalSelect.value, 10) || 60;
        isBatchRunning = true;
        runBatchBtn.disabled = true;
        runBatchBtn.textContent = 'Publicando...';

        appendMessage('agent', `🚀 Iniciando publicación de <strong>${selected.length}</strong> sitios con un intervalo de <strong>${delaySeconds}s</strong> entre cada uno...`);

        let successCount = 0;
        let failCount = 0;

        for (let i = 0; i < selected.length; i++) {
            const task = selected[i];
            const statusEl = document.getElementById('status-' + task.id);
            const taskWrap = document.getElementById('task-wrap-' + task.id);

            if (statusEl) statusEl.innerHTML = '<span class="mcp-status-processing">⏳</span>';
            if (taskWrap) taskWrap.classList.add('processing');

            appendMessage('agent', `⚙️ [${i + 1}/${selected.length}] Generando y publicando: <strong>${escapeHtml(task.title)}</strong>...`);

            try {
                const res = await fetch(CREATE_URL, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': NONCE },
                    body: JSON.stringify({ 
                        title: task.title, 
                        category: task.category,
                        region: task.region || '' 
                    })
                });
                const result = await res.json();

                if (res.ok && result.id) {
                    successCount++;
                    if (statusEl) statusEl.innerHTML = '<span class="mcp-status-done">✅</span>';
                    if (taskWrap) taskWrap.classList.replace('processing', 'done');
                    appendMessage('agent', `✅ Publicado: <a href="${result.link}" target="_blank">${escapeHtml(task.title)}</a> (ID: ${result.id})`);
                } else {
                    failCount++;
                    if (statusEl) statusEl.innerHTML = '<span class="mcp-status-error">❌</span>';
                    if (taskWrap) taskWrap.classList.replace('processing', 'failed');
                    appendMessage('agent', `❌ Falló: <strong>${escapeHtml(task.title)}</strong> — ${result.message || 'Error desconocido'}`);
                }
            } catch (err) {
                failCount++;
                if (statusEl) statusEl.innerHTML = '<span class="mcp-status-error">❌</span>';
                appendMessage('agent', `❌ Error de red al publicar: <strong>${escapeHtml(task.title)}</strong>`);
                console.error('[MCP Batch]', err);
            }

            // Wait before next item (except after the last one)
            if (i < selected.length - 1) {
                appendMessage('agent', `⏸ Esperando ${delaySeconds}s para no saturar la API...`);
                await sleep(delaySeconds * 1000);
            }
        }

        // Final summary
        isBatchRunning = false;
        runBatchBtn.disabled = false;
        runBatchBtn.textContent = 'Publicar Seleccionados';

        appendMessage('agent', `
            <strong>🏁 Lote Finalizado</strong><br>
            ✅ Publicados: <strong>${successCount}</strong><br>
            ❌ Fallidos: <strong>${failCount}</strong><br>
            ${failCount > 0 ? '⚠️ Revisa los ítems fallidos y reintenta si es necesario.' : '¡Todo perfecto! Los listados ya están en línea.'}
        `);
    }

    // =========================================================
    // UTILITY FUNCTIONS
    // =========================================================

    function escapeHtml(str) {
        if (!str) return '';
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    function removeElement(id) {
        const el = document.getElementById(id);
        if (el) el.remove();
    }

    function sleep(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }

    function getCategoryLabel(cat) {
        const labels = {
            city: '🏙️ Ciudad',
            hotel: '🏨 Hotel',
            restaurant: '🍽️ Restaurante',
            gastrobar: '🍹 Gastrobar',
            glamping: '⛺ Glamping',
            site: '📍 Sitio Turístico',
        };
        return labels[cat] || cat;
    }

    // =========================================================
    // EVENT LISTENERS
    // =========================================================

    if (sendBtn) {
        sendBtn.addEventListener('click', sendMessage);
    }

    if (chatInput) {
        chatInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });
    }

    if (runBatchBtn) {
        runBatchBtn.addEventListener('click', runBatchPublisher);
    }

})();
