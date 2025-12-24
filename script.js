const CHAT_HISTORY_KEY = 'astrindo_chat_history';
const LAST_SESSION_KEY = 'astrindo_last_session';

let chatSessionId = localStorage.getItem(LAST_SESSION_KEY) || Date.now().toString();
let chatHistory = JSON.parse(localStorage.getItem(CHAT_HISTORY_KEY) || '{}');

let deleteTargetSessionId = null;
let renameTargetSessionId = null;

// ---------- Sidebar ----------
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('hidden');
  document.getElementById('mainContent').classList.toggle('collapsed');
}
window.toggleSidebar = toggleSidebar;

// ---------- Enter to send ----------
document.getElementById("userInput").addEventListener("keydown", function (e) {
  const isRenameModalOpen = document.getElementById("renameModal").style.display === "flex";
  const isDeleteModalOpen = document.getElementById("deleteModal").style.display === "flex";
  if (e.key === "Enter" && !isRenameModalOpen && !isDeleteModalOpen) {
    e.preventDefault();
    sendMessage();
  }
});

// ---------- Send message ----------
async function sendMessage() {
  const inputField = document.getElementById("userInput");
  const message = inputField.value.trim();
  if (!message) return;

  const last = chatHistory[chatSessionId]?.messages?.at(-1);
  if (last?.sender === 'user' && last?.text === message) return;

  appendMessage("user", message);
  inputField.value = "";
  inputField.focus();

  try {
    const API_URL = `${window.location.origin}/astrindo-chatbot/chat.php`;

    const response = await fetch(API_URL, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ message })
    });

    const data = await response.json();
    console.log("DEBUG response:", data);

    appendMessage("bot", data.message || "No response");

    // ===== CHATGPT-LIKE TITLE LOGIC =====
    const session = chatHistory[chatSessionId] || { title: "", manualTitle: false, messages: [] };
    const currentTitle = (session.title || "").trim();
    const isManual = session.manualTitle === true;

    const GENERIC_TITLES = [
      "general chat",
      "quick question",
      "small talk",
      "casual chat",
      "greeting",
      "hello",
      "help",
      "need help"
    ];

    const isGeneric =
      currentTitle === "" ||
      GENERIC_TITLES.includes(currentTitle.toLowerCase());

    // Auto-set / upgrade title if:
    // - AI provides title
    // - user hasn't manually renamed
    // - title still empty OR still generic
    if (data.title && !isManual && isGeneric) {
      session.title = String(data.title).trim();
      session.manualTitle = false;
      chatHistory[chatSessionId] = session;
      saveChatHistory(chatSessionId);
    }

  } catch (err) {
    appendMessage("bot", "Error: " + err.message);
  }
}
window.sendMessage = sendMessage;

// ---------- Append message ----------
function appendMessage(sender, text, isHistory = false) {
  const container = document.getElementById("chatContainer");
  const msg = document.createElement("div");
  msg.className = "message " + sender;
  msg.innerHTML = String(text).replace(/\n/g, "<br>");
  container.appendChild(msg);

  msg.scrollIntoView({ behavior: "smooth", block: "end" });

  if (!isHistory) {
    if (!chatHistory[chatSessionId]) {
      chatHistory[chatSessionId] = { title: "", manualTitle: false, messages: [] };
    }
    chatHistory[chatSessionId].messages.push({ sender, text });
    saveChatHistory(chatSessionId);
  }
}

// ---------- Storage ----------
function saveChatHistory(sessionId) {
  localStorage.setItem(CHAT_HISTORY_KEY, JSON.stringify(chatHistory));
  localStorage.setItem(LAST_SESSION_KEY, sessionId);
  renderChatHistoryList();
}

// ---------- Render sidebar ----------
function renderChatHistoryList() {
  const list = document.getElementById("chatHistoryList");
  list.innerHTML = '';

  Object.entries(chatHistory).forEach(([id, session]) => {
    if (!session || (!session.title && (!session.messages || session.messages.length === 0))) return;

    const preview = session.messages?.find(m => m.sender === 'user')?.text?.slice(0, 25) || 'Chat';
    const title = session.title || preview;

    const item = document.createElement("div");
    item.className = "chat-item";

    const label = document.createElement("span");
    label.textContent = title;
    label.title = title;
    label.style.flex = "1";
    label.onclick = () => loadChatSession(id);

    const menuBtn = document.createElement("span");
    menuBtn.className = "chat-options";
    menuBtn.textContent = "•••";
    menuBtn.onclick = (e) => {
      e.stopPropagation();
      toggleContextMenu(id, item);
    };

    item.appendChild(label);
    item.appendChild(menuBtn);
    list.appendChild(item);
  });
}
window.renderChatHistoryList = renderChatHistoryList;

