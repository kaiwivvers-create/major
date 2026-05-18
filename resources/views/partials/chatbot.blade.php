@if(\App\Models\AppBranding::current()->chatbot_enabled)
<style>
    .chatbot-trigger {
        position: fixed;
        bottom: 24px;
        right: 24px;
        width: 60px;
        height: 60px;
        border-radius: 999px;
        background: linear-gradient(135deg, var(--primary), var(--accent));
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 8px 24px rgba(37, 99, 235, 0.4);
        z-index: 3000;
        transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    }
    .chatbot-trigger:hover {
        transform: scale(1.1);
    }
    .chatbot-window {
        position: fixed;
        bottom: 100px;
        right: 24px;
        width: 380px;
        height: 520px;
        background: #1e293b;
        border: 1px solid var(--border);
        border-radius: 20px;
        display: flex;
        flex-direction: column;
        box-shadow: 0 12px 48px rgba(2, 6, 23, 0.5);
        z-index: 3000;
        overflow: hidden;
        transform: scale(0.8);
        opacity: 0;
        pointer-events: none;
        transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        transform-origin: bottom right;
    }
    .chatbot-window.open {
        transform: scale(1);
        opacity: 1;
        pointer-events: auto;
    }
    .chatbot-header {
        padding: 16px;
        background: rgba(15, 23, 42, 0.95);
        border-bottom: 1px solid var(--border);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .chatbot-header h3 {
        font-size: 1rem;
        margin: 0;
    }
    .chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        display: flex;
        flex-direction: column;
        gap: 12px;
        scrollbar-width: thin;
        scrollbar-color: var(--accent) transparent;
    }
    .message {
        max-width: 85%;
        padding: 10px 14px;
        border-radius: 14px;
        font-size: 0.9rem;
        line-height: 1.5;
    }
    .message.bot {
        align-self: flex-start;
        background: rgba(30, 41, 59, 0.8);
        border: 1px solid var(--border);
        border-bottom-left-radius: 2px;
    }
    .message.user {
        align-self: flex-end;
        background: var(--primary);
        color: white;
        border-bottom-right-radius: 2px;
    }
    .chatbot-input {
        padding: 16px;
        background: rgba(15, 23, 42, 0.95);
        border-top: 1px solid var(--border);
        display: flex;
        gap: 8px;
    }
    .chatbot-input input {
        flex: 1;
        background: rgba(30, 41, 59, 0.6);
        border: 1px solid var(--border);
        border-radius: 10px;
        padding: 8px 12px;
        color: white;
        font-size: 0.9rem;
    }
    .chatbot-input button {
        background: var(--primary);
        border: none;
        border-radius: 10px;
        color: white;
        padding: 8px 16px;
        cursor: pointer;
        font-weight: 600;
    }
    .typing-indicator {
        display: none;
        align-self: flex-start;
        background: rgba(30, 41, 59, 0.8);
        padding: 8px 12px;
        border-radius: 14px;
        font-style: italic;
        font-size: 0.8rem;
        color: var(--muted);
    }
</style>

<div class="chatbot-trigger" id="chatbot-trigger">
    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path></svg>
</div>

<div class="chatbot-window" id="chatbot-window">
    <div class="chatbot-header">
        <div>
            <h3>KIPS Assistant</h3>
            <p style="font-size: 0.75rem; color: var(--muted); margin: 0;">Online</p>
        </div>
        <button id="chatbot-close" style="background: none; border: none; color: var(--muted); cursor: pointer; font-size: 1.5rem;">&times;</button>
    </div>
    <div class="chatbot-messages" id="chatbot-messages">
        <div class="message bot">
            Hello! I'm your KIPS assistant. How can I help you today? You can report an absence or ask about PKL procedures.
        </div>
        <div class="typing-indicator" id="chatbot-typing">Assistant is typing...</div>
    </div>
    <form class="chatbot-input" id="chatbot-form">
        <input type="text" id="chatbot-input" placeholder="Type a message..." autocomplete="off">
        <button type="submit">Send</button>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const trigger = document.getElementById('chatbot-trigger');
        const window = document.getElementById('chatbot-window');
        const close = document.getElementById('chatbot-close');
        const form = document.getElementById('chatbot-form');
        const input = document.getElementById('chatbot-input');
        const messages = document.getElementById('chatbot-messages');
        const typing = document.getElementById('chatbot-typing');

        trigger.addEventListener('click', () => {
            window.classList.toggle('open');
            if (window.classList.contains('open')) {
                input.focus();
            }
        });

        close.addEventListener('click', () => {
            window.classList.remove('open');
        });

        const addMessage = (text, type) => {
            const msg = document.createElement('div');
            msg.className = `message ${type}`;
            msg.textContent = text;
            messages.insertBefore(msg, typing);
            messages.scrollTop = messages.scrollHeight;
        };

        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            const text = input.value.trim();
            if (!text) return;

            addMessage(text, 'user');
            input.value = '';
            typing.style.display = 'block';
            messages.scrollTop = messages.scrollHeight;

            try {
                const response = await fetch('{{ url('/api/chatbot/query') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ message: text })
                });
                
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }

                const result = await response.json();
                typing.style.display = 'none';
                
                if (result && result.message) {
                    addMessage(result.message, 'bot');
                } else {
                    addMessage("Sorry, I encountered an error. Please try again later.", 'bot');
                }
            } catch (error) {
                typing.style.display = 'none';
                addMessage("Connection error. Please check your internet.", 'bot');
            }
        });
    });
</script>

@endif
