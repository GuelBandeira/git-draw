# 🎨 GitHub Contribution Art Generator

[![PHP](https://img.shields.io/badge/PHP-7.4%2B-777BB4?style=flat&logo=php&logoColor=white)](https://www.php.net/)
[![GitHub API](https://img.shields.io/badge/GitHub-API-181717?style=flat&logo=github&logoColor=white)](https://docs.github.com/en/rest)
[![License](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

A web-based tool that allows you to "draw" on your GitHub contribution graph by generating backdated commits. It features a real-time preview, commit intensity control, and a sleek user interface with dark mode support.


![Application Screenshot](/assets/main_page.png)


## ✨ Features

- **Real-time Preview:** See exactly how your text/art will look on the grid before committing.
- **Intensity Control:** Choose from 4 levels of commit shades (Low to Max) to create depth in your drawings.
- **Smart Date Calculation:** Automatically calculates dates based on the selected year and grid position.
- **Optimized Backend:** Uses commit chaining and single-ref updates to generate thousands of commits in seconds.
- **Live Progress:** Real-time progress bar with "loading screen tips" via Server-Sent Events (SSE).
- **Internationalization:** Native support for **English** (🇺🇸) and **Portuguese** (🇧🇷) with auto-detection.
- **Dark/Light Mode:** Automatically adapts to your system theme or user preference.

## 🚀 Prerequisites

- PHP 7.4 or higher.
- A Web Server (Apache/Nginx) or PHP built-in server.
- A GitHub Account.
- A GitHub **OAuth Application** (Client ID and Client Secret).

## 🛠️ Installation

1. **Clone the repository:**
   git clone https://github.com/your-username/github-art-generator.git
   cd github-art-generator

2. **Configure GitHub OAuth:**
   - Go to [GitHub Developer Settings](https://github.com/settings/developers).
   - Click **"New OAuth App"**.
   - **Application Name:** GitHub Art Generator (or your choice).
   - **Homepage URL:** `http://localhost:8000` (or your production URL).
   - **Authorization callback URL:** `http://localhost:8000/auth.php`.
   - Copy the **Client ID** and generate a **Client Secret**.

3. **Setup Configuration:**
   Create a `config.php` file in the root directory (if not already present) to store your credentials (`GITHUB_CLIENT_ID`, `GITHUB_CLIENT_SECRET`, and `REDIRECT_URI`).


   ```php
   <?php
   define('GITHUB_CLIENT_ID', 'YOUR_CLIENT_ID_HERE');
   define('GITHUB_CLIENT_SECRET', 'YOUR_CLIENT_SECRET_HERE');
   define('REDIRECT_URI', 'http://localhost:8000/auth.php');
   ?>
   ```

4. **Run the application:**
   You can use the built-in PHP server for testing:
   php -S localhost:8000

5. **Access:**
   Open your browser and navigate to `http://localhost:8000`.

## 📖 How to Use

1. **Connect:** Click the "Connect with GitHub" button to authorize the app.
2. **Configure:**
   - **Message:** Type the text you want to appear on your profile (Max 10 chars).
   - **Repository:** Enter the name of the repository where commits will be created (e.g., `your-user/art-repo`). *Note: It's recommended to use a dedicated empty repository for this.*
   - **Year:** Select the year you want to paint.
   - **Intensity:** Select how dark the green squares should be (Level 1-4).
3. **Generate:** Click "Generate Commits" and watch the magic happen!

## ⚠️ Disclaimer

This tool is created for **educational and aesthetic purposes only**.
- It manipulates your contribution graph by creating backdated commits.
- While this is generally harmless, avoid abusing the GitHub API limits.
- It is highly recommended to use a **private** or a **dedicated public repository** for these commits so you don't clutter your actual project history.

## 🤝 Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

1. Fork the project.
2. Create your feature branch (`git checkout -b feature/AmazingFeature`).
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`).
4. Push to the branch (`git push origin feature/AmazingFeature`).
5. Open a Pull Request.

## 📄 License

Distributed under the MIT License. See `LICENSE` for more information.

---

Made with 💚 by [Guel Bandeira](https://github.com/GuelBandeira)