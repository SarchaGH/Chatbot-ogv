
# Katta AI Chat

A small PHP chatbot with multiple AI agents, built for a school project. The browser talks to a PHP backend, and the backend talks to the OpenAI API. Each agent has its own name, system prompt and default temperature.

## Features

- **Multiple agents:** SpillUtvikler (game dev), Historieveileder (history), IT-support and Reiseguiden (travel). Switch agent from the toolbar.
- **Adjustable temperature:** a slider from 0.0 (precise) to 1.0 (creative). Each answer shows which agent and temperature produced it, which is handy when testing.
- **Chat history in the browser:** conversations are saved in `localStorage`, up to 50 chats. Messages are saved as soon as you send them. There is Export / Import (JSON) for backups and moving history between machines.
- **Themes:** five accent colours and Auto / Light / Dark mode. The choice is remembered and shared by the chat, login and docs pages.
- **Login:** a simple username and password protects the chat and the API, so strangers cannot spend your API credit.
- **Responsive design:** works on desktop and mobile.

## How it works

```
User
  ↓ types a question
Web page (index.php + JavaScript)
  ↓ POST /api/chat.php  (message, agent, temperature, recent history)
PHP backend (api/chat.php)
  ↓ checks login, loads the agent's system prompt, adds the API key
OpenAI API (gpt-4o-mini)
  ↓ generates the answer
PHP backend → JSON → Web page → User
```

The API key only ever exists on the server. The browser never sees it.

## Project structure

```
├── index.php        Chat page (layout + JavaScript)
├── login.php        Login page
├── logout.php       Ends the session
├── auth.php         Username and password, session helpers
├── config.php       Shared settings: model, max tokens, default agent, temperature limits
├── agents.php       All agents: name, description, temperature, system prompt, suggestions
├── secrets.php      Your OpenAI API key (never commit this)
├── api/
│   └── chat.php     Backend: validates the request and calls OpenAI
├── css/
│   ├── theme.css    All colours (palette, accent, light/dark)
│   └── style.css    Layout and components (no hard-coded colours)
├── js/
│   └── theme.js     Remembers accent colour and light/dark mode on every page
├── docs.html        Documentation page
├── .htaccess        Blocks direct access to secrets.php
└── .gitignore       Keeps secrets.php out of Git
```

## Setup

1. Open `secrets.php` and paste your API key between the quotes:
   ```php
   define('OPENAI_API_KEY', 'your-key-here');
   ```
2. Open `auth.php` and change the username and password.
3. Upload **all** files to your web host, including `.htaccess`.
4. Visit the site, log in and start chatting.

