# Google Photos Clone

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.2-red?style=for-the-badge&logo=php">
  <img src="https://img.shields.io/badge/MySQL-8.0-green?style=for-the-badge&logo=mysql">
  <img src="https://img.shields.io/badge/Docker-24.0-yellow?style=for-the-badge&logo=docker">
  <img src="https://img.shields.io/badge/React-18.2-blue?style=for-the-badge&logo=react">
  <img src="https://img.shields.io/badge/NEXT.js-18.2-blue?style=for-the-badge&logo=nextjs">
</p>

## Features

- **User Authentication**: Secure registration, login, and session management
- **Photo Management**: Upload, view, and organize photos in a timeline
- **Album System**: Create and manage photo albums with custom covers
- **Media Organization**: Tag and categorize photos for easy searching
- **Sharing**: Share photos and albums with other users (it still update)
- **Comments & Interactions**: Add comments and like/favorite photos
- **User Profiles**: Customizable user profiles with avatars
- **Notifications**: Stay updated on shared content and interactions
- **Storage Management**: Monitor and manage photo storage usage
- **Responsive Design**: Works on desktop and mobile devices
This project 

## Installation

Clone this repository and open it with your favorite code editor (i use vscode)

```bash
git clone https://github.com/huy923/Google-Photos-Clone.git
cd Google-Photos-Clone && code .
```

## Setup

If you to lazy to setup everything you just run file `run_local.sh`. Ah because i'm lazy 😭 to run every single time to setup no comment 😅

One more thing for me it works well on linux but i don't know about windows 😭 so good luck with that 😅 to run file `run_local.sh`. On windows please change your terminal to git bash then run, if you don't know how to just [click here](https://stackoverflow.com/questions/42606837/how-do-i-use-bash-on-windows-from-the-visual-studio-code-integrated-terminal)

```bash
# for Windows
sh run_local.sh

# for Linux/Mac
./run_local.sh
```

All thank to me you're wellcome 😁😁😁.

Or if you want to run on docker, before run docker you need to setup docker first. And one more thing it have any problem please open issue

```bash
# for Windows
sh ./run_docker.sh

# for Linux and mac
./run_docker.sh 
```

If you want to setup by yourself, please follow this steps when you done to install project:

- Setup backend

```bash
  cd backend
  cp .env.example .env
  php artisan key:generate
  php artisan migrate --force
  php artisan storage:link
  cd ..
```

One important thing is to change your public ip address in `.env` file

```bash
# for Linux/Mac
ip route get 1.1.1.1 2>/dev/null | awk '{print $7; exit}'

# for Windows
ipconfig getifaddr en0
```

And then copy it to `.env` file on line `APP_URL=http://127.0.0.1:8000`

- Setup frontend

```bash
  cd frontend
  cp .env.example .env.local
  yarn install
  cd ..
```

In file `.env.local` change `NEXT_PUBLIC_API_URL=http://127.0.0.1:8000` like you did in backend `.env` file

- Run project 

```bash
  cd backend
  php artisan serve
  cd ..
  cd frontend
  yarn run dev
```

## Users

- User storage will create auto (5GB-10GB)
- Password default: `password123`
