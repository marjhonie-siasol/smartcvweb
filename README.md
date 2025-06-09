<p align="center">
  <img src="https://i.imgur.com/Pj30rfC.png" alt="Smart CV Logo" width="150">
</p>

<h1 align="center">Smart CV - AI Resume Analyzer</h1>

<p align="center">
  A dynamic, full-stack web application designed to help users improve their resumes with the power of generative AI.
  <br>
  <br>
  <img src="https://i.imgur.com/a6GMYBe.png" alt="Smart CV Analysis Page Screenshot">
</p>

<p align="center">
  <a href="#-key-features"><strong>Key Features</strong></a> ·
  <a href="#-technology-stack"><strong>Tech Stack</strong></a> ·
  <a href="#-setup-and-installation"><strong>Installation</strong></a> ·
  <a href="#-future-improvements"><strong>Future Ideas</strong></a>
</p>

<p align="center">
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP Badge">
  <img src="https://img.shields.io/badge/MySQL-4479A1?style=for-the-badge&logo=mysql&logoColor=white" alt="MySQL Badge">
  <img src="https://img.shields.io/badge/JavaScript-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black" alt="JavaScript Badge">
  <img src="https://img.shields.io/badge/CSS3-1572B6?style=for-the-badge&logo=css3&logoColor=white" alt="CSS3 Badge">
  <img src="https://img.shields.io/badge/Google%20Gemini-4285F4?style=for-the-badge&logo=google&logoColor=white" alt="Gemini Badge">
</p>

---

## ✨ Key Features

-   **Secure User Authentication**: Full sign-up and login system with password hashing.
-   **AI-Powered Resume Analysis**: Integrates with the Google Gemini API to provide an initial score and a professional summary.
-   **Interactive & Stateful AI Chat**: Engage in a contextual conversation with an AI that remembers the entire chat history for intelligent, relevant follow-up advice.
-   **Persistent History**: All analysis sessions and chat conversations are saved to the database, linked to the user's account, and accessible via a clickable history panel.
-   **Polished User Interface**:
    -   Sleek, modern **dark/light mode** toggle that remembers the user's preference via `localStorage`.
    -   Smooth CSS animations for modals, buttons, and interactive elements.
    -   Dynamic UI effects like an **animated score counter** and an AI **"typing" indicator** for a premium feel.
    -   Fully responsive design that works seamlessly on desktop and mobile devices.

---

## 🛠️ Technology Stack

| Category      | Technology                                                                                                                                                                                              |
|---------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| **Frontend**  | `HTML5`, `CSS3` (with CSS Variables), `JavaScript (ES6+)`                                                                                                                                                |
| **Backend**   | `PHP`, `MySQL`                                                                                                                                                                                          |
| **AI**        | **Google Gemini API** (via cURL in PHP)                                                                                                                                                                 |
| **Dev Env**   | `XAMPP` (Apache, MySQL)                                                                                                                                                                                 |

---

## 🚀 Setup and Installation

Follow these steps to get the project running on your local machine.

<details>
<summary><strong>1. Prerequisites</strong></summary>
<br>

-   A local web server environment like **[XAMPP](https://www.apachefriends.org/index.html)** or WAMP.
-   A **[Google AI Studio API Key](https://aistudio.google.com/app/apikey)**.

</details>

<details>
<summary><strong>2. Clone the Repository</strong></summary>
<br>

Clone this project into your XAMPP `htdocs` directory (or your web server's equivalent).

```bash
git clone [your-repository-url] smartcvweb
cd smartcvweb
```

</details>

<details>
<summary><strong>3. Database Setup</strong></summary>
<br>

1.  Start the **Apache** and **MySQL** services in your XAMPP Control Panel.
2.  Open your browser and navigate to `http://localhost/phpmyadmin/`.
3.  Create a new database named `smartcvdb`.
4.  Select `smartcvdb` and go to the **SQL** tab.
5.  Run the three SQL commands below one by one to create the necessary tables.

    **Users Table:**
    ```sql
    CREATE TABLE `users` (
    `id` int(11) NOT NULL,
    `fullname` varchar(255) NOT NULL,
    `email` varchar(255) NOT NULL,
    `password` varchar(255) NOT NULL,
    `created_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ```

    **Analysis History Table:**
    ```sql
    CREATE TABLE `analysis_history` (
    `id` int(11) NOT NULL,
    `user_id` int(11) NOT NULL,
    `user_facing_filename` varchar(255) NOT NULL,
    `unique_filename` varchar(255) NOT NULL,
    `initial_prompt` text NOT NULL,
    `score` int(3) NOT NULL,
    `analysis_details` text DEFAULT NULL,
    `breakdown_table` text DEFAULT NULL,
    `strengths` text DEFAULT NULL,
    `improvements` text DEFAULT NULL,
    `final_thoughts` text DEFAULT NULL,
    `summary` text NOT NULL,
    `analyzed_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ```

    **Chat Messages Table:**
    ```sql
    CREATE TABLE `chat_messages` (
    `id` int(11) NOT NULL,
    `analysis_id` int(11) NOT NULL,
    `sender` enum('user','ai') NOT NULL,
    `message` text NOT NULL,
    `sent_at` timestamp NOT NULL DEFAULT current_timestamp()
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ```
</details>

<details>
<summary><strong>4. Configure API Key</strong></summary>
<br>

> **Warning**
> Your API key is a secret! Do not commit it directly to a public repository. For development, you can add it as described below. For production, use environment variables.

1.  Open the file `php/ai_handler.php`.
2.  Find the line `$apiKey = 'YOUR_API_KEY';`.
3.  Replace `YOUR_API_KEY` with your secret Google Gemini API key.
4.  Do the same for the file `php/chat_handler.php`.

</details>

<details>
<summary><strong>5. Run the Application</strong></summary>
<br>

-   Ensure your XAMPP services are running.
-   Open your web browser and navigate to: **`http://localhost/smartcvweb/`**
-   The application should now be fully functional. Create an account, upload a `.txt` resume, and start analyzing!

</details>

---

## 📂 Project Structure

```
smartcvweb/
    css/
        responsive.css
        style.css
    images/
    js/
        analysis.js
        script.js
    php/
        ai_handler.php
        chat_handler.php
        config.php
        login.php
        logout.php
        signup.php
        upload.php
    uploads/
    vendor/
        composer/
        erusev/
        phpoffice/
        smalot
        symfony
        autoload.php
    .gitignore
    analysis.php
    composer.json
    composer.lock
    index.php
```

---

## 💡 Future Improvements

-   [ ] **Real-time Chat with WebSockets**: Implement WebSockets for an instant chat experience without page reloads.
-   [ ] **PDF/DOCX Parsing**: Integrate server-side libraries like `spatie/pdf-to-text` or `PHPWord` to extract text from `.pdf` and `.docx` files.
-   [ ] **Professional Dashboard**: Enhance the analysis page with more detailed breakdowns, charts, and keyword analysis based on more structured AI responses.
-   [ ] **User Profile Management**: Allow users to change their password or delete their account.

---

<p align="center">
  <em>Developed with ❤️ by Marjhonie.</em>
</p>
