document.addEventListener("DOMContentLoaded", function () {
  const themeToggleButton = document.getElementById("theme-toggle-btn-header");
  const body = document.body;

  const applyInitialTheme = () => {
    const savedTheme = localStorage.getItem("theme") || "dark";
    if (savedTheme === "light") {
      body.classList.add("light-mode");
    }
  };

  if (themeToggleButton) {
    themeToggleButton.addEventListener("click", () => {
      body.classList.toggle("light-mode");
      localStorage.setItem(
        "theme",
        body.classList.contains("light-mode") ? "light" : "dark"
      );
    });
  }

  const colorClasses = [
    "score-color-great",
    "score-color-good",
    "score-color-average",
    "score-color-poor",
    "score-color-muted",
  ];

  const setScoreColor = (element, score) => {
    element.classList.remove(...colorClasses);

    if (score >= 90) element.classList.add("score-color-great");
    else if (score >= 75) element.classList.add("score-color-good");
    else if (score >= 60) element.classList.add("score-color-average");
    else if (score >= 30) element.classList.add("score-color-poor");
    else element.classList.add("score-color-muted");
  };

  const animateValue = (element, start, end, duration, isCircle) => {
    let startTimestamp = null;
    const step = (timestamp) => {
      if (!startTimestamp) startTimestamp = timestamp;
      const progress = Math.min((timestamp - startTimestamp) / duration, 1);
      const currentValue = Math.floor(progress * (end - start) + start);

      if (isCircle) {
        element.style.setProperty("--score-val", currentValue);
        element.querySelector(
          "span"
        ).innerHTML = `${currentValue}<small>/100</small>`;
      } else {
        element.innerHTML = `${currentValue}<span>/100</span>`;
      }

      if (progress < 1) {
        window.requestAnimationFrame(step);
      }
    };
    window.requestAnimationFrame(step);
  };

  const startAnimations = () => {
    const overallScoreEl = document.getElementById("overall-score-display");
    const impactScoreEl = document.getElementById("impact-score-display");
    const clarityScoreEl = document.getElementById("clarity-score-display");
    const atsScoreEl = document.getElementById("ats-score-display");

    if (overallScoreEl) {
      const score = parseInt(overallScoreEl.dataset.score);
      setScoreColor(overallScoreEl, score);
      animateValue(overallScoreEl, 0, score, 1500, false);
    }
    if (impactScoreEl) {
      const score = parseInt(impactScoreEl.dataset.score);
      setScoreColor(impactScoreEl.parentElement, score);
      animateValue(impactScoreEl, 0, score, 1500, true);
    }
    if (clarityScoreEl) {
      const score = parseInt(clarityScoreEl.dataset.score);
      setScoreColor(clarityScoreEl.parentElement, score);
      animateValue(clarityScoreEl, 0, score, 1500, true);
    }
    if (atsScoreEl) {
      const score = parseInt(atsScoreEl.dataset.score);
      setScoreColor(atsScoreEl.parentElement, score);
      animateValue(atsScoreEl, 0, score, 1500, true);
    }
  };

  const chatLog = document.getElementById("chat-log");
  const chatPromptInput = document.getElementById("chat-prompt-input");
  const chatSendBtn = document.getElementById("chat-send-btn");
  const chatAnalysisId = document.getElementById("chat-analysis-id");

  const appendMessage = (content, sender) => {
    let messageElement;
    if (sender === "user") {
      messageElement = document.createElement("div");
      messageElement.className = "chat-bubble user-bubble";
      messageElement.innerHTML = `<p>${content}</p>`;
    } else {
      messageElement = document.createElement("div");
      messageElement.className = "chat-response ai-response";

      messageElement.innerHTML = content;
    }
    chatLog.appendChild(messageElement);
    chatLog.scrollTop = chatLog.scrollHeight;
    return messageElement;
  };

  const typewriter = (element, html, onComplete) => {
    const speed = 10;
    let i = 0;

    element.innerHTML = '<span class="typing-cursor"></span>';
    const cursor = element.querySelector(".typing-cursor");

    function type() {
      if (i < html.length) {
        const char = html.charAt(i);
        let contentToAdd = char;

        if (char === "<") {
          const tagEndIndex = html.indexOf(">", i);
          if (tagEndIndex !== -1) {
            contentToAdd = html.substring(i, tagEndIndex + 1);
            i = tagEndIndex;
          }
        }

        cursor.insertAdjacentHTML("beforebegin", contentToAdd);
        chatLog.scrollTop = chatLog.scrollHeight;

        i++;
        setTimeout(type, speed);
      } else {
        cursor.remove();
        if (onComplete) onComplete();
      }
    }
    type();
  };

  const renderInitialChat = () => {
    if (!chatLog || typeof initialChat === "undefined") return;
    chatLog.innerHTML = "";
    initialChat.forEach((msg) => {
      if (msg.sender === "user") {
        const tempDiv = document.createElement("div");
        tempDiv.innerHTML = msg.message;
        appendMessage(tempDiv.textContent || "", "user");
      } else {
        appendMessage(msg.message, "ai");
      }
    });
  };

  const handleSendChat = async () => {
    const promptText = chatPromptInput.value.trim();
    if (promptText === "" || !chatAnalysisId.value) return;

    appendMessage(promptText, "user");

    const originalPrompt = promptText;
    chatPromptInput.value = "";
    chatPromptInput.style.height = "auto";
    chatSendBtn.disabled = true;

    const thinkingIndicator = appendMessage(
      '<div class="chat-bubble thinking"><p></p></div>',
      "ai"
    );

    try {
      const response = await fetch("php/chat_handler.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({
          prompt: originalPrompt,
          analysis_id: chatAnalysisId.value,
        }),
      });

      thinkingIndicator.remove();

      if (!response.ok) throw new Error(`Server error: ${response.status}`);
      const data = await response.json();

      if (data.reply) {
        const aiMessageElement = appendMessage("", "ai");
        typewriter(aiMessageElement, data.reply, () => {
          chatSendBtn.disabled = false;
        });
      } else {
        appendMessage(
          `<p class="error">${data.error || "An unknown error occurred."}</p>`,
          "ai"
        );
        chatSendBtn.disabled = false;
      }
    } catch (error) {
      if (thinkingIndicator) thinkingIndicator.remove();
      appendMessage(
        `<p class="error">Failed to connect. Please try again.</p>`,
        "ai"
      );
      chatSendBtn.disabled = false;
    }
  };

  if (chatSendBtn) chatSendBtn.addEventListener("click", handleSendChat);
  if (chatPromptInput) {
    chatPromptInput.addEventListener("keydown", (e) => {
      if (e.key === "Enter" && !e.shiftKey) {
        e.preventDefault();
        handleSendChat();
      }
    });
    chatPromptInput.addEventListener("input", () => {
      chatPromptInput.style.height = "auto";
      chatPromptInput.style.height = `${chatPromptInput.scrollHeight}px`;
    });
  }

  const guideIcon = document.getElementById("guide-icon");
  const guideModal = document.getElementById("guide-modal");
  const guideModalClose = document.getElementById("guide-modal-close");

  if (guideIcon && guideModal && guideModalClose) {
    const openModal = () => guideModal.classList.add("active");
    const closeModal = () => guideModal.classList.remove("active");

    guideIcon.addEventListener("click", openModal);
    guideModalClose.addEventListener("click", closeModal);

    guideModal.addEventListener("click", (e) => {
      if (e.target === guideModal) {
        closeModal();
      }
    });
  }

  applyInitialTheme();
  startAnimations();
  renderInitialChat();
});
