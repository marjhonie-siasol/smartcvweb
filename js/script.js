document.addEventListener("DOMContentLoaded", function () {
  const appContainer = document.getElementById("app-container");
  const isUserLoggedIn = appContainer.dataset.loggedIn === "true";
  let isFileUploaded = false;

  const themeToggleBtn = document.getElementById("theme-toggle-btn");
  const themeToggleIcon = document.querySelector("#theme-toggle-btn img");

  const loginModal = document.getElementById("login-modal");
  const signupModal = document.getElementById("signup-modal");
  const messageModal = document.getElementById("message-modal");
  const modals = document.querySelectorAll(".modal");
  const headerLoginBtn = document.getElementById("header-login-btn");
  const headerSignupBtn = document.getElementById("header-signup-btn");
  const goToSignupLink = document.getElementById("go-to-signup");
  const goToLoginLink = document.getElementById("go-to-login");
  const loginForm = document.getElementById("login-form");
  const signupForm = document.getElementById("signup-form");
  const messageText = document.getElementById("message-text");
  const messageOkBtn = document.getElementById("message-ok-btn");

  const sidebarOpenTrigger = document.getElementById("sidebar-open-trigger");
  const sidebarCloseTrigger = document.getElementById("sidebar-close-trigger");
  const sidebarLoginBtn = document.getElementById("sidebar-login-btn");
  const mainOverlay = document.getElementById("main-overlay");

  const uploadBox = document.getElementById("upload-box");
  const fileInput = document.getElementById("resume-upload-input");
  const fileNameDisplay = document.getElementById("file-name-display");
  const promptInput = document.getElementById("prompt-input");
  const analyzeBtn = document.getElementById("analyze-btn");

  const savedTheme = localStorage.getItem("theme");
  if (savedTheme === "light") {
    document.body.classList.add("light-mode");
    if (themeToggleIcon) themeToggleIcon.src = "images/dark-mode.png";
  } else {
    document.body.classList.remove("light-mode");
    if (themeToggleIcon) themeToggleIcon.src = "images/white-mode.png";
  }

  const showModal = (modal) => {
    if (modal) modal.classList.add("active");
  };
  const hideModal = (modal) => {
    if (modal) modal.classList.remove("active");
  };
  const showMessage = (message) => {
    messageText.innerHTML = message;
    showModal(messageModal);
  };

  const checkLoginBeforeAction = (action) => {
    if (!isUserLoggedIn) {
      showModal(loginModal);
    } else {
      action();
    }
  };

  const updateAnalyzeButtonState = () => {
    if (!analyzeBtn) return;
    if (isFileUploaded && promptInput.value.trim() !== "") {
      analyzeBtn.classList.remove("disabled");
    } else {
      analyzeBtn.classList.add("disabled");
    }
  };

  const handleFile = (file) => {
    const formData = new FormData();
    formData.append("resumeFile", file);
    fetch("php/upload.php", { method: "POST", body: formData })
      .then((response) => response.json())
      .then((data) => {
        if (data.status === "success") {
          fileNameDisplay.textContent = data.fileName;
          const hiddenFilenameInput =
            document.getElementById("hidden-filename");
          if (hiddenFilenameInput) {
            hiddenFilenameInput.value = data.uniqueFileName;
          }
          isFileUploaded = true;
          updateAnalyzeButtonState();
        } else {
          showMessage(data.message.replace(/<br>/g, "\n"));
          isFileUploaded = false;
          fileNameDisplay.textContent = "";
          updateAnalyzeButtonState();
        }
      })
      .catch((error) => {
        showMessage("An error occurred during file upload.");
        console.error("Upload Error:", error);
      });
  };

  const handleAuthAction = (form, url, errorElId) => {
    if (!form) return;
    form.addEventListener("submit", function (e) {
      e.preventDefault();
      const errorEl = document.getElementById(errorElId);
      errorEl.textContent = "";
      const formData = new FormData(this);
      fetch(url, { method: "POST", body: formData })
        .then((response) => response.json())
        .then((data) => {
          if (data.status === "success") {
            window.location.reload();
          } else {
            errorEl.textContent = data.message;
          }
        })
        .catch((error) => {
          errorEl.textContent = "An unexpected error occurred.";
          console.error("Error:", error);
        });
    });
  };

  const toggleSidebar = () =>
    appContainer.classList.toggle("sidebar-collapsed");
  if (window.innerWidth > 1024)
    appContainer.classList.remove("sidebar-collapsed");
  if (sidebarOpenTrigger)
    sidebarOpenTrigger.addEventListener("click", toggleSidebar);
  if (sidebarCloseTrigger)
    sidebarCloseTrigger.addEventListener("click", toggleSidebar);
  if (mainOverlay) mainOverlay.addEventListener("click", toggleSidebar);

  if (headerLoginBtn)
    headerLoginBtn.addEventListener("click", () => showModal(loginModal));
  if (headerSignupBtn)
    headerSignupBtn.addEventListener("click", () => showModal(signupModal));
  if (sidebarLoginBtn) {
    sidebarLoginBtn.addEventListener("click", (e) => {
      e.preventDefault();
      if (
        window.innerWidth <= 768 &&
        !appContainer.classList.contains("sidebar-collapsed")
      ) {
        toggleSidebar();
      }
      showModal(loginModal);
    });
  }
  if (goToSignupLink)
    goToSignupLink.addEventListener("click", (e) => {
      e.preventDefault();
      hideModal(loginModal);
      showModal(signupModal);
    });
  if (goToLoginLink)
    goToLoginLink.addEventListener("click", (e) => {
      e.preventDefault();
      hideModal(signupModal);
      showModal(loginModal);
    });
  modals.forEach((modal) => {
    const closeBtn = modal.querySelector(".close-btn");
    if (closeBtn) closeBtn.addEventListener("click", () => hideModal(modal));
    modal.addEventListener("click", (e) => {
      if (e.target === modal) hideModal(modal);
    });
  });
  if (messageOkBtn)
    messageOkBtn.addEventListener("click", () => hideModal(messageModal));

  handleAuthAction(loginForm, "php/login.php", "login-error");
  handleAuthAction(signupForm, "php/signup.php", "signup-error");

  if (promptInput) {
    promptInput.addEventListener("input", () => {
      promptInput.style.height = "auto";
      promptInput.style.height = promptInput.scrollHeight + "px";
    });
    promptInput.addEventListener("keyup", updateAnalyzeButtonState);
  }

  if (uploadBox) {
    uploadBox.addEventListener("click", () =>
      checkLoginBeforeAction(() => fileInput.click())
    );
    fileInput.addEventListener("change", () => {
      if (fileInput.files.length > 0) handleFile(fileInput.files[0]);
    });
    ["dragenter", "dragover", "dragleave", "drop"].forEach((eventName) => {
      uploadBox.addEventListener(
        eventName,
        (e) => {
          e.preventDefault();
          e.stopPropagation();
        },
        false
      );
    });
    ["dragenter", "dragover"].forEach((eventName) => {
      uploadBox.addEventListener(
        eventName,
        () => uploadBox.classList.add("dragover"),
        false
      );
    });
    ["dragleave", "drop"].forEach((eventName) => {
      uploadBox.addEventListener(
        eventName,
        () => uploadBox.classList.remove("dragover"),
        false
      );
    });
    uploadBox.addEventListener("drop", (e) => {
      checkLoginBeforeAction(() => {
        if (e.dataTransfer.files.length > 0)
          handleFile(e.dataTransfer.files[0]);
      });
    });
  }

  if (analyzeBtn) {
    analyzeBtn.addEventListener("click", (e) => {
      if (analyzeBtn.classList.contains("disabled")) {
        e.preventDefault();
      }
    });
  }

  [promptInput, analyzeBtn, uploadBox]
    .filter((el) => el)
    .forEach((element) => {
      element.addEventListener(
        "click",
        (e) => {
          if (!isUserLoggedIn) {
            e.preventDefault();
            e.stopPropagation();
            showModal(loginModal);
          }
        },
        true
      );
    });

  if (themeToggleBtn) {
    themeToggleBtn.addEventListener("click", () => {
      document.body.classList.toggle("light-mode");
      if (document.body.classList.contains("light-mode")) {
        localStorage.setItem("theme", "light");
        themeToggleIcon.src = "images/dark-mode.png";
      } else {
        localStorage.setItem("theme", "dark");
        themeToggleIcon.src = "images/white-mode.png";
      }
    });
  }

  updateAnalyzeButtonState();
});