// ---------- Load session ----------
function loadChatSession(sessionId) {
  chatSessionId = sessionId;
  localStorage.setItem(LAST_SESSION_KEY, sessionId);

  const container = document.getElementById("chatContainer");
  container.innerHTML = '';
  (chatHistory[sessionId]?.messages || []).forEach(({ sender, text }) => {
    appendMessage(sender, text, true);
  });
}
window.loadChatSession = loadChatSession;

// ---------- New chat ----------
function startNewChat() {
  chatSessionId = Date.now().toString();
  chatHistory[chatSessionId] = { title: "", manualTitle: false, messages: [] };
  saveChatHistory(chatSessionId);

  document.getElementById("chatContainer").innerHTML =
    '<div class="message bot">👋 Hello! How can I assist you today?</div>';
}
window.startNewChat = startNewChat;

// ---------- Context menu ----------
function toggleContextMenu(sessionId, parentElement) {
  document.querySelectorAll(".context-menu").forEach(el => el.remove());

  const menu = document.createElement("div");
  menu.className = "context-menu";

  const createItem = (text, handler) => {
    const item = document.createElement("div");
    item.textContent = text;
    item.onclick = () => {
      handler();
      menu.remove();
    };
    return item;
  };

  menu.appendChild(createItem("Rename", () => {
    showRenameModal(sessionId, chatHistory[sessionId].title || "this chat");
  }));

  menu.appendChild(createItem("Delete", () => {
    showDeleteModal(sessionId, chatHistory[sessionId].title || "this chat");
  }));

  parentElement.appendChild(menu);
}
window.toggleContextMenu = toggleContextMenu;

// ---------- Delete modal ----------
function showDeleteModal(sessionId, title) {
  deleteTargetSessionId = sessionId;
  document.getElementById("deleteChatName").textContent = title;
  document.getElementById("deleteModal").style.display = "flex";
}
window.showDeleteModal = showDeleteModal;

function closeDeleteModal() {
  deleteTargetSessionId = null;
  document.getElementById("deleteModal").style.display = "none";
}
window.closeDeleteModal = closeDeleteModal;

function confirmDeleteChat() {
  if (deleteTargetSessionId) {
    delete chatHistory[deleteTargetSessionId];
    localStorage.setItem(CHAT_HISTORY_KEY, JSON.stringify(chatHistory));
    renderChatHistoryList();
    if (chatSessionId === deleteTargetSessionId) startNewChat();
  }
  closeDeleteModal();
}
window.confirmDeleteChat = confirmDeleteChat;

// ---------- Rename modal ----------
function showRenameModal(sessionId, currentTitle) {
  renameTargetSessionId = sessionId;
  document.getElementById("renameInput").value = currentTitle;
  document.getElementById("renameModal").style.display = "flex";
}
window.showRenameModal = showRenameModal;

function closeRenameModal() {
  renameTargetSessionId = null;
  document.getElementById("renameModal").style.display = "none";
}
window.closeRenameModal = closeRenameModal;

function confirmRenameChat() {
  const newName = document.getElementById("renameInput").value.trim();
  if (newName && renameTargetSessionId) {
    chatHistory[renameTargetSessionId].title = newName;
    chatHistory[renameTargetSessionId].manualTitle = true; // 🔒 lock title
    saveChatHistory(renameTargetSessionId);
  }
  closeRenameModal();
}
window.confirmRenameChat = confirmRenameChat;

// ---------- Search ----------
function startSearchChat() {
  const input = document.getElementById("searchInput");
  input.style.display = input.style.display === "none" ? "block" : "none";
  input.focus();
  filterChatHistory();
}
window.startSearchChat = startSearchChat;

function filterChatHistory() {
  const keyword = document.getElementById("searchInput").value.toLowerCase();
  const list = document.getElementById("chatHistoryList");
  list.innerHTML = '';

  Object.entries(chatHistory).forEach(([id, session]) => {
    if (!session || !session.title) return;
    if (!session.title.toLowerCase().includes(keyword)) return;

    const item = document.createElement("div");
    item.className = "chat-item";

    const label = document.createElement("span");
    label.textContent = session.title;
    label.onclick = () => loadChatSession(id);

    item.appendChild(label);
    list.appendChild(item);
  });
}
window.filterChatHistory = filterChatHistory;

// ---------- Init ----------
(function init() {
  if (chatHistory[chatSessionId]) {
    loadChatSession(chatSessionId);
  } else {
    startNewChat();
  }
  renderChatHistoryList();
})();
